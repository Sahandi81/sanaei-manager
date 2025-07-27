<?php

namespace Modules\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServerRequest extends FormRequest
{
	private array $formTypes;

	const CREATE_SERVER = 'create_server';

	private function setFromTypes()
	{
		$this->formTypes = [
			self::CREATE_SERVER,
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

			self::CREATE_SERVER => [
				'user_id' 			=> ['nullable'],
				'name' 				=> ['required', 'string', 'min:3', 'max:255'],
				'ip' 				=> ['required', 'string', 'ip'],
				'location' 			=> ['nullable', 'string', 'max:100'],
				'panel_type' 		=> ['required', 'in:sanaei'], // add more types if needed
				'api_url' 			=> ['required', 'string', 'url', 'min:10'],
				'status' 			=> ['required', 'int'],
			],

			default => ['form' => ['required', Rule::in(implode(',', $this->formTypes))]],
		};

	}


}
