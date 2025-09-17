<?php

namespace Modules\Client\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Shop\Models\Order;

class Client extends Model
{
	protected $fillable = [
		'user_id',
		'name',
		'telegram_id',
		'type',
		'desc',
		'status',
		'referrer_id',
		'referral_code',
		'referred_at',
	];


	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$userAdminStatus = Auth::user()->role->is_admin;
		if ($userAdminStatus){
			return self::query()->latest()->paginate($perPage);
		}else {
			return self::query()->where('user_id', Auth::id())->latest()->paginate($perPage);
		}
	}


	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function orders(): HasMany
	{
		return $this->hasMany(Order::class)->tap(fn($q) => $q->latest());
	}

	public function referrer(): BelongsTo
	{
		return $this->belongsTo(Client::class, 'referrer_id');
	}

	public function referrals(): HasMany
	{
		return $this->hasMany(Client::class, 'referrer_id')->tap(fn($q) => $q->latest());
	}

	/**
	 * اگر کد دعوت ندارد، یک کد یکتا تولید و ذخیره می‌کند.
	 */
	public function ensureReferralCode(): void
	{
		if (!empty($this->referral_code)) return;

		do {
			// کد کوتاه، تصادفی و lowercase
			$code = Str::lower(Str::random(10));
		} while (self::query()->where('referral_code', $code)->exists());

		$this->referral_code = $code;
		$this->save();
	}
}
