@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Create') . ' ' . tr_helper('contents', 'User'))

@section('title', $customPageName)

@section('vendor-style')
	@vite([
		'resources/assets/vendor/libs/select2/select2.scss',
		'resources/assets/vendor/libs/@form-validation/form-validation.scss',
		'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'
	])
@endsection

@section('vendor-script')
	@vite([
		'resources/assets/vendor/libs/select2/select2.js',
		'resources/assets/vendor/libs/@form-validation/popular.js',
		'resources/assets/vendor/libs/@form-validation/auto-focus.js',
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
						<form id="userForm" method="POST"
							  action="{{ route('panel.users.store') }}"
							  class="row g-3" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="CREATE">

							<x-form.form-input
								name="name"
								required
								:validation="['minLength' => 3, 'maxLength' => 255]"
								:value="old('name')"
							/>

							<x-form.form-input
								name="email"
								type="email"
								required
								:validation="['maxLength' => 255]"
								:value="old('email')"
							/>

							<x-form.form-input
								name="password"
								type="password"
								required
							/>


							<x-form.form-input
								name="role_key"
								type="select"
								:options="$roles->pluck('title', 'role_key')"
								:value="old('role_key')"
								required
							/>

							<x-form.form-input
								name="status"
								type="select"
								:options="[1 => tr_helper('contents','Active'), 0 => tr_helper('contents','InActive')]"
								:value="old('status', 1)"
								required
							/>
							@if(\Illuminate\Support\Facades\Auth::user()->role->full_access)
								<x-form.form-input
									name="parent_id"
									type="select"
									:options="\App\Models\User::query()->whereHas('role', function ($q)
										{
											$q->where('is_admin', 1);
										}
									)->pluck('email','id')"
									:value="old('parent_id', 1)"
								/>
							@else
								<input type="hidden" name="parent_id" value="{{ \Illuminate\Support\Facades\Auth::id() }}">
							@endif

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button>
									<span class="btn-text">{{ tr_helper('contents', 'Save') }}</span>
									<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
								</button>
							</div>
						</form>
					</div>
				</div>

				{{-- در صفحه ایجاد، بخش‌های سفارش/تاریخچه وجود ندارد --}}
			</div>
		</div>
	</div>
@endsection
