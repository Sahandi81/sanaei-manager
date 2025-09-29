@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Create') . ' ' . tr_helper('contents', 'Server'))

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
	  // 'resources/assets/js/form-validation.js',
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
						<form id="serverForm" class="row g-3" method="POST" action="{{ route('servers.store') }}" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="create_server">

							{{-- Basic fields --}}
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

							{{-- === NEW: انتخاب کاربران (many-to-many) === --}}
							@php ($isAdmin = in_array(\Illuminate\Support\Facades\Auth::user()->role_key, ['super_admin', 'manager']))

							@if($isAdmin)
								{{-- اگر x-form.form-input شما multiple را پشتیبانی نمی‌کند، از select خام زیر استفاده کن --}}
								<div class="col-12">
									<label for="server-users-select" class="form-label">
										{{ tr_helper('contents','Users who can access this server') }}
									</label>
									<select id="server-users-select"
											name="user_ids[]"
											class="form-select"
											multiple
											data-placeholder="{{ tr_helper('contents','Select users') }}">
										@foreach(($users ?? []) as $uId => $uName)
											<option value="{{ $uId }}"
												@selected(collect(old('user_ids', []))->contains($uId))>
												{{ $uName }}
											</option>
										@endforeach
									</select>
									<small class="text-muted d-block mt-1">
										برای انتخاب چند نفر از Ctrl/Cmd استفاده کن (یا سرچ Select2).
									</small>
									@error('user_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
									@error('user_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
								</div>
							@else
								{{-- برای نقش‌های غیر ادمین: فقط خودش --}}
								<input type="hidden" name="user_ids[]" value="{{ auth()->id() }}">
							@endif
							{{-- === /NEW === --}}

							<x-form.form-input
								name="panel_type"
								type="select"
								:options="['sanaei' => 'Sanaei']"
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
								name="username"
								type="text"
								required
								:validation="['minLength' => 3]"
							/>

							<x-form.form-input
								name="password"
								type="password"
								helpText="Save password somewhere safe. You can't see it here anymore"
								required
								:validation="['minLength' => 3]"
							/>

							<x-form.form-input
								name="status"
								type="select"
								:options="\Modules\Server\Models\Server::getStatuesRaw()"
								default="Select Status"
								required
							/>

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button style="max-width: 10rem">
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

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// اگر select2 لود شد، مالتی‌سلکت را زیباتر کن
			if (window.$ && $.fn.select2) {
				const $el = $('#server-users-select');
				if ($el.length) {
					$el.select2({
						width: '100%',
						placeholder: $el.data('placeholder') || '{{ __("Select users") }}'
					});
				}
			}
		});
	</script>
@endpush
