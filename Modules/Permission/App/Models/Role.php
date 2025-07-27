<?php

namespace Modules\Permission\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Role extends Model
{
    use HasFactory;


	protected $fillable = [
		'role_key',
		'title',
		'modified_by',
		'is_admin',
		'full_access',
	];

	protected $hidden = [
		'modified_by'
	];


	public function permissions(): HasManyThrough
	{
		return $this->hasManyThrough(Permission::class, RoleHasPermission::class, 'role_id', 'permissions.id', 'id', 'permission_id');
	}
    
}
