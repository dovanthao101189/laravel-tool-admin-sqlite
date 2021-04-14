<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\StoreShop;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;


class ToolController extends Controller
{
    private $storeShops;

    public function __construct()
    {
        $this->storeShops = $this->getStoreShopS();
    }

    private function getStoreShopS()
    {
        $data = StoreShop::all();
        $ref = array();
        foreach ($data as $d) {
            $ref[$d->id] = $d;
        }

        return $ref;
    }

    public function create(Request $request)
    {
        $link = $request->input('link', '');
        $site = $request->input('site', []);
        $target = $request->input('target', null);
        $source = $request->input('source', null);
        if ($target == 'collection') {
            if ($source === 'shopify') {
                $arrLink = $this->getArrLinkFromCollectionShopify($link);
                if ($arrLink['success']) {
                    $reviewSite = [];
                    foreach ($arrLink['data'] as $l) {
                        $views = $this->addProduct($l, $site);
                        $reviewSite = array_merge($reviewSite, $views);
                        usleep(1 * 1000000);
                    }
                    return response()->json(['success' => true, 'views' => $reviewSite]);
                }
            } elseif ($source === 'shopbase') {
                $reviewSite = [];
                $arr = parse_url($link);
                $domain = $arr['scheme'] . '://' . $arr['host'];
                $baseGet = $domain . '/api/catalog/products_v2.json?collection_ids=' . $this->getIdShopbase($link) . '&page=';
                $i = 1;
                while ($i >= 1) {
                    $results = $this->getProductByLink($baseGet . $i);
                    $products = json_decode($results['data'], true);
                    $datas = $products['products'];
                    if (!$results['success'] || count($datas) === 0) {
                        break;
                    }
                    $i++;
                    foreach ($datas as $data) {
                        $views = $this->addProductData($data, $site, $source);
                        $reviewSite = array_merge($reviewSite, $views);
                        usleep(1 * 1000000);
                    }
                }

                return response()->json(['success' => true, 'views' => $reviewSite]);
            }

        } else {
            $reviewSite = $this->addProduct($link, $site, $source);
            return response()->json(['success' => true, 'views' => $reviewSite]);
        }

        return response()->json(['success' => true, 'views' => []]);
    }

    private function addProduct($link, $site, $source = 'shopify')
    {
        $link = trim($link);
        if ($link !== '') {
            $sourceData = [];
            $isSuccess = false;
            if ($source === 'shopify') {
                $link = str_replace('.json', '', $link) . '.json';
            }

            if ($source === 'shopbase'){
                $arr = parse_url($link);
                $domain = $arr['scheme'] . '://' . $arr['host'];
                $link = $domain . '/api/catalog/products_v2.json?ids=' . $this->getIdShopbase($link, 'product');
            }

            if ($source === 'shopify' || $source === 'shopbase') {
                $product = $this->getProductByLink($link);
                $isSuccess = $product['success'];
                $productData = json_decode($product['data'], true);
                $sourceData = $source === 'shopify' ? $productData['product'] : $productData['products'][0];
            }

            if ($source === 'teechip'){
                $results = $this->getProductTeechip($link);
                $isSuccess = $results['success'];
                $sourceData = $results['data'];
            }


            if ($isSuccess) {
                $reviewSite = [];
                foreach ($site as $sId) {
                    $sId = intval($sId);
                    if ($sId > 0) {
                        $shop = $this->storeShops[$sId];
                        $results = ['success' => false];
                        if (strtolower($shop->type_shop) === 'shopify') {
                            $results = $this->addProductShopify($sourceData, $source, $shop);
                        }

                        if (strtolower($shop->type_shop) === 'shopbase') {
                            $results = $this->addProductShopbase($sourceData, $source, $shop);
                        }

                        if ($results['success']) {
                            array_push($reviewSite, [
                                "link" => $shop->store_front . $sourceData['handle'],
                                "title" => $sourceData['title']
                            ]);
                        }
                    }
                }

                return $reviewSite;

            }
        }
    }

