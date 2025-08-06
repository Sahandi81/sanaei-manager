@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Edit') . ' ' . tr_helper('contents', 'Transaction'))

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
						<form id="transactionForm" method="POST"
							  action="{{ route('finance.transactions.update', $transaction->id) }}" class="row g-3"
							  data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="UPDATE">

							<!-- Disabled Fields Section -->
							<div class="col-md-6">
								<div class="mb-3">
									<label
										class="form-label">{{ tr_helper('validation', 'attributes.client_id') }}</label>
									<input type="text" class="form-control" value="{{ $transaction->client->name }}"
										   disabled>
								</div>
							</div>

							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">{{ tr_helper('validation', 'attributes.amount') }}</label>
									<div class="input-group">
										<input type="text" class="form-control"
											   value="{{ number_format($transaction->amount) }}" disabled>
										<span class="input-group-text">{{ $transaction->currency }}</span>
									</div>
								</div>
							</div>

							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">{{ tr_helper('validation', 'attributes.type') }}</label>
									<input type="text" class="form-control" value="{{ ucfirst($transaction->type) }}"
										   disabled>
								</div>
							</div>

							<div class="col-md-6">
								<div class="mb-3">
									<label
										class="form-label">{{ tr_helper('validation', 'attributes.verified_at') }}</label>
									<input type="text" class="form-control"
										   value="{{ $transaction->verified_at }}" disabled>
								</div>
							</div>

							<div class="col-md-6">
								<div class="mb-3">
									<label
										class="form-label">{{ tr_helper('validation', 'attributes.modified_by') }}</label>
									<input type="text" class="form-control"
										   value="{{ $transaction->modifier?->id }} - {{ $transaction->modifier?->name }}" disabled>
								</div>
							</div>

							<div class="col-12">
								<div class="mb-3">
									<label
										class="form-label">{{ tr_helper('validation', 'attributes.description') }}</label>
									<textarea class="form-control" disabled>{{ $transaction->description }}</textarea>
								</div>
							</div>

							<!-- Editable Fields -->
							<div class="col-md-6">
								<x-form.form-input
									name="status"
									type="select"
									:options="\Modules\Finance\Models\Transaction::getStatusesRaw()"
									:value="$transaction->status"
									required
								/>
							</div>

							<div class="col-md-6">
								<x-form.form-input
									name="rejection_reason"
									type="textarea"
									:value="$transaction->rejection_reason"
									:required="$transaction->status == \Modules\Finance\Models\Transaction::STATUS_REJECTED"
								/>
							</div>

							{{-- Show existing attachments --}}
							@if($transaction->files->isNotEmpty())
								<div class="card mb-4">
									<h5 class="card-header">{{ tr_helper('contents', 'ExistingAttachments') }}</h5>
									<div class="card-body">
										<div class="row">
											@foreach($transaction->files as $file)
												<div class="mb-3">
													<div class="border rounded p-2 position-relative text-center">
														@if(str_starts_with($file->mime_type, 'image/'))
															<img src="{{ $file->url }}" class="img-fluid rounded mb-2"
																 alt="{{ $file->original_name }}"
																 style="max-height: 500px;">
														@else
															<i class="ti ti-file-text fs-2 mb-2"></i>
														@endif
														<small
															class="d-block text-truncate">{{ $file->original_name }}</small>
{{--														<button type="button"--}}
{{--																class="btn-close position-absolute top-0 end-0 m-1"--}}
{{--																aria-label="Remove"--}}
{{--																onclick="deleteFile({{ $file->id }}, this)"></button>--}}
													</div>
												</div>
											@endforeach
										</div>
									</div>
								</div>
							@endif
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
									<span class="btn-text">{{ tr_helper('contents', 'Update') }}</span>
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
