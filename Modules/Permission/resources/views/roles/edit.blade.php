@extends('layouts/layoutMaster')

@section('title', tr_helper('contents', 'UPDATE') . ' ' . tr_helper('contents', 'ROLE'))

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css')}}">
<link rel="stylesheet" href="{{asset('assets/vendor/libs/animate-css/animate.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/select2/select2.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css')}}" />
@endsection

@section('page-style')
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-user-view.css')}}" />
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/moment/moment.js')}}"></script>
<script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
<script src="{{asset('assets/vendor/libs/sweetalert2/sweetalert2.js')}}"></script>
<script src="{{asset('assets/vendor/libs/cleavejs/cleave.js')}}"></script>
<script src="{{asset('assets/vendor/libs/cleavejs/cleave-phone.js')}}"></script>
<script src="{{asset('assets/vendor/libs/select2/select2.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js')}}"></script>
@endsection

@section('page-script')
<script src="{{asset('assets/js/modal-edit-user.js')}}"></script>
<script src="{{asset('assets/js/app-user-view.js')}}"></script>
<script src="{{asset('assets/js/app-user-view-account.js')}}"></script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">{{tr_helper('contents', 'ROLES')}} / {{tr_helper('contents', 'UPDATE')}} /</span>
  {{ $role['title'] }}
</h4>
@include('components.errors')
<div class="row">
  <!-- User Sidebar -->
  <div class="col-xl-12 col-lg-12 col-md-12 order-0 order-md-1">
    <form action="{{ route('admin.panel.roles.update', $role['id']) }}" method="post">
      @csrf
      <input type="hidden" name="form" value="admin_update_role">
      <div class="col-md-12">
      <div class="card mb-4">
        <h5 class="card-header">{{ tr_helper('contents', 'UPDATE') }} {{ tr_helper('contents', 'ROLE_DETAILS')  }}</h5>
        <div class="card-body demo-vertical-spacing demo-only-element">

          <div class="form-password-toggle">
            <label class="form-label"
                   for="basic-default-password12">{{ tr_helper('validation', 'attributes.title') }}</label>
            <div class="input-group">
              <span class="input-group-text" id="basic-addon11"><i class="bx bx-user"></i></span>
              <input type="text" required name="title" value="{{ $role['title'] }}" class="form-control" placeholder="{{ tr_helper('validation', 'attributes.title') }}" aria-label="Username" aria-describedby="basic-addon11">
            </div>
          </div>

          <div class="form-password-toggle">
            <label class="form-label"
                   for="basic-default-password12">{{ tr_helper('validation', 'attributes.role_key') }}</label>
            <div class="input-group">
              <div class="input-group">
                <span class="input-group-text" id="basic-addon11"><i class="bx bx-key"></i></span>
                <input type="text" name="title" value="{{ $role['role_key'] }}" disabled class="form-control" placeholder="{{ tr_helper('validation', 'attributes.role_key') }}" aria-label="Username" aria-describedby="basic-addon11">
              </div>
            </div>
          </div>

          <div class="form-password-toggle">
            <label class="form-label" for="FilterTransaction">{{ tr_helper('validation', 'attributes.full_access') }}</label>
            <div class="input-group">
              <select id="FilterTransaction" name="full_access" class="form-select text-capitalize">
                @foreach(['HAS_NOT', 'HAS'] as $index => $item)
                  <option value="{{ $index }}" {{ ($index == $role['full_access'] ? 'selected' : '') }} class="text-capitalize">
                    {{ tr_helper('contents', $item) }}</option>
                @endforeach
              </select>
            </div>
          </div>

           <div class="form-password-toggle">
            <label class="form-label" for="FilterTransaction">{{ tr_helper('validation', 'attributes.is_admin') }}</label>
            <div class="input-group">
              <select id="UserRole" name="is_admin" class="form-select text-capitalize">
                @foreach(['IS_NOT','IS'] as $index => $item)
                  <option value="{{ $index }}" {{ ($index == $role['is_admin'] ? 'selected' : '') }} class="text-capitalize">
                    {{ tr_helper('contents', $item) }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-12">
            <button type="submit"
                    class="btn btn-primary"> {{ tr_helper('contents', 'UPDATE') }} </button>
          </div>
        </div>
      </div>
    </div>
    </form>
  </div>
  <!--/ User Content -->

</div>
  <!-- Permission Content -->
<div class="card mb-4">
  <h5 class="card-header"> {{ tr_helper('contents', 'ROLE_PERMISSIONS') }} </h5>
  <form class="card-body" action="{{ route('admin.panel.roles.permissions.sync.permission', $role['id']) }}" method="POST">
    @csrf
    <input type="hidden" name="form" value="{{ \Modules\Permission\App\Http\Requests\PermissionRequest::ADMIN_SYNC_PERMISSION }}">
    @foreach($permissions as $index => $permission)
      <h6> - {{ $index }}</h6>
      <div class="row">
        @foreach($permission as $key => $route)
          @php
              if ($role['full_access'] == 1){
                  $checked = true;
              }else{
				  if (isset($rolePermission[$route['id']])){
                    $checked = true;
				  }else{
				    $checked = false;
				  }
              }
          @endphp
          <div class="col-md col-lg-6 col-md-6 col-sm-12 mb-md-0 my-2">
            <div class="form-check col-md-12 col-sm-12 col-lg-12 custom-option ltr custom-option-basic {{ $checked ? 'checked' : '' }}" style="">
              <label class="form-check-label custom-option-content" for="customRadioTemp{{$index}}{{$key}}" style="text-align:left; direction: ltr">
                <input name="permission[{{ $route['id'] }}]" class="form-check-input" type="checkbox" id="customRadioTemp{{$index}}{{$key}}" {{ $checked ? 'checked' : '' }}>
                <span class="custom-option-header">
                  <span class="h6 mb-0" style="max-width: 95%">{{ $route['title'] }}</span>
                  <span></span>
                </span>
                <span class="custom-option-body">
                  <small> {{ $route['route_name'] }} </small>
                </span>
              </label>
            </div>
          </div>
        @endforeach
        <hr class="my-5">
      </div>
      @endforeach
      <div class="pt-4">
        <button type="submit" class="btn btn-primary me-sm-3 me-1"> {{ tr_helper('contents', 'SEND') }} </button>
      </div>
  </form>
</div>
  <!--/ Permission Content -->


<!-- Modal -->
@include('_partials/_modals/modal-edit-user')
@include('_partials/_modals/modal-upgrade-plan')
<!-- /Modal -->
@endsection
