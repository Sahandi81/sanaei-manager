<?php

namespace Modules\Server\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inbound extends Model
{
	protected $fillable = [
		'server_id',
		'panel_inbound_id',
		'port',
		'protocol',
		'stream',
		'up',
		'down',
		'total',
		'enable',
		'status',
		'remark',
		'raw',
	];

	protected $casts = [
		'raw' => 'array',
		'enable' => 'boolean',
	];

	public function server(): BelongsTo
	{
		return $this->belongsTo(Server::class);
	}
}
