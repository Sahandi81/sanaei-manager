<?php

namespace Modules\TgBot\Handlers\Contracts;

use App\Models\User;

interface Handler
{
	public function handle(User $owner, array $update): void;
}