    private function addProductData($data, $site, $source = 'shopify')
    {
        $totalKey = count((array)$data);
        if ($totalKey > 0) {
            $reviewSite = [];

            foreach ($site as $sId) {
                $sId = intval($sId);
                if ($sId > 0) {
                    $shop = $this->storeShops[$sId];
                    $results = ['success' => false];
                    if (strtolower($shop->type_shop) === 'shopify') {
                        $results = $this->addProductShopify($data, $source, $shop);
                    }

                    if (strtolower($shop->type_shop) === 'shopbase') {
                        $results = $this->addProductShopbase($data, $source, $shop);
                    }

                    if ($results['success']) {
                        array_push($reviewSite, [
                            "link" => $shop->store_front . $data['handle'],
                            "title" => $data['title']
                        ]);
                    }
                }
            }

            return $reviewSite;
        }
    }

    private function getArrLinkFromCollectionShopify($link)
    {
        $parse = parse_url($link);
        $domain = $parse['scheme'] . '://' . $parse['host'];
        $link = trim($link);
        $baseGet = 'https://ncu8zq1h33.execute-api.us-west-2.amazonaws.com/default/shopify_collections_extractor?url=' . $link . '?page=';
        $results = $this->getProductByLink($baseGet . '1');
        $data = json_decode($results['data'], true);
        if (!$results['success'] || count($data) === 0) {
            return [
                'success' => false,
                'data' => []
            ];
        }
        $arrLink = [];
        array_push($arrLink, $data);

        $i = 2;
        while ($i >= 2) {
            $results = $this->getProductByLink($baseGet . $i);
            $data = json_decode($results['data'], true);
            if (!$results['success'] || count($data) === 0) {
                break;
            }
            $i++;
            array_push($arrLink, $data);
        }

        $arrLinkValid = [];
        foreach ($arrLink as $v) {
            foreach ($v as $link) {
                if (!in_array($link, $arrLinkValid)) {
                    array_push($arrLinkValid, $domain . $link . '.json');
                }
            }
        }
        return [
            'success' => true,
            'data' => $arrLinkValid
        ];
    }

    private function getProductByLink($link)
    {
        $client = new Client();
        $request = $client->get($link);
        if ($request->getStatusCode() === 200) {
            return [
                'success' => true,
                'data' => $request->getBody()->getContents()
            ];
        }

        return [
            'success' => false,
            'data' => []
        ];
    }

