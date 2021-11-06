@extends('main')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ route('Dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active">Archives Of Materials</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive">
                            <table id="datatable" class="table table-bordered table-striped">
                                <thead class="text-center">
                                <tr>
                                    <th class="text-center">ID NO</th>
                                    <th class="text-center">ACC NUM</th>
                                    <th class="text-center">ISBN</th>
                                    <th class="text-center">TITLE</th>
                                    <th class="text-center">TYPE</th>
                                    <th class="text-center">ACTION</th>
                                </tr>
                                </thead>
                                <tbody class="text-center">

                                </tbody>
                            </table>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function(){
            $('.select2').select2({
                theme: "classic",
                width: "resolve"
            });

            $('#modal').on('hidden.bs.modal', function (e) {
                $('.form-control').removeClass('is-invalid');
                $('.modal-body .form-group')
                    .find("input,textarea,select")
                    .val('')
                    .end()
                    .find("input[type=checkbox], input[type=radio]")
                    .prop("checked", "")
                    .end();
            })

            $('.btn-add').on('click', function(){
                $('.for_edit').attr("hidden", false);
                $('.add_mask').addClass("col-md-6").removeClass("col-md-12");
            });

            var table = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                bjQueryUI: true,
                ajax : {
                    url : "{{ route('Materials_List_Datatables') }}",
                    type : "GET",
                    dataType: 'JSON'
                },
                columns: [
                    {data: 'materials_id', name: 'materials_id'},
                    {data: 'accnum', name: 'accnum'},
                    {data: 'isbn', name: 'isbn'},
                    {data: 'title', name: 'title'},
                    {data: 'material_type', name: 'material_type'},
                    {data: 'action', name: 'action'},

                ],
                dom: 'Bfrtip',
                responsive: true,  "autoWidth": false,
                buttons: ["csv", "excel", "pdf", "print"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            $(document).on('click' , '.data-edit' , function(){
                var id = $(this).attr("data-id");
                var type = $(this).attr("data-type");
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to restore this data!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Restore it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type:"get",
                            url: '{{ route('Archive_Restore') }}' ,
                            data: {
                                '_token': $('input[name=_token]').val(),
                                'id' : id,
                                'type' : type
                            }, // get all form field value in serialize form
                            success: function(response){
                                /*swal.fire("Sorry this function currently not working");*/
                                if(response.status == "success"){
                                    Swal.fire(
                                        'Deleted!',
                                        'Your Data has been restore.',
                                        'success'
                                    ).then(function(){
                                        location.reload();
                                    });
                                }else{
                                    swal.fire("Something is error please contact developer", "","error");
                                }
                            }
                        });

                    }else{
                        Swal.fire(
                            'Data is not restored!',
                            '',
                            'info'
                        )
                    }
                })
            });
        })
    </script>
@endsection
