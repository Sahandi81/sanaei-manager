<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Client\Models\Client;
use Modules\Server\Models\Server;
use Modules\Server\Models\Inbound;
use Modules\Shop\Models\Order;

class OrderConfig extends Model
{
	protected $fillable = [
		'server_id',
		'inbound_id',
		'panel_email',
		'order_id',
		'used_traffic_gb',
		'client_id',
		'config',
		'expires_at'
	];

	protected $casts = [
		'expires_at' => 'datetime'
	];

	public function server(): BelongsTo
	{
		return $this->belongsTo(Server::class);
	}

	public function inbound(): BelongsTo
	{
		return $this->belongsTo(Inbound::class);
	}

	public function order(): BelongsTo
	{
		return $this->belongsTo(Order::class);
	}

	public function client(): BelongsTo
	{
		return $this->belongsTo(Client::class);
	}
}