    private function addImageShopify($data, $shop)
    {
        $apiKey = $shop->api_key;
        $secretKey = $shop->secret_key;
        $store = $shop->store_name;
        $client = new Client();
        $productId = $data['product_id'];
        $endpoint = "https://${apiKey}:${secretKey}@${store}.myshopify.com/admin/api/2021-01/products/${productId}/images.json";
        $request = $client->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['image' => $data])
        ]);
        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            return [
                'success' => true,
                'data' => $request->getBody()->getContents()
            ];
        }

        return [
            'success' => false,
            'data' => []
        ];
    }

    private function addProductShopify($product, $source, $shop)
    {
        $data = [];
        $images = [];
        if (array_key_exists('images', $product)) {
            $images = $product['images'];
            unset($product['images']);
        }

        if (array_key_exists('image', $product)) {
            unset($product['image']);
        }

        if ($source === 'shopbase') {
            $options = [];
            if (array_key_exists('option_sets', $product)) {
                $options = $product['option_sets'];
                unset($product['option_sets']);
            }
            $product['published_at'] = date("Y-m-d H:i:s", $product['published_at']);
            $data['options'] = [];
            foreach ($options as $op) {
                $values = [];
                foreach ($op['options'] as $sop) {
                    array_push($values, $sop['value']);
                }
                array_push($data['options'], [
                    "name" => $op['value'],
                    "position" => 1,
                    "values" => $values
                ]);
            }
        }

        $variantIdAndSku = [];
        foreach ($product as $k => $v) {
            if ($k === 'variants') {
                foreach ($product[$k] as $sk => $sv) {
                    if (strlen(trim(strval($sv['sku']))) > 0) {
                        $variantIdAndSku[$sv['id']] = ['sku' => $sv['sku'], 'id' => $sv['id']];
                    } else {
                        $variantIdAndSku[$sv['id']] = ['title' => $sv['title'], 'id' => $sv['id']];
                    }
                    $data[$k][$sk] = $sv;
                    unset($data[$k][$sk]['image_id']);
                    unset($data[$k][$sk]['fulfillment_service']);
                }
            } elseif ($k === 'body_html') {
                $data['body_html'] = preg_replace("/<a href=.*?>(.*?)<\/a>/", "", $v);
                $data['body_html'] = str_replace("today", "", $data['body_html']);
                $data['body_html'] = str_replace("Today", "", $data['body_html']);
                $data['body_html'] = str_replace("TODAY", "", $data['body_html']);
            } else {
                $data[$k] = $v;
            }

        }

        $apiKey = $shop->api_key;
        $secretKey = $shop->secret_key;
        $store = $shop->store_name;
        $client = new Client();
        $endpoint = "https://${apiKey}:${secretKey}@${store}.myshopify.com/admin/api/2021-01/products.json";
        $request = $client->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['product' => $data])
        ]);

        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            $product = $request->getBody()->getContents();
            $product_d = json_decode($product, true);

            foreach ($product_d['product']['variants'] as $variant) {
                foreach ($variantIdAndSku as $k => $val) {
                    if (array_key_exists('sku', $val)) {
                        if ($val['sku'] === $variant['sku']) {
                            $variantIdAndSku[$k]['new_id'] = $variant['id'];
                        }
                    } else {
                        if ($val['title'] === $variant['title']) {
                            $variantIdAndSku[$k]['new_id'] = $variant['id'];
                        }
                    }
                }
            }

            foreach ($images as $i) {
                $i['product_id'] = $product_d['product']['id'];
                $dataI = $this->mapVariantIdAfterInsert($i, $variantIdAndSku);
                $this->addImageShopify($dataI, $shop);
                usleep(1 * 1000000);
            }
            return [
                'success' => true,
                'data' => $product
            ];
        }

        return [
            'success' => false,
            'data' => []
        ];
    }

    private function getIdShopbase($link, $source = 'collection')
    {
        $html = file_get_contents($link);
        preg_match_all('/<script>(.*?)<\/script>/s', $html, $matches);
        $id = null;
        if (count($matches) > 0) {
            foreach ($matches as $k => $v) {
                foreach ($v as $sk => $sv) {
                    $strValue = str_replace(' ', '', strval($sv));
                    if (strpos($strValue, 'window.__INITIAL_STATE__=') !== false) {
                        $strData = str_replace(
                            'window.__INITIAL_STATE__=',
                            '',
                            $sv);
                        $strData = str_replace(
                            ';(function(){var s;(s=document.currentScript||document.scripts[document.scripts.length-1]).parentNode.removeChild(s);}());',
                            '',
                            $strData);
                        $data = json_decode($strData, true);
                        if (json_last_error() === JSON_ERROR_NONE || json_last_error() === 0) {
                            if ($source === 'collection') {
                                if (array_key_exists('collection', $data)) {
                                    if (array_key_exists('collection', $data['collection'])) {
                                        if (array_key_exists('id', $data['collection']['collection'])) {
                                            $id = $data['collection']['collection']['id'];
                                        }
                                    }
                                }
                            } else {
                                if (array_key_exists('customProduct', $data)) {
                                    if (array_key_exists('product', $data['customProduct'])) {
                                        if (array_key_exists('id', $data['customProduct']['product'])) {
                                            $id = $data['customProduct']['product']['id'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $id;
    }

    private function addImageShopbase($data, $shop)
    {
        $apiKey = $shop->api_key;
        $secretKey = $shop->secret_key;
        $store = $shop->store_name;
        $client = new Client();
        $productId = $data['product_id'];
        $endpoint = "https://${apiKey}:${secretKey}@${store}.onshopbase.com/admin/products/${productId}/images.json";
        $request = $client->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['image' => $data])
        ]);

        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            return [
                'success' => true,
                'data' => $request->getBody()->getContents()
            ];
        }

        return [
            'success' => false,
            'data' => []
        ];
    }

    private function addProductShopbase($product, $source = 'shopify', $shop)
    {
        $data = [];
        $images = [];
        if (array_key_exists('images', $product)) {
            $images = $product['images'];
            unset($product['images']);
        }

        if (array_key_exists('image', $product)) {
            unset($product['image']);
        }

        $idValueOptions = [];
        if ($source === 'shopbase') {
            $optionSets = $product['option_sets'];
            foreach ($optionSets as $k => $v) {
                foreach ($v['options'] as $sk => $sv) {
                    $idValueOptions[$sv['id']] = $sv;
                }
            }
        }
        $keys = array_keys($idValueOptions);
        $tmp = [];
        $variantIdAndSku = [];
        foreach ($product as $k => $v) {
            if ($k === 'images') {
                foreach ($product[$k] as $sk => $sv) {
                    $data[$k][$sk]['position'] = $sv['position'];
                    $data[$k][$sk]['src'] = $sv['src'];
                }
            } elseif ($k === 'variants') {
                foreach ($product[$k] as $sk => $sv) {
                    if (strlen(trim(strval($sv['sku']))) > 0) {
                        $variantIdAndSku[$sv['id']] = ['sku' => $sv['sku'], 'id' => $sv['id']];
                    } else {
                        $variantIdAndSku[$sv['id']] = ['title' => $sv['title'], 'id' => $sv['id']];
                    }

                    $data[$k][$sk] = $sv;
                    if (array_key_exists('option1', $sv)) {
                        if (count($keys) > 0 && $source === 'shopbase') {
                            if (in_array($sv['option1'], $keys)) {
                                array_push($tmp, $sv['option1']);
                                $data[$k][$sk]['option1'] = $idValueOptions[$sv['option1']]['value'];
                            } else {
                                $data[$k][$sk]['option1'] = null;
                            }
                        } else {
                            $data[$k][$sk]['option1'] = strval($sv['option1']);
                        }
                    }
                    if (array_key_exists('option2', $sv)) {
                        if (count($keys) > 0 && $source === 'shopbase') {
                            if (in_array($sv['option2'], $keys)) {
                                array_push($tmp, $sv['option2']);
                                $data[$k][$sk]['option2'] = $idValueOptions[$sv['option2']]['value'];
                            } else {
                                $data[$k][$sk]['option2'] = null;
                            }
                        } else {
                            $data[$k][$sk]['option2'] = strval($sv['option2']);
                        }
                    }
                    if (array_key_exists('option3', $sv)) {
                        if (count($keys) > 0 && $source === 'shopbase') {
                            if (in_array($sv['option3'], $keys)) {
                                $data[$k][$sk]['option3'] = $idValueOptions[$sv['option3']]['value'];
                            } else {
                                $data[$k][$sk]['option3'] = null;
                            }
                        } else {
                            $data[$k][$sk]['option3'] = strval($sv['option3']);
                        }
                    }
                }
            } elseif ($k === 'body_html') {
                $data['body_html'] = preg_replace("/<a href=.*?>(.*?)<\/a>/", "", $v);
                $data['body_html'] = str_replace("today", "", $data['body_html']);
                $data['body_html'] = str_replace("Today", "", $data['body_html']);
                $data['body_html'] = str_replace("TODAY", "", $data['body_html']);
            } else {
                $data[$k] = $v;
            }
        }
        unset($data['tags']);
        $apiKey = $shop->api_key;
        $secretKey = $shop->secret_key;
        $store = $shop->store_name;
        $client = new Client();
        $endpoint = "https://${apiKey}:${secretKey}@${store}.onshopbase.com/admin/products.json";
        $request = $client->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['product' => $data])
        ]);

        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            $product = $request->getBody()->getContents();
            $product_d = json_decode($product, true);
            foreach ($product_d['product']['variants'] as $variant) {
                foreach ($variantIdAndSku as $k => $val) {
                    if (array_key_exists('sku', $val)) {
                        if ($val['sku'] === $variant['sku']) {
                            $variantIdAndSku[$k]['new_id'] = $variant['id'];
                        }
                    } else {
                        if ($val['title'] === $variant['title']) {
                            $variantIdAndSku[$k]['new_id'] = $variant['id'];
                        }
                    }
                }
            }
            foreach ($images as $i) {
                $i['product_id'] = $product_d['product']['id'];
                $dataI = $this->mapVariantIdAfterInsert($i, $variantIdAndSku);
                $this->addImageShopbase($dataI, $shop);
                usleep(1 * 1000000);
            }
            return [
                'success' => true,
                'data' => $product
            ];
        }

        return [
            'success' => false,
            'data' => []
        ];
    }

    private function mapVariantIdAfterInsert($dataImage, $variantIdAndSku)
    {
        $VariantIds = [];
        if (array_key_exists('variant_ids', $dataImage)) {
            foreach ($variantIdAndSku as $k => $val) {
                if (in_array($k, $dataImage['variant_ids'])) {
                    array_push($VariantIds, $val['new_id']);
                }
            }
            $dataImage['variant_ids'] = $VariantIds;
        }

        return $dataImage;
    }

    private function getProductTeechip($link)
    {
        $html = file_get_contents($link);
        $tagScript = strip_tags($html, '<script>');
        $string = str_replace("<script", "|||||<script", $tagScript);
        $string = str_replace("</script>", "</script>|||||", $string);
        $arr = explode("|||||", $string);
        $strData = null;
        if (count($arr) > 0) {
            foreach ($arr as $k => $v) {
                if (strpos($v, 'window.__INITIAL_STATE__') !== false) {
                    $strData = str_replace('<script nonce="**CSP_NONCE**">', '', $v);
                    $strData = str_replace('</script>', '', $strData);
                    $strData = str_replace('<script>', '', $strData);
                    $strData = str_replace(
                        'window.__INITIAL_STATE__=',
                        '',
                        $strData);
                    $strData = str_replace(
                        'window.__INITIAL_STATE__ =',
                        '',
                        $strData);
                    $strData = str_replace(
                        ';(function(){var s;(s=document.currentScript||document.scripts[document.scripts.length-1]).parentNode.removeChild(s);}());',
                        '',
                        $strData);
                    $strData = trim($strData);
                    $lastCharacter = substr($strData, -1);
                    if ($lastCharacter === ';') {
                        $strData = substr($strData, 0, -1);
                    }
                    $data = json_decode($strData, true);
                    if (json_last_error() === JSON_ERROR_NONE || json_last_error() === 0) {
                        $dataFormat = $this->convertTeechipToShopify($data);
                        return [
                            'success' => true,
                            'data' => $dataFormat
                        ];
//                        @$doc = new DOMDocument();
//                        @$doc->loadHTML($html);
//                        $xpath = new DomXPath($doc);
//                        $nodeList = $xpath->query("//div[@class='bc-grey-200 bwt-1 w-full']");
//                        $node = $nodeList->item(0);
//                        $innerHTML= '';
//                        $children = $node->childNodes;
//                        foreach ($children as $child) {
//                            $innerHTML .= $child->ownerDocument->saveXML($child);
//                        }
//                        $data['body_html'] = $innerHTML;
                    }
                }
            }
        }

        return [
            'success' => false,
            'data' => []
        ];
    }

    private function convertTeechipToShopify($data)
    {
        $title = '';
        $sizeAll = ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'];
        $colors = [];
        $sizesMulti = [];
        $tagsMulti = [];
        $images = [];
        $imagesCheck = [];
        $variants = [];
        $productsFetch = $data['vias']['RetailProduct']['docs']['code'];
        $codeCurrent = $data['routing']['locationBeforeTransitions']['query']['retailProductCode'];
        $sameCodeCurrent = explode("-", $codeCurrent);
        $groupCodeCurrent = $sameCodeCurrent[2];

        $positionImage = 1;
        if (count($productsFetch) > 0) {
            $title = $productsFetch[$codeCurrent]['doc']['names']['design'];
            foreach ($productsFetch as $k => $p) {
                $sameCode = explode("-", $k);
                $groupCode = $sameCode[2];
                if ($groupCodeCurrent === $groupCode) {
                    $product = $p['doc'];
                    if (!in_array($product['color'], $colors)) {
                        array_push($colors, $product['color']);
                    }

                    $title = $product['names']['design'] . ' ' . $product['names']['product'];
                    $price = $product['price'];
                    if ($price > 0) {
                        $price = $price / 100;
                    }
                    $tags = $product['tags']['product'];

                    foreach ($tags as $tag_key => $tag) {
                        if (!in_array($tag, $tagsMulti)) {
                            array_push($tagsMulti, $tag);
                        }
                    }

                    $imagesFetch = $product['images'];
                    $sizesByVariant = [];
                    foreach ($imagesFetch as $k_img => $img) {
                        if (!in_array($img['prefix'] . '/regular.jpg', $imagesCheck)) {
                            $awsUrl = '';
                            $url = $img['prefix'].'/regular.jpg';
                            $contents = file_get_contents($url);
                            $name = substr($url, strrpos($url, '/') + 1);
                            $name = explode('?', $name);
                            $fileName = $name[0];
                            $filePath = 'teechip/products/'.$k.'/'.$fileName;
                            $awsSaved = Storage::disk('s3')->put($filePath, $contents, 'public');
                            if ($awsSaved) {
                                $awsUrl = Storage::disk('s3')->url($filePath);
                            }

                            array_push($imagesCheck, $img['prefix'] . '/regular.jpg');
                            array_push($images, [
                                'position' => $positionImage,
                                'src' => $awsUrl,
                                'variant_ids' => [$product['_id']],
                            ]);
                        }
                        $positionImage++;

                        $sizes = $img['sizes'];
                        foreach ($sizes as $k_size => $size) {
                            $size = $size['size'];
                            $sizeInsert = [];
                            if (strtolower($size) === 'all') {
                                $sizeInsert = $sizeAll;
                            } else {
                                $sizeInsert = [$size];
                            }

                            foreach ($sizeInsert as $s) {
                                if (!in_array($s, $sizesMulti)) {
                                    array_push($sizesMulti, $s);
                                }

                                if (!in_array($s, $sizesByVariant)) {
                                    array_push($sizesByVariant, $s);
                                }
                            }
                        }
                    }

                    foreach ($sizesByVariant as $sizeOp) {
                        array_push($variants, [
                            'id' => $product['_id'],
                            'title' => $title,
                            'product_id' => $product['productId'],
                            'price' => $price,
                            'sku' => $k,
                            "option1" => $product['color'],
                            "option2" => $sizeOp,
                        ]);
                    }
                }
            }
        }

        $options = [
            ['name' => 'Size', 'position' => 1, 'values' => $sizesMulti],
            ['name' => 'Color', 'position' => 2, 'values' => $colors],
        ];


        return [
            'title' => $title,
            'body_html' => '',
            'handle' => $codeCurrent,
            'vendor' => '',
            'product_type' => '',
            'published_at' => date('Y-m-d H:i:s'),
            'published_scope' => 'web',
            'status' => 'active',
            "tags" => $tagsMulti,
            "options" => $options,
            "variants" => $variants,
            "images" => $images,
        ];
    }
}
