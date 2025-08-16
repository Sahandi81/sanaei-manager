<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Client\Models\Client;

class Order extends Model
{
	protected $fillable = [
		'user_id',
		'client_id',
		'product_id',
		'price',
		'traffic_gb',
		'used_traffic_gb',
		'duration_days',
		'expires_at',
		'subs',
		'qr_path',
		'status'
	];
	const STATUS_PENDING = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_EXPIRED = 2;

	public static array $statuses = [
		self::STATUS_PENDING => [
			'text' => 'Pending',
			'type' => 'public',
			'status' => 'warning'
		],
		self::STATUS_ACTIVE => [
			'text' => 'Active',
			'type' => 'public',
			'status' => 'success'
		],
		self::STATUS_EXPIRED => [
			'text' => 'Expired',
			'type' => 'public',
			'status' => 'danger'
		]
	];
	public static function getStatusesRaw($filter = null): array
	{
		$updatedStatuses = [];
		foreach (self::$statuses as $index => $status) {
			if ($status['type'] === 'public' || $filter === '*') {
				$updatedStatuses[$index] = tr_helper('contents', $status['text']);
			}
		}
		return $updatedStatuses;
	}

	public static function getStatuses(): array
	{
		foreach (self::$statuses as $index => $status) {
			self::$statuses[$index]['text'] = tr_helper('contents', $status['text']);
		}
		return self::$statuses;
	}

	public static function getActiveItems(): Collection
	{
		$userAdminStatus = Auth::user()->role->is_admin;
		if ($userAdminStatus) {
			return self::query()
			->where('status', 1)
				->get();
		}else{
			return self::query()
			->where('status', 1)
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

	public function client(): BelongsTo
	{
		return $this->belongsTo(Client::class);
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(Product::class);
	}

	public function configs(): HasMany
	{
		return $this->hasMany(OrderConfig::class);
	}
}
