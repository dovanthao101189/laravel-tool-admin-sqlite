<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\StoreShop;
use GuzzleHttp\Client;
use Illuminate\Http\Request;


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

        $storeShops = [];
        foreach ($site as $sId) {
            $sId = intval($sId);
            if ($sId > 0) {
                $shop = $this->storeShops[$sId];
                array_push($storeShops, $shop);
            }
        }

        return $this->callImportCore($link, $target, $source, $storeShops);
    }

    private function callImportCore($link, $target, $source, $storeShops)
    {
        $payload = [
            'link' => $link,
            'source' => $source,
            'target' => $target,
            'store_shops' => $storeShops,
        ];

        $client = new Client();
        $request = $client->post("http://54.151.242.94/api/import", [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload)
        ]);
        if ($request->getStatusCode() === 200 || $request->getStatusCode() === 201) {
            return response()->json(json_decode($request->getBody()->getContents(), true));
        }

        return response()->json(['success' => false, 'views' => []]);
    }
}
