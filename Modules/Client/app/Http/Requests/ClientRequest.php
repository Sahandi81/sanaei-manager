<?php

namespace Modules\Client\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
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
				'user_id' 			=> ['required', 'exists:users,id'],
				'name' 				=> ['required', 'string', 'max:255'],
				'telegram_id' 		=> ['nullable', 'numeric'],
				'desc' 				=> ['nullable', 'string'],
				'status' 			=> ['required', 'integer'],
			],
			self::UPDATE => [
				'name' 				=> ['sometimes', 'required', 'string', 'max:255'],
				'telegram_id' 		=> ['sometimes', 'nullable', 'numeric'],
				'desc' 				=> ['sometimes', 'nullable', 'string'],
				'status' 			=> ['sometimes', 'required', 'integer'],
			],
			default => [
				'form' => ['required', Rule::in($this->formTypes)]
			],
		};
	}
}
