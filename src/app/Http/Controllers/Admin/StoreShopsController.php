<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyStoreShopRequest;
use App\Http\Requests\StoreStoreShopRequest;
use App\Http\Requests\UpdateStoreShopRequest;
use App\StoreShop;

class StoreShopsController extends Controller
{
    public function index()
    {
        $storeShops = StoreShop::all();

        return view('admin.storeShops.index', compact('storeShops'));
    }

    public function create()
    {
        return view('admin.storeShops.create');
    }

    public function store(StoreStoreShopRequest $request)
    {
        $storeShop = StoreShop::create($request->all());

        return redirect()->route('admin.storeShops.index');
    }

    public function edit(StoreShop $storeShop)
    {
        return view('admin.storeShops.edit', compact('storeShop'));
    }

    public function update(UpdateStoreShopRequest $request, StoreShop $storeShop)
    {
        $storeShop->update($request->all());

        return redirect()->route('admin.storeShops.index');
    }

    public function show(StoreShop $storeShop)
    {
        return view('admin.storeShops.show', compact('storeShop'));
    }

    public function destroy(StoreShop $storeShop)
    {
        $storeShop->delete();

        return back();
    }

    public function massDestroy(MassDestroyStoreShopRequest $request)
    {
        StoreShop::whereIn('id', request('ids'))->delete();

        return response(null, 204);
    }
}
