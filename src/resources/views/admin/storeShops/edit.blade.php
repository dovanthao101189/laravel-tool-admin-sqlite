@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('global.storeShop.title_singular') }}
    </div>

    <div class="card-body">
        <form action="{{ route("admin.storeShops.update", [$storeShop->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-group {{ $errors->has('type_shop') ? 'has-error' : '' }}">
                <label for="type_shop">{{ trans('global.storeShop.fields.type_shop') }}*</label>
                <input type="text" id="type_shop" name="type_shop" class="form-control" value="{{ old('type_shop', isset($storeShop) ? $storeShop->type_shop : '') }}">
                @if($errors->has('type_shop'))
                    <p class="help-block">
                        {{ $errors->first('type_shop') }}
                    </p>
                @endif
                <p class="helper-block">
                    {{ trans('global.storeShop.fields.type_shop_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('store_name') ? 'has-error' : '' }}">
                <label for="store_name">{{ trans('global.storeShop.fields.store_name') }}*</label>
                <input type="text" id="store_name" name="store_name" class="form-control" value="{{ old('store_name', isset($storeShop) ? $storeShop->store_name : '') }}">
                @if($errors->has('store_name'))
                    <p class="help-block">
                        {{ $errors->first('store_name') }}
                    </p>
                @endif
                <p class="helper-block">
                    {{ trans('global.storeShop.fields.store_name_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('store_front') ? 'has-error' : '' }}">
                <label for="store_front">{{ trans('global.storeShop.fields.store_front') }}*</label>
                <input type="text" id="store_front" name="store_front" class="form-control" value="{{ old('store_front', isset($storeShop) ? $storeShop->store_front : '') }}">
                @if($errors->has('store_front'))
                    <p class="help-block">
                        {{ $errors->first('store_front') }}
                    </p>
                @endif
                <p class="helper-block">
                    {{ trans('global.storeShop.fields.store_front_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('api_key') ? 'has-error' : '' }}">
                <label for="api_key">{{ trans('global.storeShop.fields.api_key') }}*</label>
                <input type="text" id="api_key" name="api_key" class="form-control" value="{{ old('api_key', isset($storeShop) ? $storeShop->api_key : '') }}">
                @if($errors->has('api_key'))
                    <p class="help-block">
                        {{ $errors->first('api_key') }}
                    </p>
                @endif
                <p class="helper-block">
                    {{ trans('global.storeShop.fields.api_key_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('secret_key') ? 'has-error' : '' }}">
                <label for="secret_key">{{ trans('global.storeShop.fields.secret_key') }}*</label>
                <input type="text" id="secret_key" name="secret_key" class="form-control" value="{{ old('secret_key', isset($storeShop) ? $storeShop->secret_key : '') }}">
                @if($errors->has('secret_key'))
                    <p class="help-block">
                        {{ $errors->first('secret_key') }}
                    </p>
                @endif
                <p class="helper-block">
                    {{ trans('global.storeShop.fields.secret_key_helper') }}
                </p>
            </div>
            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
            </div>
        </form>
    </div>
</div>

@endsection
