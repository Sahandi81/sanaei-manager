<?php

namespace Modules\Permission\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
			'route_name',
			'parent',
			'title',
			'url',
			'method',
	];
    
}
