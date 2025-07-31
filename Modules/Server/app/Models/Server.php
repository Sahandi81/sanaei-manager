<?php

namespace Modules\Server\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
	public static array $statuses = [
		0 => [
			'text' 		=> 'InActive',
			'type' 		=> 'public',
			'status' 	=> 'warning'
		],
		1 => [
			'text' 		=> 'Active',
			'type' 		=> 'public',
			'status' 	=> 'success'
		],
		2 => [
			'text' 		=> 'Interrupt',
			'type' 		=> 'system',
			'status' 	=> 'danger'
		]
	];
    protected $fillable = [
		'creator_id',
		'user_id',
		'name',
		'ip',
		'location',
		'panel_type',
		'api_url',
		'api_key',
		'username',
		'password',
		'status',
	];

	public static function getStatuesRaw($filter = null): array
	{
		$updatedStatuses = [];
		foreach (self::$statuses as $index => $status) {
			if ($status['type'] === 'public' || $filter === '*')
				$updatedStatuses[$index] = tr_helper('contents', $status['text']);
		}
		return $updatedStatuses;
	}
	public static function getStatues(): array
	{
		foreach (self::$statuses as $index => $status) {
			self::$statuses[$index]['text'] = tr_helper('contents', $status['text']);
		}
		return self::$statuses;
	}





	public function inbounds(): HasMany
	{
		return $this->hasMany(Inbound::class);
	}

}
