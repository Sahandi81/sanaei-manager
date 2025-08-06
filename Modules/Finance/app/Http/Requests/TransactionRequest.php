<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
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
				'client_id' => ['required', 'exists:clients,id'],
				'amount' => ['required', 'numeric', 'min:0'],
				'currency' => ['required', 'string', 'size:3'],
				'description' => ['nullable', 'string', 'max:500'],
				'card_id' => ['nullable', 'exists:cards,id'],
				'type' => ['required', Rule::in(['panel', 'telegram'])],
			],
			self::UPDATE => [
				'status' => ['required', 'numeric', Rule::in([0, 1, 2])],
				'rejection_reason' => ['required_if:status,2', 'nullable', 'string', 'max:500'],
			],
			default => [
				'form' => ['required', Rule::in($this->formTypes)]
			],
		};
	}
}
