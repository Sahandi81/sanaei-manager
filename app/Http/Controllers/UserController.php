<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Logging\Traits\Loggable;
use Modules\Permission\App\Models\Role;
use Modules\TgBot\Services\TelegramWebhookService;

class UserController extends Controller
{
	use Loggable;

	public function index(): Factory|Application|View
	{
		$users = User::paginate();
		return view('users.list', compact('users'));
	}

	public function create(): Factory|Application|View
	{
		$roles = Role::getRoles();
		return view('users.create', compact('roles'));
	}

	public function store(Request $request): RedirectResponse
	{
		$fields = $request->validate([
			'name'     => 'required|string|max:255',
			'email'    => 'required|email|unique:users,email',
			'password' => 'required|min:6',
			'status'   => 'required|boolean',
			'role_key' => 'required|string|max:50',
		]);

		$fields['password'] = Hash::make($fields['password']);

		$user = User::query()->create($fields);

		$this->logInfo('createUser', 'User created', [
			'user_id' => $user->id,
			'role_key' => $user->role_key,
		]);

		return redirect()->route('panel.users.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	public function edit(User $client): Factory|Application|View
	{
		$roles = Role::getRoles();
		return view('users.edit', ['user' => $client, 'roles' => $roles]);
	}

	public function update(Request $request, User $client): RedirectResponse
	{
		$fields = $request->validate([
			'name'                 => 'required|string|max:255',
			'email'                => 'required|email|unique:users,email,' . $client->id,
			'password'             => 'nullable|min:6',
			'status'               => 'required|boolean',
			'role_key'             => 'required|string|max:50',
			'telegram_bot_token'   => 'nullable|string|max:255',
			'telegram_webhook'     => 'nullable|string|max:255',
		]);

		if (!empty($fields['password'])) {
			$fields['password'] = Hash::make($fields['password']);
		} else {
			unset($fields['password']);
		}

		if (!array_key_exists('telegram_webhook', $fields) || empty($fields['telegram_webhook'])) {
			if (empty($client->telegram_webhook)) {
				$fields['telegram_webhook'] = $this->generateUniqueTelegramWebhook();
				$this->logInfo('generateTelegramWebhook', 'Generated unique telegram_webhook for user', [
					'user_id' => $client->id,
					'webhook' => $fields['telegram_webhook'],
				]);
			} else {
				$fields['telegram_webhook'] = $client->telegram_webhook;
			}
		}

		$client->update($fields);

		$this->logInfo('updateUser', 'User updated', [
			'user_id' => $client->id,
		]);

		if (!empty($client->telegram_bot_token)) {
			try {
				$res = app(TelegramWebhookService::class)->setWebhookForUser($client);
				if (!$res)
					throw new \Exception(tr_helper('contents', 'ConnectionFailed'));
			} catch (\Exception $e) {
				$this->logError('setTelegramWebhookFailed', 'Failed to set Telegram webhook after user update', [
					'user_id'   => $client->id,
					'error'     => $e->getMessage(),
				]);

				return redirect()->back()
					->with('error_msg', tr_helper('contents', 'ConnectionFailed'));
			}
		}

		return redirect()->route('panel.users.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}


	public function destroy(User $client): RedirectResponse
	{
		$client->delete();

		$this->logInfo('deleteUser', 'User deleted', [
			'user_id' => $client->id,
		]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}


	private function generateUniqueTelegramWebhook(int $length = 40): string
	{
		do {
			// رشته امن و طولانی (حروف/ارقام) — می‌تونی از Str::uuid() هم استفاده کنی
			$slug = Str::random($length);
		} while (User::where('telegram_webhook', $slug)->exists());

		return $slug;
	}
}
