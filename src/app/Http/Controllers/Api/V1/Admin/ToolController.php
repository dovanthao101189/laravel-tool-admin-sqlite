<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;


class ToolController extends Controller
{
    private $shopifyApiKey;
    private $shopifySecretKey;
    private $shopifyStore;
    private $shopifyViewLink;

    private $shopbaseApiKey;
    private $shopbaseSecretKey;
    private $shopbaseStore;
    private $shopbaseViewLink;


    public function __construct()
    {
        $this->shopifyApiKey = config('app.shopify_api_key');
        $this->shopifySecretKey = config('app.shopify_secret_key');
        $this->shopifyStore = config('app.shopify_store');
        $this->shopifyViewLink = config('app.shopify_view_link');

        $this->shopbaseApiKey = config('app.shopbase_api_key');
        $this->shopbaseSecretKey = config('app.shopbase_secret_key');
        $this->shopbaseStore = config('app.shopbase_store');
        $this->shopbaseViewLink = config('app.shopbase_view_link');
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
                if ($arrLink['success']){
                    foreach ($arrLink['data'] as $l) {
                        $this->addProduct($l, $site);
                        usleep( 1 * 1000000 );
                    }
                    return response()->json(['total' => count($arrLink['data'])]);
//                    return view("products.create", ['success' => true, 'total' => count($arrLink['data'])]);
                }
            } else {
                $total = 0;
                $arr = parse_url($link);
                $domain = $arr['scheme'].'://'.$arr['host'];
                $baseGet = $domain.'/api/catalog/products_v2.json?collection_ids='.$this->getIdShopbase($link).'&page=';
                $i = 1;
                while ($i >= 1) {
                    $results = $this->getProductByLink($baseGet.$i);
                    $products = json_decode($results['data'], true);
                    $datas = $products['products'];
                    if(!$results['success'] || count($datas) === 0) {
                        break;
                    }
                    $i++;
                    $total += count($datas);
                    foreach ($datas as $data) {
                        $this->addProductData($data, $site, $source);
                        usleep( 1 * 1000000 );
                    }
                }

                return response()->json(['total' => $total]);
//                return view("products.create", ['success' => true, 'total' => $total]);
            }

        } else {
            $reviewSite = $this->addProduct($link, $site, $source);
            return response()->json([]);
//            return view("products.create", ['success' => true, 'view' => $reviewSite]);
        }

        return response()->json([]);
