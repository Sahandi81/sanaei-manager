<?php

namespace Modules\Permission\App\Http\Traits;

use JetBrains\PhpStorm\ArrayShape;
use Modules\Permission\App\Http\Controllers\PermissionController;
use Modules\Permission\App\Models\Role;

class RoleHelper
{

	private static array $rolePermissions;

	private static Role $role;

	private static array $permission;


	private static function setPermission(Role $role): void
	{
		self::$permission = $role->permissions()->pluck('route_name')->toArray();;
	}

	private static function setRole(Role $role): void
	{
		self::$role = $role;
	}

	protected static function roleHasPermission(string|object $route): bool
	{
		if (self::$role->full_access){
			return true;
		} else {
			$routeName = is_string($route) ? $route : $route->route_name;
			return in_array($routeName, self::$permission);
		}
	}

	#[ArrayShape(['role' => "mixed", 'role_permissions' => "array"])] public static function rolePermissions(Role $role): array
	{
		self::setPermission($role);
		self::setRole($role);
		$allPermissions = (new PermissionController())->index('system');
		foreach ($allPermissions as $index => $permissionGroup){
			self::$rolePermissions[$index] = [];
			foreach ($permissionGroup as $permission){
				$access = self::roleHasPermission($permission);
				self::$rolePermissions[$index][] = [
					'id' 			=> $permission['id'],
					'route_name' 	=> $permission['route_name'],
					'url'			=> $permission['url'],
					'method'		=> $permission['method'],
					'access'		=> $access,
				];
			}
		}
		return ['role' => self::$role->title, 'role_permissions' => self::$rolePermissions];
	}


	public static function hasAccess(Role $role, string $routeName): bool
	{
		self::setRole($role);
		self::setPermission($role);
		return self::roleHasPermission($routeName);
	}





}
