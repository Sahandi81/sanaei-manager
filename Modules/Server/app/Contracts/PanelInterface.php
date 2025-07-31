<?php

namespace Modules\Server\Contracts;

interface PanelInterface
{
	public function testConnection(): bool;
	public function login(): ?bool;
	public function getInbounds(): bool|array;
	public function createUser(array $payload): bool;
	public function disableInbound(int $id): bool;
	public function rechargeInbound(int $id, int $expiryDays): bool;
}
