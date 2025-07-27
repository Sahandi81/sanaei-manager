<?php

namespace Modules\Permission\App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Modules\Permission\App\Models\Role;
use Modules\User\App\Http\Controllers\Auth\LoginController;

class AdminAccessMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws AuthorizationException
	 */
    public function handle(Request $request, Closure $next): mixed
	{
		$user = Auth::user();
		if ($user){
			$role = Role::where('role_key', $user->role)->first();
			if ($role->is_admin){
				return $next($request);
			}
		}
		if ($_SERVER['HTTP_ACCEPT'] === 'application/json'){
			throw new AuthorizationException();
		}else{
			# Logging out user for prevent redirecting!
			(new LoginController())->logout($request);
			return Redirect::route('login')->withErrors('USER_IS_NOT_ADMIN');
		}
	}
}
