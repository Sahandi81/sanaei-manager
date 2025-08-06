<?php

namespace Modules\Client\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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

}
