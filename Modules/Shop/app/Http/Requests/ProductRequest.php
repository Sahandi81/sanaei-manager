<?php

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
	private array $formTypes;

	const CREATE = 'CREATE';
	const UPDATE = 'UPDATE';

	private function setFormTypes(): void
	{
		$this->formTypes = [
			self::CREATE,
			self::UPDATE,
		];

		$this->formTypes = array_combine($this->formTypes, $this->formTypes);
	}

	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$this->setFormTypes();

		return match ($this->get('form')) {
			self::CREATE => [
				'name' 					=> ['required', 'string'],
				'traffic_gb' 			=> ['required', 'numeric', 'min:0'],
				'duration_days' 		=> ['required', 'numeric', 'min:0'],
				'price' 				=> ['required', 'numeric', 'min:0'],
				'user_limit' 			=> ['required', 'integer', 'min:1'],
				'user_id'	 			=> ['required', 'integer', 'min:1'],
				'is_active' 			=> ['required', 'boolean'],
				'is_test' 				=> ['nullable', 'boolean'],
				'parent_id' 			=> ['nullable', 'exists:products,id'],
				'servers' 				=> ['required', 'array', 'min:1'],
				'servers.*' 			=> ['exists:servers,id'],
			],

			self::UPDATE => [
				'name' 					=> ['sometimes', 'required', 'string'],
				'price' 				=> ['sometimes', 'required', 'numeric', 'min:0'],
				'user_limit' 			=> ['sometimes', 'required', 'integer', 'min:1'],
				'is_active' 			=> ['sometimes', 'required', 'boolean'],
				'servers' 				=> ['required', 'array', 'min:1'],
				'servers.*' 			=> ['exists:servers,id'],
			],

			default => [
				'form' => ['required', Rule::in(array_keys($this->formTypes))],
			],
		};
	}
}
