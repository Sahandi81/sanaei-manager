<?php

namespace Modules\Permission\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
	private array $formTypes;

	const ADMIN_CREATE_ROLE 		= 'admin_create_role';
	const ADMIN_UPDATE_ROLE 		= 'admin_update_role';

	private function setFromTypes()
	{
		$this->formTypes = [
			self::ADMIN_CREATE_ROLE,
			self::ADMIN_UPDATE_ROLE,
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

			self::ADMIN_CREATE_ROLE => [
				'title'			=> ['required', 'string', Rule::unique('roles', 'title')->ignore($this->route('role') ?? 0)],
				'role_key'		=> ['nullable', 'string', Rule::unique('roles', 'role_key')->ignore($this->route('role') ?? 0)],
				'full_access'	=> ['required', 'numeric', 'max:1', 'min:0'],
				'is_admin'		=> ['required', 'numeric', 'max:1', 'min:0'],
			],
			self::ADMIN_UPDATE_ROLE => [
				'title'			=> ['required', 'string', Rule::unique('roles', 'title')->ignore($this->route('role') ?? 0)],
				'full_access'	=> ['required', 'numeric', 'max:1', 'min:0'],
				'is_admin'		=> ['required', 'numeric', 'max:1', 'min:0'],
			],

			default => ['form' => ['required', Rule::in(implode(',', $this->formTypes))]],
		};

	}


}