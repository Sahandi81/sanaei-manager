<?php

namespace Modules\Permission\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoleHasPermission extends Model
{
    use HasFactory;

    protected $fillable = [
			'role_id',
			'permission_id',
			'created_by',
	];

}
