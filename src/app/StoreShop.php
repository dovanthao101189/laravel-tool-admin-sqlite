<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreShop extends Model
{
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'type_shop',
        'store_name',
        'store_front',
        'api_key',
        'secret_key',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $table = 'store_shops';
}
