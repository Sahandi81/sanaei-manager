<!DOCTYPE html>
@php
$menuFixed = ($configData['layout'] === 'vertical') ? ($menuFixed ?? '') : (($configData['layout'] === 'front') ? '' : $configData['headerType']);
$navbarType = ($configData['layout'] === 'vertical') ? ($configData['navbarType'] ?? '') : (($configData['layout'] === 'front') ? 'layout-navbar-fixed': '');
$isFront = ($isFront ?? '') == true ? 'Front' : '';
$contentLayout = (isset($container) ? (($container === 'container-xxl') ? "layout-compact" : "layout-wide") : "");
@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}" class="{{ $configData['style'] }}-style {{($contentLayout ?? '')}} {{ ($navbarType ?? '') }} {{ ($menuFixed ?? '') }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}" dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{url('/')}}" data-framework="laravel" data-template="{{ $configData['layout'] . '-menu-' . $configData['theme'] . '-' . $configData['styleOpt'] }}">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>@yield('title') |
    {{ config('variables.templateName') ? config('variables.templateName') : 'TemplateName' }} -
    {{ config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix' }}
  </title>
  <meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />



  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)
	<style>
		.demo {
			text-transform: none!important;
		}
		.fv-plugins-bootstrap5:not(.fv-plugins-bootstrap5-form-inline) label~.fv-plugins-icon{
			display: none;
		}
		.fv-plugins-message-container{
			direction: ltr !important;
		}
	</style>
	<script>

		document.addEventListener('DOMContentLoaded', function() {

			// Track initialized forms to prevent duplicate initialization
			const initializedForms = new WeakSet();

			// Initialize all forms with data-dynamic-validation attribute
			document.querySelectorAll('form[data-dynamic-validation]').forEach(form => {
				if (!initializedForms.has(form)) {
					initDynamicFormValidation(form);
					initializedForms.add(form);
				}
			});

		});

		function initDynamicFormValidation(form) {
			const fields = {};
			const validationConfig = {
				plugins: {
					trigger: new FormValidation.plugins.Trigger({
						event: '', // 👈 disables real-time validation
					}),
					bootstrap5: new FormValidation.plugins.Bootstrap5({
						rowSelector: '.row',
						rowInvalidClass: 'fv-plugins-bootstrap5-row-invalid',
						invalidClass: 'is-invalid',
						validClass: 'is-valid',
					}),
					submitButton: new FormValidation.plugins.SubmitButton(),
					icon: new FormValidation.plugins.Icon({
						valid: 'fa fa-check',
						invalid: 'fa fa-times',
						validating: 'fa fa-refresh'
					}),
				},
			};

			// Process all fields with validation rules
			form.querySelectorAll('[data-validation-rules]').forEach(field => {
				const fieldName = field.name;

				try {
					const rules = JSON.parse(field.getAttribute('data-validation-rules'));

					if (Object.keys(rules).length > 0) {
						fields[fieldName] = {
							validators: {}
						};
						// Convert rules to FormValidation validators
						Object.entries(rules).forEach(([rule, config]) => {
							if (rule === 'notEmpty') {
								fields[fieldName].validators.notEmpty = {
									message: getValidationMessage(fieldName, 'required')
								};
								hasError = true
							}
							else if (rule === 'emailAddress') {
								fields[fieldName].validators.emailAddress = {
									message: getValidationMessage(fieldName, 'email')
								};
							}
							else if (rule === 'uri') {
								fields[fieldName].validators.uri = {
									message: getValidationMessage(fieldName, 'url')
								};
							}
							else if (rule === 'numeric') {
								fields[fieldName].validators.numeric = {
									message: getValidationMessage(fieldName, 'numeric')
								};
							}
							else if (rule === 'ip') {
								fields[fieldName].validators.ip = {
									message: getValidationMessage(fieldName, 'ip'),
									ipv4: true,
									ipv6: false
								};
							}
							else if (rule === 'identical') {
								fields[fieldName].validators.identical = {
									compare: config.field,
									message: config.message || getValidationMessage(fieldName, 'confirmed')
								};
							}
							else if (rule === 'string') {
								fields[fieldName].validators.string = {
									message: getValidationMessage(fieldName, 'string')
								};
							}
							else if (rule === 'minLength') {
								fields[fieldName].validators.stringLength = {
									min: config,
									message: getValidationMessage(fieldName, 'min.string', { min: config })
								};
							}
							else if (rule === 'maxLength') {
								fields[fieldName].validators.stringLength = {
									max: config,
									message: getValidationMessage(fieldName, 'max.string', { max: config })
								};
							}
						});
					}
				} catch (e) {
					console.error(`Error parsing validation rules for field ${fieldName}:`, e);
				}
			});

			// Only initialize if we have validation rules
			if (Object.keys(fields).length > 0) {
				validationConfig.fields = fields;

				// Clean up any existing validation instances
				if (form.fv) {
					form.fv.destroy();
				}

				// Initialize new validation
				form.fv = FormValidation.formValidation(form, validationConfig);

				const submitButton = form.querySelector('[data-submit-button]');
				if (submitButton) {
					submitButton.addEventListener('click', function(e) {
						e.preventDefault();

						form.fv.validate().then(function(status) {
							if (status === 'Valid') {
								// Show loading state
								submitButton.disabled = true;
								submitButton.querySelector('.btn-text').classList.add('d-none');
								submitButton.querySelector('.spinner-border').classList.remove('d-none');

								form.fv.destroy(); // Optional cleanup
								form.submit();     // Native submit
							} else {
								const invalidFields = form.querySelectorAll('.is-invalid');
								if (invalidFields.length > 0) {
									invalidFields[0].focus();
								}
							}
						});
					});
				}

			}
		}

		function getValidationMessage(field, rule, replacements = {}) {
			// Use your translation helper or fallback to default messages
			if (typeof window.tr === 'function') {
				return window.tr(`validation.${rule}`, {
					attribute: window.tr(`validation.attributes.${field}`),
					...replacements
				});
			}

			// Fallback English messages
			const messages = {
				required: `The ${field} field is required.`,
				email: `The ${field} must be a valid email address.`,
				url: `The ${field} must be a valid URL.`,
				ip: `The ${field} must be a valid IP address.`,
				numeric: `The ${field} must be a number.`,
				confirmed: `The ${field} confirmation does not match.`,
				string: `The ${field} must be a string.`,
				'min.string': `The ${field} must be at least ${replacements.min} characters.`,
				'max.string': `The ${field} may not be greater than ${replacements.max} characters.`
			};

			return messages[rule] || `Validation failed for ${field}.`;
		}
	</script>

</head>

<body>


  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->



  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)

</body>

</html>
