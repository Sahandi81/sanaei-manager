@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'CREATE') . ' ' . tr_helper('contents', 'SERVER'))

@section('title', $customPageName)

<!-- Vendor Styles -->
@section('vendor-style')
	@vite([
	  'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss',
	  'resources/assets/vendor/libs/select2/select2.scss',
	  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
	  'resources/assets/vendor/libs/typeahead-js/typeahead.scss',
	  'resources/assets/vendor/libs/tagify/tagify.scss',
	  'resources/assets/vendor/libs/@form-validation/form-validation.scss'
	])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
	@vite([
	  'resources/assets/vendor/libs/select2/select2.js',
	  'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js',
	  'resources/assets/vendor/libs/moment/moment.js',
	  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
	  'resources/assets/vendor/libs/typeahead-js/typeahead.js',
	  'resources/assets/vendor/libs/tagify/tagify.js',
	  'resources/assets/vendor/libs/@form-validation/popular.js',
	  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
	  'resources/assets/vendor/libs/@form-validation/auto-focus.js'
	])
@endsection

<!-- Page Scripts -->
@section('page-script')
	@vite([
//		'resources/assets/js/form-validation.js',
	])
@endsection

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')
		<div class="row">
			<!-- FormValidation -->
			<div class="col-12">
				<div class="card">
					<h5 class="card-header">{{ $customPageName }}</h5>
					<div class="card-body">
						<form id="serverForm" class="row g-3" method="POST" action="{{ route('servers.store') }}"
							  data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="create_server">
							<!-- Server Basic Information -->

							<x-form.form-input
								name="name"
								required
								:validation="['minLength' => 3, 'maxLength' => 255]"
							/>

							<x-form.form-input
								name="ip"
								type="text"
								required
								helpText="The server's IP address or domain"
								:validation="['ip' => true]"
							/>

							<x-form.form-input
								name="location"
								type="text"
								helpText="Physical location of the server"
								:validation="['maxLength' => 100]"
							/>


							@if(\Illuminate\Support\Facades\Auth::user()->role_key == 'super_admin')
								<x-form.form-input
									name="user_id"
									type="select"
									defaultDisabled="false"
									:options="$users"
									default="Select user"
								/>
							@endif


							<x-form.form-input
								name="panel_type"
								type="select"
								:options="[
                                    'sanaei' => 'Sanaei',
                                ]"
								default="Select Panel Type"
								required
							/>


							<x-form.form-input
								name="api_url"
								type="url"
								required
								helpText="Full URL to the API endpoint (e.g., https://example.com:56789/DFGFssdfRTHR)"
								:validation="['minLength' => 10]"
							/>


							<x-form.form-input
								name="status"
								type="select"
								:options="\Modules\Server\Models\Server::getStatuesRaw()"
								default="Select Status"
								required
							/>
							<div class="col-12">
								<button type="submit" class="btn btn-primary" data-submit-button
										style="max-width: 10rem">
									<span class="btn-text"> {{ tr_helper('contents', 'Save') }} </span>
									<span class="spinner-border spinner-border-sm d-none" role="status"
										  aria-hidden="true"></span>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
