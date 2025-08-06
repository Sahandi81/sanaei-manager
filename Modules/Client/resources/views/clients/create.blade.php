@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Create') . ' ' . tr_helper('contents', 'Client'))

@section('title', $customPageName)

@section('vendor-style')
	@vite([
		'resources/assets/vendor/libs/select2/select2.scss',
		'resources/assets/vendor/libs/@form-validation/form-validation.scss'
	])
@endsection

@section('vendor-script')
	@vite([
		'resources/assets/vendor/libs/select2/select2.js',
		'resources/assets/vendor/libs/@form-validation/popular.js',
		'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
		'resources/assets/vendor/libs/@form-validation/auto-focus.js'
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
						<form id="clientForm" method="POST" action="{{ route('clients.store') }}" class="row g-3" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="CREATE">

							@if(auth()->user()->role->is_admin)
								<x-form.form-input
									name="user_id"
									type="select"
									:options="$users->pluck('name', 'id')"
									required
								/>
							@endif

							<x-form.form-input
								name="name"
								required
								:validation="['minLength' => 3, 'maxLength' => 255]"
							/>

							<x-form.form-input
								name="telegram_id"
								type="number"
							/>

							<x-form.form-input
								name="type"
								type="select"
								:options="['telegram' => 'Telegram', 'panel' => 'Panel']"
								:value="'panel'"
								disabled="true"
								required
							/>

							<x-form.form-input
								name="desc"
								type="textarea"
							/>

							<x-form.form-input
								name="status"
								type="select"
								:options="[1 => tr_helper('contents', 'Active'), 0 => tr_helper('contents','InActive')]"
								required
							/>

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button>
									<span class="btn-text">{{ tr_helper('contents', 'Save') }}</span>
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
