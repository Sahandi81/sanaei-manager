<?php

namespace Modules\Permission\App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Permission\App\Http\Traits\RoleHelper;
use Modules\Permission\App\Models\Role;

class AccessMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws AuthorizationException
	 * @throws AuthenticationException
	 */
    public function handle(Request $request, Closure $next): mixed
	{
		if ( ! Auth::check())
			throw new AuthenticationException();

		$role = Role::where('role_key', Auth::user()->role);
		if ( ! RoleHelper::hasAccess($role->first(), Route::currentRouteName())){
			throw new AuthorizationException();
		};
		return $next($request);
    }
}
