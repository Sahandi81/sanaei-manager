<?php

namespace Modules\Permission\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
	private array $formTypes;

	const ADMIN_SYNC_PERMISSION 	= 'admin_sync_permission';

	private function setFromTypes()
	{
		$this->formTypes = [
			self::ADMIN_SYNC_PERMISSION,
		];

		$this->formTypes = array_combine($this->formTypes, $this->formTypes); # =))
	}


	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
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

			self::ADMIN_SYNC_PERMISSION => [
				'permission'		=> ['required', 'array'],
				'permission.*'		=> ['required']
			],

			default => ['form' => ['required', Rule::in(implode(',', $this->formTypes))]],
		};

	}


}