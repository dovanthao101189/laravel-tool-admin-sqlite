@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('global.storeShop.title') }}
    </div>

    <div class="card-body">
        <table class="table table-bordered table-striped">
            <tbody>
                <tr>
                    <th>
                        {{ trans('global.storeShop.fields.type_shop') }}
                    </th>
                    <td>
                        {{ $storeShop->type_shop }}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.storeShop.fields.store_name') }}
                    </th>
                    <td>
                        {{ $storeShop->store_name }}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.storeShop.fields.store_front') }}
                    </th>
                    <td>
                        {{ $storeShop->store_front }}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.storeShop.fields.api_key') }}
                    </th>
                    <td>
                        {{ $storeShop->api_key }}
                    </td>
                </tr>
                <tr>
                    <th>
                        {{ trans('global.storeShop.fields.secret_key') }}
                    </th>
                    <td>
                        {{ $storeShop->secret_key }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
