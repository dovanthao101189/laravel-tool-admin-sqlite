<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreShopRequest extends FormRequest
{
    public function rules()
    {
        return [
            'type_shop' => [
                'required',
            ],
            'store_name' => [
                'required',
            ],
            'store_front' => [
                'required',
            ],
            'api_key' => [
                'required',
            ],
            'secret_key' => [
                'required',
            ],
        ];
    }
}
