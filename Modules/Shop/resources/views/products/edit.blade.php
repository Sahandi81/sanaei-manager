@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Edit') . ' ' . tr_helper('contents', 'Product'))

@section('title', $customPageName)

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

@section('page-script')
	@vite([
//      'resources/assets/js/form-validation.js',
	])
@endsection

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')

		<div class="row">
			<div class="col-12">
				<div class="card">
					<h5 class="card-header">{{ $customPageName }}</h5>
					<div class="card-body">
						<form id="productForm" method="POST" action="{{ route('products.update', $product->id) }}" class="row g-3" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="UPDATE">

							<x-form.form-input
								name="name"
								required
								:value="$product->name"
								:validation="['minLength' => 3, 'maxLength' => 255]"
							/>

							@if(\Illuminate\Support\Facades\Auth::user()->role->is_admin)
								<x-form.form-input
									name="user_id"
									type="select"
									:options="$users->pluck('name', 'id')"
									:selected="$product->user_id"
									default="Select User"
								/>
							@endif

							<x-form.form-input
								name="traffic_gb"
								type="number"
								required
								min="0"
								:value="$product->traffic_gb"
								:validation="['numeric' => true]"
							/>

							<x-form.form-input
								name="duration_days"
								type="number"
								required
								step="0.1"
								min="0"
								:value="$product->duration_days"
								:validation="['numeric' => true]"
							/>

							<x-form.form-input
								name="price"
								type="number"
								required
								min="0"
								:value="$product->price"
								:validation="['numeric' => true]"
							/>

							<x-form.form-input
								name="user_limit"
								type="number"
								required
								min="1"
								:value="$product->user_limit"
								:validation="['numeric' => true]"
							/>

							<x-form.form-input
								name="is_active"
								type="select"
								:options="[1 => tr_helper('contents', 'Active'), 0 => tr_helper('contents', 'InActive')]"
								:selected="$product->is_active"
								required
							/>

							<x-form.form-input
								name="is_test"
								type="select"
								:options="[0 => tr_helper('contents', 'Main'), 1 => tr_helper('contents', 'Test')]"
								:selected="$product->is_test"
							/>

							<x-form.form-input
								name="parent_id"
								type="select"
								:options="$products->pluck('name', 'id')"
								:selected="$product->parent_id"
								default="Select Parent Product (if test)"
							/>

							<x-form.form-input
								name="servers"
								type="select"
								multiple
								:options="$servers->pluck('name', 'id')"
								:value="$product->servers->pluck('id')->toArray()"
								required
								col="col-12"
							/>

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button style="max-width: 10rem">
									<span class="btn-text">{{ tr_helper('contents', 'Update') }}</span>
									<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

	</div>
@endsection
