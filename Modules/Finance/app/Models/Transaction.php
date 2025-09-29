<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Client\Models\Client;
use Modules\FileManager\Models\FileManager;

class Transaction extends Model
{
	// Status constants
	const STATUS_PENDING = 0;
	const STATUS_APPROVED = 1;
	const STATUS_REJECTED = 2;

	// Type constants
	const TYPE_PANEL = 'panel';
	const TYPE_TELEGRAM = 'telegram';

	public static array $statuses = [
		self::STATUS_PENDING => [
			'text' => 'Pending',
			'type' => 'public',
			'status' => 'warning'
		],
		self::STATUS_APPROVED => [
			'text' => 'Approved',
			'type' => 'public',
			'status' => 'success'
		],
		self::STATUS_REJECTED => [
			'text' => 'Rejected',
			'type' => 'public',
			'status' => 'danger'
		]
	];

	protected $fillable = [
		'user_id',
		'modified_by',
		'client_id',
		'amount',
		'currency',
		'description',
		'card_id',
		'status',
		'type',
		'item_type',
		'item_id',
		'verified_at',
		'rejection_reason'
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

	public static function getPendingTransactions(): Collection
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus) {
			return self::query()
				->where('status', self::STATUS_PENDING)
				->get();
		}
		$users = User::getOwnUsers()->pluck('id');
		return self::query()
			->where('status', self::STATUS_PENDING)
			->where('user_id', Auth::id())
			->orWhereIn('user_id', $users)
			->get();
	}

	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus) {
			return self::query()->latest()->paginate($perPage);
		}
		$users = User::getOwnUsers()->pluck('id');
		return self::query()
			->where('user_id', Auth::id())
			->orWhereIn('user_id', $users)
			->latest()
			->paginate($perPage);
	}

	public function client(): BelongsTo
	{
		return $this->belongsTo(Client::class);
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function modifier(): BelongsTo
	{
		return $this->belongsTo(User::class, 'modified_by');
	}

	public function getTypeTextAttribute(): string
	{
		return match($this->type) {
			self::TYPE_TELEGRAM => 'Telegram',
			default => 'Panel',
		};
	}

	public function files(): MorphMany
	{
		return $this->morphMany(FileManager::class, 'item');
	}
}
