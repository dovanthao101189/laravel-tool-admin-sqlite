<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassDestroyStoreShopRequest extends FormRequest
{
    public function rules()
    {
        return [
            'ids'   => 'required|array',
            'ids.*' => 'exists:storeShops,id',
        ];
    }
}
