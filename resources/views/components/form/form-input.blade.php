@props([
    'name',
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'defaultDisabled' => 'true',
    'value' => null,
    'required' => false,
    'helpText' => null,
    'options' => [],
    'default' => null,
    'col' => 'col-md-6',
    'validation' => [], // Custom validation rules
    'disabled' => false,
    'readonly' => false,
    'autocomplete' => null,
    'step' => null, // For number inputs
    'min' => null,
    'max' => null,
    'pattern' => null,
    'multiple' => false, // For file/select inputs
    'accept' => null, // For file inputs
])

@php
	// Automatic validation rules based on props
	$autoValidation = [];

	if ($required) {
		$autoValidation['notEmpty'] = true;
	}

	if ($type === 'email') {
		$autoValidation['emailAddress'] = true;
	}

	if ($type === 'url') {
		$autoValidation['uri'] = true;
	}

	if ($type === 'number') {
		$autoValidation['numeric'] = true;
	}

	if ($type === 'password' && isset($validation['confirm'])) {
		$autoValidation['identical'] = [
			'field' => $validation['confirm'],
			'message' => tr_helper('validation', 'attributes.confirmed', $name)
		];
	}
    $selectedValues = old($name, $value ?? []);
    if (!is_array($selectedValues)) {
        $selectedValues = [$selectedValues];
    }
	$validationRules = array_merge($autoValidation, $validation);
@endphp

<div class="{{ $col }}" data-field-container>
	{{-- Show label for all input types except standalone checkboxes/radios --}}
	@if(!in_array($type, ['checkbox', 'radio']) || ($type === 'select' && $multiple))
		<label class="form-label @if($type === 'select' && $multiple)d-block @endif" for="{{ $type === 'select' && $multiple ? '' : $name }}">
			{{ $label ?? tr_helper('validation', 'attributes.'.$name) }}
			@if($required) <span class="text-danger">*</span> @endif
		</label>
	@endif

	@if($type === 'select' && $multiple)
		{{-- Checkbox group for multiple select --}}
		<div class="border rounded p-3">
			<div class="row">
				@foreach($options as $key => $option)
					<div class="col-md-6 mb-2">
						<div class="form-check">
							<input type="checkbox"
								   id="{{ $name }}_{{ $key }}"
								   class="form-check-input"
								   name="{{ $name }}[]"
								   value="{{ $key }}"
								   @checked(in_array($key, $selectedValues))
								   @if($disabled) disabled @endif
								   @if($readonly) readonly @endif
								   data-validation-rules="{{ json_encode($validationRules) }}" />
							<label class="form-check-label" for="{{ $name }}_{{ $key }}">
								{{ $option }}
							</label>
						</div>
					</div>
				@endforeach
			</div>
		</div>
	@elseif($type === 'select')
		{{-- Regular single select --}}
		<select id="{{ $name }}"
				class="form-select"
				name="{{ $name }}"
				@if($disabled) disabled @endif
				@if($readonly) readonly @endif
				data-validation-rules="{{ json_encode($validationRules) }}"
				@if($autocomplete) autocomplete="{{ $autocomplete }}" @endif>
			@if($default)
				<option value="" {{ $defaultDisabled == 'true' ? 'disabled' : '' }} selected>{{ $default }}</option>
			@endif
			@foreach($options as $key => $option)
				<option value="{{ $key }}" @selected(in_array($key, $selectedValues))>
					{{ $option }}
				</option>
			@endforeach
		</select>
	@elseif($type === 'checkbox' || $type === 'radio')
		<div class="form-check">
			<input type="{{ $type }}"
				   id="{{ $name }}"
				   class="form-check-input"
				   name="{{ $name }}"
				   value="{{ $value ?? 1 }}"
				   @checked(old($name, $value))
				   @if($disabled) disabled @endif
				   @if($readonly) readonly @endif
				   data-validation-rules="{{ json_encode($validationRules) }}" />
			<label class="form-check-label" for="{{ $name }}">
				{{ $label ?? tr_helper('validation', 'attributes.'.$name) }}
			</label>
		</div>
	@elseif($type === 'textarea')
		<textarea id="{{ $name }}"
				  class="form-control"
				  name="{{ $name }}"
				  rows="{{ $rows ?? 3 }}"
				  @if($disabled) disabled @endif
				  @if($readonly) readonly @endif
				  @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
				  data-validation-rules="{{ json_encode($validationRules) }}">{{ old($name, $value) }}</textarea>
	@elseif($type === 'file')
		<input type="file"
			   id="{{ $name }}"
			   class="form-control"
			   name="{{ $name }}{{ $multiple ? '[]' : '' }}"
			   @if($multiple) multiple @endif
			   @if($disabled) disabled @endif
			   @if($readonly) readonly @endif
			   @if($accept) accept="{{ $accept }}" @endif
			   data-validation-rules="{{ json_encode($validationRules) }}" />
	@else
		<input type="{{ $type }}"
			   id="{{ $name }}"
			   class="form-control"
			   placeholder="{{ $placeholder ?? tr_helper('validation','input_placeholder.enter', $name) }}"
			   name="{{ $name }}"
			   value="{{ old($name, $value) }}"
			   @if($disabled) disabled @endif
			   @if($readonly) readonly @endif
			   @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
			   @if($step) step="{{ $step }}" @endif
			   @if($min) min="{{ $min }}" @endif
			   @if($max) max="{{ $max }}" @endif
			   @if($pattern) pattern="{{ $pattern }}" @endif
			   data-validation-rules="{{ json_encode($validationRules) }}" />
	@endif

	@if($helpText)
		<div class="form-text">{{ $helpText }}</div>
	@endif

	<div class="fv-plugins-message-container invalid-feedback ltr">
		<div class="fv-help-block"></div>
	</div>
</div>


