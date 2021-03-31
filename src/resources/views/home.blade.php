@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-primary">
{{--                <form method="post" action="{{ route('admin.tool') }}">--}}
                <form id="ajaxform">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <input type="text" placeholder="Enter link" required class="form-control" name="link"/>
                        </div>


                        <div class="form-group">
                            <label for="first_name">Insert:</label>
                            <div style="margin-left: 25px" class="form-group">
                                <!-- Checked checkbox -->
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="shopify"
                                        id="shopifyChecked"
                                        name="site"
                                        checked
                                    />
                                    <label class="form-check-label" for="shopifyChecked">
                                        shopify
                                    </label>
                                </div>


                                <!-- Default checkbox -->
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="shopbase"
                                        name="site"
                                        id="shopbaseChecked"
                                        checked
                                    />
                                    <label class="form-check-label" for="shopbaseChecked">
                                        shopbase
                                    </label>
                                </div>
                            </div>
                        </div>



                        <div class="form-group">
                            <label for="first_name">Target(Insert):</label>
                            <div style="margin-left: 25px" class="form-group">
                                <div class="form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" value="product" checked name="target">Product
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" value="collection" name="target">Collection
                                    </label>
                                </div>
                            </div>
                        </div>



                        <div class="form-group">
                            <label for="first_name">Source from:</label>
                            <div style="margin-left: 25px" class="form-group">
                                <div class="form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" value="shopify" checked name="source">Shopify
                                    </label>
                                </div>
                                <div class="form-check-inline">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input" value="shopbase" name="source">Shopbase
                                    </label>
                                </div>
                            </div>
                        </div>


                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer" id="bt_saved">
                        <button type="button" id="save-data" class="btn btn-primary">Add</button>

                        <button class="btn btn-primary hide" id="save-data-disable" type="button" disabled>
                            <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
@section('scripts')
@parent
<script>
    let Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1000 * 10
    });
    $("#save-data").click(function(event){
        event.preventDefault();
        let site =[];
        $.each($("input[name='site']:checked"), function(){
            site.push($(this).val());
        });
        let link = $("input[name=link]").val();
        let target = $("input[name=target]:checked").val();
        let source = $("input[name=source]:checked").val();
        let _token   = $('meta[name="csrf-token"]').attr('content');
        // console.log('link: ', link);
        // console.log('target: ', target);
        // console.log('source: ', source);
        // console.log('site: ', site);

        if(link.trim().length === 0 || site.length === 0 || target.trim().length === 0 || source.trim().length === 0) {
            alert('input invalid!')
        } else {
            $("#save-data").addClass("hide");
            $("#save-data-disable").removeClass("hide");
            $.ajax({
                url: '{{ route('admin.tool') }}',
                type: "POST",
                data:{
                    link:link,
                    site:site,
                    target:target,
                    source:source,
                    _token: _token
                },
                success:function(response){
                    console.log(response);
                    // toastr.success('Success.')
                    Toast.fire({
                        icon: 'success',
                        title: 'Success'
                    });
                    $("#save-data-disable").addClass("hide");
                    $("#save-data").removeClass("hide");
                    if(response) {
                        // $('.success').text(response.success);
                        // $("#ajaxform")[0].reset();
                    }
                },
                error: function (request, status, error) {
                    // toastr.error('Failure.')
                    Toast.fire({
                        icon: 'error',
                        title: 'Error'
                    });
                    $("#save-data-disable").addClass("hide");
                    $("#save-data").removeClass("hide");
                    console.log('responseText: ', request.responseText);
                    console.log('error: ', error);
                }
            });

        }
    });
</script>
@endsection
