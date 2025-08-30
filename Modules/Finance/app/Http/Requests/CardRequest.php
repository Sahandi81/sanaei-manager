<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardRequest extends FormRequest
{
	private array $formTypes;

	public const CREATE = 'CREATE';
	public const UPDATE = 'UPDATE';

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

		$card = $this->route('card'); // برای UPDATE جهت ignore در unique

		return match ($this->get('form')) {
			self::CREATE => [
				'user_id'     => ['required', 'exists:users,id'],
				'card_number' => ['required', 'string', 'max:32', 'unique:cards,card_number'],
				'bank_name'   => ['required', 'string', 'max:255'],
				'owner_name'  => ['required', 'string', 'max:255'],
				'is_default'  => ['nullable', 'integer'], // یا: 'boolean'
			],
			self::UPDATE => [
				'user_id'     => ['sometimes', 'required', 'exists:users,id'],
				'card_number' => [
					'sometimes', 'required', 'string', 'max:32',
					Rule::unique('cards', 'card_number')->ignore($card?->id),
				],
				'bank_name'   => ['sometimes', 'required', 'string', 'max:255'],
				'owner_name'  => ['sometimes', 'required', 'string', 'max:255'],
				'is_default'  => ['sometimes', 'nullable', 'integer'], // یا: 'boolean'
			],
			default => [
				'form' => ['required', Rule::in($this->formTypes)],
			],
		};
	}
}
