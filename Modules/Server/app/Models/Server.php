<?php

namespace Modules\Server\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class Server extends Model
{
	const INACTIVE_STATUS   = 0;
	const ACTIVE_STATUS     = 1;
	const INTERRUPT_STATUS  = 2;

	public static array $statuses = [
		self::INACTIVE_STATUS => [
			'text'   => 'InActive',
			'type'   => 'public',
			'status' => 'warning'
		],
		self::ACTIVE_STATUS => [
			'text'   => 'Active',
			'type'   => 'public',
			'status' => 'success'
		],
		self::INTERRUPT_STATUS => [
			'text'   => 'Interrupt',
			'type'   => 'system',
			'status' => 'danger'
		]
	];

	protected $fillable = [
		'creator_id',
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

	/** -------- Status helpers -------- */
	public static function getStatuesRaw($filter = null): array
	{
		$updatedStatuses = [];
		foreach (self::$statuses as $index => $status) {
			if ($status['type'] === 'public' || $filter === '*') {
				$updatedStatuses[$index] = tr_helper('contents', $status['text']);
			}
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

	/** -------- Relationships -------- */
	// رابطه‌ی قدیمی (سازگاری موقت)
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	// رابطه‌ی جدید many-to-many
	public function users(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'server_user', 'server_id', 'user_id')->withTimestamps();
	}

	public function inbounds(): HasMany
	{
		return $this->hasMany(Inbound::class);
	}

	public function activeInbounds(): HasMany
	{
		return $this->inbounds()->tap(function ($q) {
			$q->where('status', 1);
		});
	}

	/** -------- Visibility scope -------- */
	public function scopeVisibleTo($query, User $user)
	{
		if ($user->role->full_access) {
			return $query; // سوپر ادمین همه را می‌بیند
		}

		return $query->where(function ($q) use ($user) {
			$users = User::getOwnUsers()->pluck('id');
			$q->whereHas('users', fn($qq) => $qq->where('users.id', $user->id)->orWhereIn('users.id', $users));
		});
	}

	/** -------- Queries (refactored) -------- */
	public static function getActiveServers(): Collection
	{
		$user = Auth::user();

		return self::query()
			->visibleTo($user)
			->where('status', self::ACTIVE_STATUS)
			->get();
	}

	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$user = Auth::user();

		return self::query()
			->visibleTo($user)
			->latest()
			->paginate($perPage);
	}
}
