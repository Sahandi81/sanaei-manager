@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Create') . ' ' . tr_helper('contents', 'Transaction'))

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
						<form id="transactionForm" method="POST" action="{{ route('finance.transactions.store') }}" class="row g-3" data-dynamic-validation enctype="multipart/form-data">
							@csrf
							<input type="hidden" name="form" value="CREATE">

							<x-form.form-input
								name="client_id"
								type="select"
								:options="$clients->pluck('name', 'id')"
								required
							/>

							<x-form.form-input
								name="amount"
								type="number"
								required
								min="0"
								:validation="['numeric' => true]"
							/>

							<x-form.form-input
								name="currency"
								type="select"
								:options="['IRR' => 'IRR', 'USD' => 'USD']"
								required
							/>

							<x-form.form-input
								name="type"
								type="select"
								:options="['panel' => 'Panel', 'telegram' => 'Telegram']"
								required
							/>

							<x-form.form-input
								name="description"
								type="textarea"
							/>

							<div class="card">
								<h5 class="card-header"> {{ tr_helper('contents', 'Attachments') }} </h5>
								<div class="card-body">
									<x-file-upload
										name="files[]"
										:max-files="5"
										:max-size="10"
										accept="image/*"
									/>
								</div>
								<input type="hidden" name="uploaded_files" id="uploaded-files" value="">
							</div>

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
