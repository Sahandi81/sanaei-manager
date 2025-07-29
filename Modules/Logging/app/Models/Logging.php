<?php

namespace Modules\Logging\Models;

use Illuminate\Database\Eloquent\Model;

class Logging extends Model
{
    protected $fillable = [
    	'module',
    	'action',
    	'message',
    	'context',
    	'level',
    	'user_id',
	];

	protected $casts = [
		'context' => 'array',
	];

}
