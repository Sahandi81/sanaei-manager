<?php

namespace Modules\Server\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class Server extends Model
{

	const INACTIVE_STATUS = 0;
	const ACTIVE_STATUS = 1;
	const INTERRUPT_STATUS = 2;


	public static array $statuses = [
		self::INACTIVE_STATUS => [
			'text' 		=> 'InActive',
			'type' 		=> 'public',
			'status' 	=> 'warning'
		],
		self::ACTIVE_STATUS => [
			'text' 		=> 'Active',
			'type' 		=> 'public',
			'status' 	=> 'success'
		],
		self::INTERRUPT_STATUS => [
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

	public static function getActiveServers(): Collection
	{
		$userAdminStatus = Auth::user()->role->is_admin;
		if ($userAdminStatus) {
			return self::query()
			->where('status', Server::ACTIVE_STATUS)
				->get();
		}else{
			return self::query()
			->where('status', Server::ACTIVE_STATUS)
			->where('user_id', Auth::id())
				->get();
		}
	}


	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$userAdminStatus = Auth::user()->role->is_admin;
		if ($userAdminStatus){
			return self::query()->latest()->paginate($perPage);
		}else {
			return self::query()->where('user_id', Auth::id())->latest()->paginate($perPage);
		}
	}



	public function inbounds(): HasMany
	{
		return $this->hasMany(Inbound::class);
	}

}
