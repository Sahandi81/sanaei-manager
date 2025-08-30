<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class WalletTransaction extends Model
{
	protected $fillable = [
		'wallet_id','type','amount_minor','running_balance_minor',
		'idempotency_key','ref_type','ref_id','meta'
	];

	protected $casts = ['meta' => 'array'];

	public const TYPE_DEPOSIT      = 'deposit';
	public const TYPE_WITHDRAW     = 'withdraw';
	public const TYPE_TRANSFER_OUT = 'transfer_out';
	public const TYPE_TRANSFER_IN  = 'transfer_in';
	public const TYPE_ADJUSTMENT   = 'adjustment';

	public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }

	public function ref(): MorphTo { return $this->morphTo(__FUNCTION__, 'ref_type','ref_id'); }

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
}
