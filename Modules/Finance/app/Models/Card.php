<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Card extends Model
{
	protected $fillable = [
		'user_id',
		'card_number',
		'bank_name',
		'owner_name',
		'is_default',
	];

	protected $casts = [
		'is_default' => 'boolean',
	];

	public function user()
	{
		return $this->belongsTo(\App\Models\User::class);
	}

	public static function makeOnlyDefaultForUser(int $userId, int $cardId): void
	{
		DB::transaction(function () use ($userId, $cardId) {
			self::query()
				->where('user_id', $userId)
				->where('id', '!=', $cardId)
				->where('is_default', true)
				->update(['is_default' => false]);

			self::query()->whereKey($cardId)->update(['is_default' => true]);
		});
	}

	public static function ensureHasDefault(int $userId): void
	{
		$hasDefault = self::query()
			->where('user_id', $userId)
			->where('is_default', true)
			->exists();

		if (! $hasDefault) {
			$first = self::query()->where('user_id', $userId)->orderByDesc('id')->first();
			if ($first) {
				$first->update(['is_default' => true]);
			}
		}
	}

	public static function getActiveItems(): Collection
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus) {
			return self::query()
				->get();
		} else {
			$users = User::getOwnUsers()->pluck('id');
			return self::query()
				->where('user_id', Auth::id())
				->orWhereIn('user_id', $users)
				->get();
		}
	}

	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus){
			return self::query()->latest()->paginate($perPage);
		} else {
			$users = User::getOwnUsers()->pluck('id');
			return self::query()->where('user_id', Auth::id())->orWhereIn('user_id', $users)->latest()->paginate($perPage);
		}
	}
}
