@extends('layouts.admin')
@section('content')
<div style="margin-bottom: 10px;" class="row">
    <div class="col-lg-12">
        <a class="btn btn-success" href="{{ route("admin.storeShops.create") }}">
            {{ trans('global.add') }} {{ trans('global.storeShop.title_singular') }}
        </a>
    </div>
</div>
<div class="card">
    <div class="card-header">
        {{ trans('global.storeShop.title_singular') }} {{ trans('global.list') }}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('global.storeShop.fields.type_shop') }}
                        </th>
                        <th>
                            {{ trans('global.storeShop.fields.store_name') }}
                        </th>
                        <th>
                            {{ trans('global.storeShop.fields.store_front') }}
                        </th>
                        <th>
                            {{ trans('global.storeShop.fields.api_key') }}
                        </th>
                        <th>
                            {{ trans('global.storeShop.fields.secret_key') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($storeShops as $key => $storeShop)
                        <tr data-entry-id="{{ $storeShop->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $storeShop->type_shop ?? '' }}
                            </td>
                            <td>
                                {{ $storeShop->store_name ?? '' }}
                            </td>
                            <td>
                                {{ $storeShop->store_front ?? '' }}
                            </td>
                            <td>
                                {{ $storeShop->api_key ?? '' }}
                            </td>
                            <td>
                                {{ $storeShop->secret_key ?? '' }}
                            </td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.storeShops.show', $storeShop->id) }}">
                                    {{ trans('global.view') }}
                                </a>
                                <a class="btn btn-xs btn-info" href="{{ route('admin.storeShops.edit', $storeShop->id) }}">
                                    {{ trans('global.edit') }}
                                </a>
                                <form action="{{ route('admin.storeShops.destroy', $storeShop->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                </form>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.storeShops.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
  dtButtons.push(deleteButton)

  $('.datatable:not(.ajaxTable)').DataTable({ buttons: dtButtons })
})

</script>
@endsection
