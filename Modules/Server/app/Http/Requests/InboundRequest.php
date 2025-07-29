<?php

namespace Modules\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InboundRequest extends FormRequest
{
	private array $formTypes;

	const EXAMPLE = 'EXAMPLE';

	private function setFromTypes()
	{
		$this->formTypes = [
			self::EXAMPLE,
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

			self::EXAMPLE => [
				'example' => ['required', 'string'],
			],

			default => ['form' => ['required', Rule::in(implode(',', $this->formTypes))]],
		};

	}


}
