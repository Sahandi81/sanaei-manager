<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class Wallet extends Model
{
	protected $fillable = [
		'owner_type','owner_id','currency','balance_minor','status','meta'
	];

	protected $casts = ['meta' => 'array'];

	public const STATUS_INACTIVE = 0;
	public const STATUS_ACTIVE   = 1;
	public const STATUS_FROZEN   = 2;

	public function owner(): MorphTo { return $this->morphTo(); }

	public function transactions(): HasMany
	{
		return $this->hasMany(WalletTransaction::class);
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
}