//        return view("products.create");
    }

    private function addProduct($link, $site, $source='shopify') {
        $link = trim($link);
        if ($link !== '') {
            if ($source === 'shopify') {
                $link = str_replace('.json', '', $link) . '.json';
            } else {
                $arr = parse_url($link);
                $domain = $arr['scheme'].'://'.$arr['host'];
                $link = $domain.'/api/catalog/products_v2.json?ids='.$this->getIdShopbase($link, 'product');
            }

            $product = $this->getProductByLink($link);
            if ($product['success']) {
                $reviewSite = [];
                if (in_array('shopify', $site)) {
                    $productData = json_decode($product['data'], true);
                    $sourceData = $source === 'shopify' ? $productData['product'] : $productData['products'][0];
                    $results = $this->addProductShopify($sourceData, $source);
                    if ($results['success']) {
                        $data = json_decode($results['data'], true);
                        $productHandle = $data['product']['handle'] ? $data['product']['handle'] : '';
                        array_push($reviewSite, [
                            "link" => $this->shopifyViewLink.$productHandle,
                            "name" => "shopify"
                        ]);
                    }
                }

                if (in_array('shopbase', $site)) {
                    $productData = json_decode($product['data'], true);
                    $sourceData = $source === 'shopify' ? $productData['product'] : $productData['products'][0];
                    $results = $this->addProductShopbase($sourceData, $source);
                    if ($results['success']) {
                        $data = json_decode($results['data'], true);
                        $productHandle = $data['product']['handle'] ? $data['product']['handle'] : '';
                        array_push($reviewSite, [
                            "link" => $this->shopbaseViewLink.$productHandle,
                            "name" => "shopbase"
                        ]);
                    }
                }

                return $reviewSite;
            }
        }
    }

    private function addProductData($data, $site, $source='shopify') {
        $totalKey = count((array)$data);
        if ($totalKey > 0) {
            $reviewSite = [];
            if (in_array('shopify', $site)) {
                $results = $this->addProductShopify($data, $source);
                if ($results['success']) {
                    $productHandle = $data['handle'] ? $data['handle'] : '';
                    array_push($reviewSite, [
                        "link" => $this->shopifyViewLink.$productHandle,
                        "name" => "shopify"
                    ]);
                }
            }

            if (in_array('shopbase', $site)) {
                $results = $this->addProductShopbase($data, $source);
                if ($results['success']) {
                    $productHandle = $data['handle'] ? $data['handle'] : '';
                    array_push($reviewSite, [
                        "link" => $this->shopbaseViewLink.$productHandle,
                        "name" => "shopbase"
                    ]);
                }
            }

            return $reviewSite;
        }
    }

    private function getArrLinkFromCollectionShopify($link)
    {
        $parse = parse_url($link);
        $domain = $parse['scheme']. '://' .$parse['host'];
        $link = trim($link);
        $baseGet = 'https://ncu8zq1h33.execute-api.us-west-2.amazonaws.com/default/shopify_collections_extractor?url=' . $link . '?page=';
        $results = $this->getProductByLink($baseGet .'1');
        $data = json_decode($results['data'], true);
        if(!$results['success'] || count($data) === 0) {
            return [
                'success' => false,
                'data' => []
            ];
        }
        $arrLink = [];
        array_push($arrLink, $data);

        $i = 2;
        while ($i >= 2) {
            $results = $this->getProductByLink($baseGet.$i);
            $data = json_decode($results['data'], true);
            if(!$results['success'] || count($data) === 0) {
                break;
            }
            $i++;
            array_push($arrLink, $data);
        }

        $arrLinkValid = [];
        foreach ($arrLink as $v) {
            foreach ($v as $link) {
                if (!in_array($link, $arrLinkValid)) {
                    array_push($arrLinkValid, $domain.$link.'.json');
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

    private function addImageShopify($data)
    {
        $apiKey = $this->shopifyApiKey;
        $secretKey = $this->shopifySecretKey;
        $store = $this->shopifyStore;
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

    private function addProductShopify($product, $source='shopify')
    {
        $data = [];
        if ($source==='shopbase') {
            $product['published_at'] = date("Y-m-d H:i:s", $product['published_at']);
        }
        $images = $product['images'];
        unset($product['images']);
        unset($product['image']);
        $options = [];
        if (array_key_exists('option_sets', $product)) {
            $options = $product['option_sets'];
            unset($product['option_sets']);
        }
        if ($source==='shopbase') {
            $data['options'] = [];
            foreach ($options as $op) {
                $values = [];
                foreach ($op['options'] as $sop) {
                    array_push($values, $sop['value']);
                }
                array_push($data['options'], [
//                            "id" => $op['id'],
//                            "product_id" => $product_d['product']['id'],
                    "name" => $op['value'],
                    "position" => 1,
                    "values" => $values
                ]);
            }
        }

        foreach ($product as $k=>$v) {
            if ($k === 'variants') {
                foreach ($product[$k] as $sk=>$sv) {
                    $data[$k][$sk] = $sv;
                    unset($data[$k][$sk]['image_id']);
                    unset($data[$k][$sk]['fulfillment_service']);
                }
            } elseif($k === 'body_html') {
                $data['body_html'] = preg_replace("/<a href=.*?>(.*?)<\/a>/","",$v);
                $data['body_html'] = str_replace("today","",$data['body_html']);
                $data['body_html'] = str_replace("Today","",$data['body_html']);
                $data['body_html'] = str_replace("TODAY","",$data['body_html']);
            } else {
                $data[$k] = $v;
            }

        }
        $apiKey = $this->shopifyApiKey;
        $secretKey = $this->shopifySecretKey;
        $store = $this->shopifyStore;
        $client = new Client();
        $endpoint = "https://${apiKey}:${secretKey}@${store}.myshopify.com/admin/api/2021-01/products.json";
        $request = $client->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['product' => $data])
        ]);

        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            $product = $request->getBody()->getContents();
            $product_d = json_decode($product, true);
            foreach ($images as $i) {
                $i['product_id'] = $product_d['product']['id'];
                $this->addImageShopify($i);
                usleep( 1 * 1000000 );
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

    private function getIdShopbase($link, $source = 'collection') {
        $html = file_get_contents($link);
        preg_match_all('/<script>(.*?)<\/script>/s', $html, $matches);
        $id = null;
        if (count($matches) > 0) {
            foreach ($matches as $k=>$v) {
                foreach ($v as $sk=>$sv) {
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

    private function addImageShopbase($data)
    {
        $apiKey = $this->shopbaseApiKey;
        $secretKey = $this->shopbaseSecretKey;
        $store = $this->shopbaseStore;
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

    private function addProductShopbase($product, $source='shopify')
    {
        $data = [];
        $images = $product['images'];
        unset($product['images']);
        unset($product['image']);
        foreach ($product as $k=>$v) {
            if ($k === 'images') {
                foreach ($product[$k] as $sk=>$sv) {
                    $data[$k][$sk]['position'] = $sv['position'];
                    $data[$k][$sk]['src'] = $sv['src'];
                }
            } elseif($k === 'variants') {
                foreach ($product[$k] as $sk=>$sv) {
                    if (array_key_exists('option1', $sv)) {
                        $data[$k][$sk]['option1'] = strval($sv['option1']);
                    }

                    if (array_key_exists('option2', $sv)) {
                        $data[$k][$sk]['option2'] = strval($sv['option2']);
                    }
                }
            }  elseif($k === 'body_html') {
                $data['body_html'] = preg_replace("/<a href=.*?>(.*?)<\/a>/","",$v);
                $data['body_html'] = str_replace("today","",$data['body_html']);
                $data['body_html'] = str_replace("Today","",$data['body_html']);
                $data['body_html'] = str_replace("TODAY","",$data['body_html']);
            } else {
                $data[$k] = $v;
            }
        }
        unset($data['tags']);
        $apiKey = $this->shopbaseApiKey;
        $secretKey = $this->shopbaseSecretKey;
        $store = $this->shopbaseStore;
        $client = new Client();
        $endpoint = "https://${apiKey}:${secretKey}@${store}.onshopbase.com/admin/products.json";
        $request = $client->post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['product' => $data])
        ]);

        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            $product = $request->getBody()->getContents();
            $product_d = json_decode($product, true);
            foreach ($images as $i) {
                $i['product_id'] = $product_d['product']['id'];
                $this->addImageShopbase($i);
                usleep( 1 * 1000000 );
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
}
