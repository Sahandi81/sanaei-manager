<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Permission\App\Models\Role;
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
        'password',
        'role_key',
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
		return self::query()->where('status', 1)->get();
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
}
