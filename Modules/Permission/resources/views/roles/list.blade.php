@extends('layouts/layoutMaster')

@section('title', tr_helper('contents', 'LIST') . ' - '. tr_helper('contents', 'ROLES'))

@section('vendor-style')
    <link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
    <link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css')}}"/>

@endsection

@section('vendor-script')
    <script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/cleavejs/cleave.js')}}"></script>
    <script src="{{asset('assets/vendor/libs/cleavejs/cleave-phone.js')}}"></script>
@endsection

@section('page-script')
    <script src="{{asset('assets/js/app-user-list.js')}}"></script>
@endsection

@section('content')
    @include('components.errors')
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{tr_helper('contents', 'ROLES')}} / </span> {{tr_helper('contents', 'LIST')}}
    </h4>
    <div class="" style="width: 10rem">
        <a href="{{ route('admin.panel.roles.create') }}"><button type="button" data-bs-toggle="modal" class="btn btn-primary mb-3 text-nowrap add-new-role">
            <i class="bx bx-plus"></i> {{ tr_helper('contents', 'ADD_NEW_ROLE') }} </button></a>
    </div>
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="datatables-users table border-top dataTable no-footer dtr-column" id="DataTables_Table_0"
                   aria-describedby="DataTables_Table_0_info" style="width: 1376px;">
                <thead>
                <tr>
                    <th class="control sorting_disabled dtr-hidden" rowspan="1" colspan="1"
                        style="width: 0px; display: none;" aria-label=""></th>
                    <th class="" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1"
                        colspan="1" style="width: 347px;" aria-label="User: activate to sort column ascending"
                        aria-sort="descending">{{ tr_helper('validation', 'attributes.title') }}
                    </th>
                    <th class="" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1"
                        style="width: 173px;" aria-label="Role: activate to sort column ascending">{{ tr_helper('validation', 'attributes.role_key') }}
                    </th>
                    <th class="" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1"
                        style="width: 113px;" aria-label="Status: activate to sort column ascending">{{ tr_helper('validation', 'attributes.full_access') }}
                    </th>
                    <th class="" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1"
                        style="width: 113px;" aria-label="Status: activate to sort column ascending">{{ tr_helper('validation', 'attributes.is_admin') }}
                    </th>
                    <th class="" rowspan="1" colspan="1" style="width: 140px;" aria-label="Actions">
                        {{ tr_helper('contents', 'ACTIONS') }}
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                    <tr class="odd">
                    <td class="  control" tabindex="0" style="display: none;"></td>
                    <td class="sorting_1">
                        <div class="d-flex flex-column">{{ $role->title }} </span></div>
                    </td>
                    <td><span class="text-truncate d-flex align-items-center"><span
                                    class="badge badge-center rounded-pill bg-label-primary w-px-30 h-px-30 me-2"><i
                                        class="bx bx-cog bx-xs"></i></span> {{ $role->role_key }} </span></td>

                    <td>
                        {{ $role->full_access ? tr_helper('contents', "HAS") : tr_helper('contents', 'HAS_NOT') }}
                    </td>
                    <td>
                        {{ $role->is_admin ? tr_helper('contents', 'IS') : tr_helper('contents', 'IS_NOT') }}
                    </td>
                    <td>
                        <div class="d-inline-block text-nowrap">
                            <a href="{{ route('admin.panel.roles.edit', $role->id) }}" class="btn btn-sm btn-icon"><i class="bx bx-edit text-info"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <!-- Offcanvas to add new user -->
    </div>

@endsection
