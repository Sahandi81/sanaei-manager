<?php

namespace Modules\Shop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
	private array $formTypes;

	const ORDER_CREATE = 'order_create';

	private function setFromTypes(): void
	{
		$this->formTypes = [
			self::ORDER_CREATE,
		];

		$this->formTypes = array_combine($this->formTypes, $this->formTypes); # =))
	}


	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize(): bool
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$this->setFromTypes();
		return match ($this->get('form')) {

			self::ORDER_CREATE => [
				'client_id' 	=> ['required','exists:clients,id'],
				'product_id' 	=> ['required','exists:products,id']
			],

			default => ['form' => ['required', Rule::in(implode(',', $this->formTypes))]],
		};

	}


}
