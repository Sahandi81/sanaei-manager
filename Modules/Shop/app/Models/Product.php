<?php

namespace Modules\Shop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Server\Models\Server;

class Product extends Model
{
	protected $fillable = [
		'user_id',
		'name',
		'traffic_gb',
		'duration_days',
		'price',
		'user_limit',
		'is_active',
		'is_test',
		'parent_id',
	];

	public static function getActiveProducts($userID = null): Collection
	{
		$userAdminStatus = Auth::user()->role->is_admin;
		if ($userAdminStatus) {
			return self::query()->where('is_active', 1)
				->tap(function ($q) use ($userID){
					if ($userID){
						$q->where('user_id', $userID);
					}
				})
				->get();
		}else{
			return self::query()
				->where('user_id', Auth::id())
				->where('is_active', 1)->get();
		}
	}

	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$userAdminStatus = Auth::user()->role->is_admin;

		$self = self::with('servers')->latest();

		if ($userAdminStatus) {
			return $self->paginate($perPage);
		}else{
			return $self
				->where('user_id', Auth::id())
				->paginate($perPage);
		}
	}

	public function servers(): BelongsToMany
	{
		return $this->belongsToMany(
			Server::class,
			'product_server',
			'product_id',
			'server_id'
		);
	}

	public function parent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'parent_id');
	}

	public function testProducts(): HasMany
	{
		return $this->hasMany(self::class, 'parent_id');
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
