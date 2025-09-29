<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Permission\App\Models\Role;
use Modules\Server\Models\Server;
use Modules\Shop\Models\Product;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'email',
        'parent_id',
        'password',
        'telegram_id',
        'role_key',
        'bot_name',
        'bot_id',
        'support_id',
        'tut_url',
        'telegram_bot_token',
        'telegram_webhook',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

	public static function getActiveUsers(): Collection
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus){
			return self::query()->where('status', 1)->latest()->get();
		}else {
			return self::getOwnUsers()->where('status', 1)->orWhere('id', Auth::id())->latest()->get();
		}
	}

	public static function paginate($perPage = 25): LengthAwarePaginator
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus){
			return self::query()->latest()->paginate($perPage);
		}else {
			return self::getOwnUsers()->orWhere('id', Auth::id())->latest()->paginate($perPage);
		}
	}

	public static function getOwnUsers()
	{
		return self::query()->where('parent_id', Auth::id());
	}

	/**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


	public static function getNonSuperAdmins(): array
	{
		return User::query()
			->where('role_key', '!=', 'super_admin')
			->pluck('email', 'id')->toArray();
	}


	public function role(): HasOne
	{
		return $this->hasOne(Role::class, 'role_key', 'role_key');
	}


	public function products(): HasMany
	{
		return $this->hasMany(Product::class);
	}

	public function servers(): BelongsToMany
	{
		return $this->belongsToMany(Server::class, 'server_user', 'user_id', 'server_id')->withTimestamps();
	}
}
