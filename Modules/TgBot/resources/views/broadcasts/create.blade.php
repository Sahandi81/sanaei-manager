{{-- Modules/TgBot/Resources/views/broadcasts/create.blade.php --}}
@extends('layouts/layoutMaster')
@php($customPageName = (tr_helper('contents', 'Create') ?? 'Create') . ' ' . (tr_helper('contents', 'Broadcast') ?? 'Broadcast'))

@section('title', $customPageName)

@section('vendor-style')
	@vite([
	  'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss',
	  'resources/assets/vendor/libs/select2/select2.scss',
	  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
	  'resources/assets/vendor/libs/@form-validation/form-validation.scss'
	])
@endsection

@section('vendor-script')
	@vite([
	  'resources/assets/vendor/libs/select2/select2.js',
	  'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js',
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
					<h5 class="card-header d-flex justify-content-between align-items-center">
						{{ $customPageName }}
					</h5>
					<div class="card-body">
						<form id="broadcastForm" method="POST" action="{{ route('tgbot.broadcasts.store') }}" class="row g-3" data-dynamic-validation>
							@csrf

							{{-- متن پیام --}}
							<x-form.form-input
								name="text"
								type="textarea"
								required
								col="col-12"
								:validation="['minLength' => 1]"
								placeholder="{{ tr_helper('contents','WriteYourMessage') ?? 'Write your message...' }}"
							/>

							{{-- حالت پارس --}}
							<x-form.form-input
								name="parse_mode"
								type="select"
								:options="['MarkdownV2' => 'MarkdownV2', 'HTML' => 'HTML', 'none' => 'None']"
								:selected="'MarkdownV2'"
							/>

							{{-- فیلتر مخاطبین --}}
							<x-form.form-input
								name="only"
								type="select"
								:options="[
                  'active' => (tr_helper('contents','Active') ?? 'Active'),
                  'all' => (tr_helper('contents','All') ?? 'All'),
                  'testless_active' => (tr_helper('contents','OnlyWithoutTestActive') ?? 'Only active users without test'),
                  'testless_all' => (tr_helper('contents','OnlyWithoutTestAll') ?? 'All users without test')
                ]"
								:selected="'active'"
							/>

							{{-- حالت ارسال: نرمال یا تست برای ادمین --}}
							<x-form.form-input
								name="delivery_mode"
								type="select"
								:options="[
                  'normal' => (tr_helper('contents','DeliveryNormal') ?? 'Send to recipients'),
                  'test_admin' => (tr_helper('contents','DeliveryTestAdmin') ?? 'Test: send only to admin')
                ]"
								:selected="'normal'"
							/>

							{{-- مارک‌آپ (JSON inline/reply keyboard) --}}
							<x-form.form-input
								name="markup"
								type="textarea"
								col="col-12"
								placeholder='{"inline_keyboard":[[{"text":"Website","url":"https://example.com"}]]]}'
							/>

							{{-- انتخاب مالک فقط برای ادمین --}}
							@if(\Illuminate\Support\Facades\Auth::user()->role->is_admin)
								<x-form.form-input
									name="user_id"
									type="select"
									:options="$users->pluck('name','id')"
									default="{{ tr_helper('contents','SelectUser') ?? 'Select User' }}"
								/>
							@endif

							<div class="col-12 d-flex gap-2">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button style="max-width: 10rem">
									<span class="btn-text">{{ tr_helper('contents','Send') ?? 'Send' }}</span>
									<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
								</button>

								<button type="button" id="fillInlineSample" class="btn btn-outline-secondary">
									<i class="bx bx-code-block"></i> {{ tr_helper('contents','InsertInlineKeyboardSample') ?? 'Insert Inline Keyboard Sample' }}
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		{{-- Helper Notes --}}
		<div class="row mt-3">
			<div class="col-12">
				<div class="alert alert-info" role="alert">
					<strong>{{ tr_helper('contents','Tips') ?? 'Tips' }}:</strong>
					<ul class="mb-0">
						<li>{{ tr_helper('contents','ParseModeTip') ?? 'For MarkdownV2, escape special characters properly.' }}</li>
						<li>{{ tr_helper('contents','RateLimitTip') ?? 'Large broadcasts respect Telegram rate limits automatically.' }}</li>
						<li>{{ tr_helper('contents','MarkupTip') ?? 'Leave markup empty if you do not need buttons.' }}</li>
					</ul>
				</div>
			</div>
		</div>

		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const form = document.getElementById('broadcastForm');
				const submitBtn = form.querySelector('[data-submit-button]');
				const spinner = submitBtn.querySelector('.spinner-border');
				const btnText = submitBtn.querySelector('.btn-text');
				const markupEl = form.querySelector('[name="markup"]');
				const parseEl = form.querySelector('[name="parse_mode"]');

				function setLoading(on) {
					if (on) {
						spinner.classList.remove('d-none');
						btnText.textContent = '{{ tr_helper('contents','Sending') ?? 'Sending...' }}';
						submitBtn.disabled = true;
					} else {
						spinner.classList.add('d-none');
						btnText.textContent = '{{ tr_helper('contents','Send') ?? 'Send' }}';
						submitBtn.disabled = false;
					}
				}

				form.addEventListener('submit', function (e) {
					// Validate JSON in markup if provided
					const raw = (markupEl?.value || '').trim();
					if (raw.length) {
						try {
							JSON.parse(raw);
						} catch (err) {
							e.preventDefault();
							if (typeof showToast === 'function') {
								showToast('warning', '{{ tr_helper('contents','InvalidJson') ?? 'Invalid markup JSON.' }}');
							} else {
								alert('{{ tr_helper('contents','InvalidJson') ?? 'Invalid markup JSON.' }}');
							}
							return false;
						}
					}
					setLoading(true);
				});

				// Quick insert inline keyboard sample
				document.getElementById('fillInlineSample')?.addEventListener('click', function () {
					const sample = {
						inline_keyboard: [
							[
								{ text: 'Website', url: 'https://example.com' },
								{ text: 'Contact', url: 'https://t.me/username' }
							]
						]
					};
					if (markupEl) {
						markupEl.value = JSON.stringify(sample);
					}
					if (parseEl && parseEl.value === 'none') {
						parseEl.value = 'MarkdownV2';
					}
				});
			});
		</script>
	</div>
@endsection
