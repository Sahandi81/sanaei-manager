@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Edit') . ' ' . tr_helper('contents', 'User'))

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
							  action="{{ route('panel.users.update', $user->id) }}"
							  class="row g-3" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="UPDATE">

							<x-form.form-input
								name="name"
								:value="$user->name"
								required
								:validation="['minLength' => 3, 'maxLength' => 255]"
							/>

							<x-form.form-input
								name="email"
								type="email"
								:value="$user->email"
								required
								:validation="['maxLength' => 255]"
							/>

							<x-form.form-input
								name="password"
								type="password"
								placeholder="{{ tr_helper('contents', 'LeaveBlankIfNoChange') }}"
							/>

							<x-form.form-input
								name="role_key"
								type="select"
								:options="$roles->pluck('title', 'role_key')"
								:value="$user->role_key"
								required
							/>

							{{-- اختیاری: فیلدهای تلگرام طبق مدل User --}}
							<x-form.form-input
								name="telegram_bot_token"
								:value="$user->telegram_bot_token"
							/>
							<x-form.form-input
								name="telegram_webhook"
								:value="$user->telegram_webhook"
							/>

							<x-form.form-input
								name="status"
								type="select"
								:options="[1 => tr_helper('contents','Active'), 0 => tr_helper('contents','InActive')]"
								:value="$user->status"
								required
							/>

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button>
									<span class="btn-text">{{ tr_helper('contents', 'Update') }}</span>
									<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
								</button>
							</div>
						</form>
					</div>
				</div>

				{{-- برای کاربران، بخش‌های خرید سرویس و تاریخچه سفارش‌ها وجود ندارد --}}
			</div>
		</div>
	</div>
@endsection
