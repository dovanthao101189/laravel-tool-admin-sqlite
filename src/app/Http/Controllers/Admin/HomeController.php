<?php

namespace App\Http\Controllers\Admin;

use App\StoreShop;

class HomeController
{
    public function index()
    {
        $storeShops = StoreShop::all();

        return view('home', compact('storeShops'));
    }
}
