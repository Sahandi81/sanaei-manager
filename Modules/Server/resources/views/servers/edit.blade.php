```php
@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Edit') . ' ' . tr_helper('contents', 'Server'))

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
	<script>
		document.addEventListener("DOMContentLoaded", function () {
			const testConnectionBtn = document.getElementById('TestConnection');

			testConnectionBtn.addEventListener('click', async function () {
				const serverId = "{{ $server->id }}";
				const spinner = testConnectionBtn.querySelector('.spinner-border');
				const btnText = testConnectionBtn.querySelector('.btn-text');
				const statusSelect = document.querySelector('select[name="status"]');

				// Start loading UI
				spinner.classList.remove('d-none');
				btnText.textContent = '{{ tr_helper("contents", "Checking") ?? "Checking..." }}';
				testConnectionBtn.disabled = true;

				try {
					const response = await fetch(`/api/v1/servers/${serverId}/test-connection`, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json',
							'Content-Type': 'application/json',
						},
						credentials: 'same-origin', // Important: sends session cookie for auth
					});

					const data = await response.json();

					if (response.ok) {
						showToast('success', data.msg);
					} else {
						showToast('warning', data.msg);
					}

					if (data.server_status !== undefined && statusSelect) {
						statusSelect.value = data.server_status;
						const event = new Event('change');
						statusSelect.dispatchEvent(event);
					}
				} catch (error) {
					showToast('danger', 'Something goes wrong. Call administrator. (t.me/Real_Sahandi81)');
					console.error("Network Error:", error);
				} finally {
					// Stop loading UI
					spinner.classList.add('d-none');
					btnText.textContent = '{{ tr_helper("contents", "CheckConnection") ?? "Check Connection" }}';
					testConnectionBtn.disabled = false;
				}
			});
		});
	</script>
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
						<form id="serverForm" class="row g-3" method="POST"
							  action="{{ route('servers.update', $server->id) }}"
							  data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="update_server">

							<!-- Server Basic Information -->
							<x-form.form-input
								name="name"
								:value="$server->name"
								required
								:validation="['minLength' => 3, 'maxLength' => 255]"
							/>

							<x-form.form-input
								name="ip"
								type="text"
								:value="$server->ip"
								:disabled="true"
								readonly
								helpText="The server's IP address or domain"
							/>

							<x-form.form-input
								name="location"
								type="text"
								:value="$server->location"
								readonly
								:disabled="true"
								helpText="Physical location of the server"
							/>

							@if(\Illuminate\Support\Facades\Auth::user()->role_key == 'super_admin')
								<x-form.form-input
									name="user_id"
									type="select"
									:value="$server->user_id"
									defaultDisabled="false"
									:options="$users"
									default="Select user"
								/>
							@endif

							<x-form.form-input
								name="panel_type"
								type="select"
								:value="$server->panel_type"
								:options="[
                                    'sanaei' => 'Sanaei',
                                ]"
								default="Select Panel Type"
								readonly
								:disabled="true"
							/>

							<x-form.form-input
								name="api_url"
								type="url"
								:value="$server->api_url"
								required
								helpText="Full URL to the API endpoint (e.g., https://example.com:56789/DFGFssdfRTHR)"
								:validation="['minLength' => 10]"
							/>

							<x-form.form-input
								name="username"
								type="text"
								:value="$server->username"
								required
								:validation="['minLength' => 3]"
							/>

							<x-form.form-input
								name="password"
								type="password"
								placeholder="******"
								helpText="Save password somewhere safe. You can't see it here anymore"
								:validation="['minLength' => 3]"
							/>

							<x-form.form-input
								name="status"
								type="select"
								:value="$server->status"
								:options="\Modules\Server\Models\Server::getStatuesRaw('*')"
								default="Select Status"
								required
							/>
							<div class="col-12">
								<div class="col-4 col-md-6 col-sm-12 d-flex justify-content-between flex-wrap btn-9rem">
									<button type="submit" class="btn btn-primary my-3 btn-9rem" data-submit-button>
										<span class="btn-text"> {{ tr_helper('contents', 'Save') }} </span>
										<span class="spinner-border spinner-border-sm d-none" role="status"
											  aria-hidden="true"></span>
									</button>

									<button type="button" class="btn btn-success my-3 btn-9rem" id="TestConnection">
										<span class="btn-text"> {{ tr_helper('contents', 'CheckConnection') }} </span>
										<span class="spinner-border spinner-border-sm d-none" role="status"
											  aria-hidden="true"></span>
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
```
