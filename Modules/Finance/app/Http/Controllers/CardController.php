<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Logging\Traits\Loggable;
use Modules\Finance\Models\Card;
use Modules\Finance\Http\Requests\CardRequest;

class CardController extends Controller
{
	use Loggable;

	public function index(): Factory|Application|View
	{
		$cards = Card::paginate();
		return view('finance::cards.list', compact('cards'));
	}

	public function create(): Factory|Application|View
	{
		$users = Auth::user()->role->is_admin ? User::getActiveUsers() : collect();
		return view('finance::cards.create', compact('users'));
	}

	public function store(CardRequest $request): RedirectResponse
	{
		$fields = $request->validated();

		if (!isset($fields['user_id']) || !Auth::user()->role->is_admin) {
			$fields['user_id'] = Auth::id();
		}

		$userId = (int) $fields['user_id'];
		$isFirst = ! Card::query()->where('user_id', $userId)->exists();
		if ($isFirst) {
			$fields['is_default'] = true;
		}

		$card = DB::transaction(function () use ($fields, $userId) {
			$card = Card::query()->create($fields);

			if (!empty($fields['is_default'])) {
				Card::makeOnlyDefaultForUser($userId, $card->id);
			} else {
				Card::ensureHasDefault($userId);
			}

			return $card;
		});

		$this->logInfo('createCard', 'Card created', [
			'card_id' => $card->id,
			'user_id' => $card->user_id,
		]);

		return redirect()->route('finance.cards.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	public function edit(Card $card): Factory|Application|View
	{
		$users = Auth::user()->role->is_admin ? User::getActiveUsers() : collect();
		return view('finance::cards.edit', compact('card', 'users'));
	}

	public function update(CardRequest $request, Card $card): RedirectResponse
	{
		$fields = $request->validated();

		// جلوگیری از تغییر user_id توسط کاربر غیر ادمین
		if (!Auth::user()->role->is_admin) {
			unset($fields['user_id']);
		}

		DB::transaction(function () use ($fields, $card) {
			$card->update($fields);

			// اگر is_default=true شد، بقیه را false کن
			if (array_key_exists('is_default', $fields) && $fields['is_default']) {
				Card::makeOnlyDefaultForUser($card->user_id, $card->id);
			} else {
				// اگر هیچ پیش‌فرضی برای کاربر باقی نماند، یکی را پیش‌فرض کن
				Card::ensureHasDefault($card->user_id);
			}
		});

		$this->logInfo('updateCard', 'Card updated', [
			'card_id' => $card->id,
		]);

		return redirect()->route('finance.cards.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}

	public function destroy(Card $card): RedirectResponse
	{
		$userId = $card->user_id;

		DB::transaction(function () use ($card, $userId) {
			$wasDefault = (bool) $card->is_default;
			$card->delete();

			if ($wasDefault) {
				// اگر کارت پیش‌فرض حذف شد، یکی از باقی‌مانده‌ها را پیش‌فرض کن
				Card::ensureHasDefault($userId);
			}
		});

		$this->logInfo('deleteCard', 'Card deleted', [
			'card_id' => $card->id,
		]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}

	/** اکشن جدا برای ست‌کردن پیش‌فرض (دکمه در لیست) */
	public function setDefault(Card $card): RedirectResponse
	{
		Card::makeOnlyDefaultForUser($card->user_id, $card->id);

		$this->logInfo('setDefaultCard', 'Card set as default', [
			'card_id' => $card->id,
		]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}
}
