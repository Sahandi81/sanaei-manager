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
			document.querySelectorAll('.toggle-inbound').forEach(checkbox => {
				checkbox.addEventListener('change', function() {
					const inboundId = this.dataset.inboundId;
					const isEnabled = this.checked;
					const spinner = document.createElement('span');
					spinner.className = 'spinner-border spinner-border-sm';

					// Replace checkbox with spinner during request
					this.parentNode.appendChild(spinner);
					this.style.display = 'none';
					const baseToggleUrl = `{{ route('api.v1.servers.inbounds.toggle', ['server' => $server->id, 'inbound' => 'INBOUND_ID']) }}`;
					const finalUrl = baseToggleUrl.replace('INBOUND_ID', inboundId);
					fetch(finalUrl, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json',
							'Content-Type': 'application/json',
						},
						body: JSON.stringify({
							enable: isEnabled
						}),
						credentials: 'same-origin',
					})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								showToast('success', data.message);
							} else {
								showToast('danger', data.message);
								// Revert checkbox state if failed
								this.checked = !isEnabled;
							}
						})
						.catch(error => {
							showToast('danger', 'Something went wrong');
							console.error("Error:", error);
							this.checked = !isEnabled;
						})
						.finally(() => {
							spinner.remove();
							this.style.display = 'block';
						});
				});
			});


			// اسکریپت برای دکمه TestConnection
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
					const response = await fetch(`{{ route('api.v1.servers.test_connection', $server->id) }}`, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json',
							'Content-Type': 'application/json',
						},
						credentials: 'same-origin',
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

			// اسکریپت برای دکمه SyncInbounds
			const SyncInbounds = document.getElementById('SyncInbounds');

			SyncInbounds.addEventListener('click', async function () {
				const serverId = "{{ $server->id }}";
				const spinner = SyncInbounds.querySelector('.spinner-border');
				const btnText = SyncInbounds.querySelector('.btn-text');
				const statusSelect = document.querySelector('select[name="status"]');

				// Start loading UI
				spinner.classList.remove('d-none');
				btnText.textContent = '{{ tr_helper('contents', 'Syncing') }}';
				SyncInbounds.disabled = true;

				try {
					const response = await fetch(`{{ route('api.v1.servers.sync_inbounds', $server->id) }}`, {
						method: 'POST',
						headers: {
							'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
							'Accept': 'application/json',
							'Content-Type': 'application/json',
						},
						credentials: 'same-origin',
					});
					const data = await response.json();

					if (response.ok) {
						showToast('success', data.msg);
						location.reload();
					} else {
						showToast('danger', data.msg);
					}
				} catch (error) {
					showToast('danger', 'Something goes wrong. Call administrator. (t.me/Real_Sahandi81)');
					console.error("Network Error:", error);
				} finally {
					spinner.classList.add('d-none');
					btnText.textContent = '{{ tr_helper('contents', 'SyncInbounds') ?? 'Sync Inbounds' }}';
					SyncInbounds.disabled = false;
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
											<option value="{{ $uId }}" @if(in_array($uId, $usersHasAccess)) selected @endif
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

								<div class="col-12">
									<button type="submit" class="btn btn-primary my-3 btn-9rem" data-submit-button>
										<span class="btn-text"> {{ tr_helper('contents', 'Save') }} </span>
										<span class="spinner-border spinner-border-sm d-none" role="status"
											  aria-hidden="true"></span>
									</button>
								</div>


								<div class="col-lg-6 col-md-8 col-12 d-flex justify-content-end flex-wrap float-end">

									<button type="button" class="btn btn-dark m-3 btn-15rem" id="TestConnection">
										<span class="btn-text"> <i class="bx bx-link me-2"></i> {{ tr_helper('contents', 'CheckConnection') }} </span>
										<span class="spinner-border spinner-border-sm d-none" role="status"
											  aria-hidden="true"></span>
									</button>

									<button type="button" class="btn btn-dark m-3 btn-15rem" id="SyncInbounds">
										<span class="btn-text"> <i class="bx bx-sync me-2"></i> {{ tr_helper('contents', 'SyncInbounds') ?? 'Sync Inbounds' }} </span>
										<span class="spinner-border spinner-border-sm d-none" role="status"
											  aria-hidden="true"></span>
									</button>

								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			@if (!empty($server->inbounds))
				<div class="card mt-4">
					<h5 class="card-header">{{ tr_helper('contents', 'AvailableInbounds') }}</h5>
					<div class="table-responsive text-nowrap">
						<table class="table">
							<thead>
							<tr>
								<th>#</th>
								<th>{{ tr_helper('validation', 'attributes.remark') }}</th>
								<th>{{ tr_helper('validation', 'attributes.port') }}</th>
								<th>{{ tr_helper('validation', 'attributes.protocol') }}</th>
								<th>{{ tr_helper('validation', 'attributes.stream') }}</th>
								<th>{{ tr_helper('validation', 'attributes.usage') }}</th>
								<th>{{ tr_helper('validation', 'attributes.status') }}</th>
							</tr>
							</thead>
							<tbody class="table-border-bottom-0">
							@foreach ($server->inbounds as $inbound)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td>{{ $inbound['remark'] }}</td>
									<td>{{ $inbound['port'] }}</td>
									<td>{{ strtoupper($inbound['protocol']) }}</td>
									<td>{{ $inbound['streamSettings']['network'] ?? '-' }}</td>
									<td>
										@php($up = $inbound['up'] ?? 0)
										@php($down = $inbound['down'] ?? 0)
										@php($usage = $up + $down)
										{{ formatBytes($usage) }}
									</td>
									<td>
										<div class="form-check form-switch">
											<input class="form-check-input toggle-inbound"
												   type="checkbox"
												   data-inbound-id="{{ $inbound['id'] }}"
												   @if($inbound['status'] ?? false) checked @endif>
										</div>
									</td>

								</tr>
							@endforeach
							</tbody>
						</table>
					</div>
				</div>
			@endif
		</div>
	</div>
@endsection

