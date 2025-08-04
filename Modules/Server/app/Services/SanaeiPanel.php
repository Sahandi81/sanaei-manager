<?php

namespace Modules\Server\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Crypt;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Contracts\PanelInterface;
use Modules\Server\Models\Server;

class SanaeiPanel implements PanelInterface
{
	use Loggable;

	protected Server $server;
	protected Client $client;
	protected CookieJar $cookieJar;

	public function __construct(Server $server)
	{
		$this->server = $server;

		$this->client = new Client([
			'base_uri' => $this->server->api_url,
			'timeout'  => 5.0,
			'verify'   => false,
		]);

		$this->cookieJar = new CookieJar();
	}

	public function testConnection(): bool
	{
		try {
			$response = $this->client->get('xui/inbounds');
			return $response->getStatusCode() === 200;
		} catch (Exception $e) {
			$this->logError('testConnection', 'Connection failed', [
				'error' => $e->getMessage(),
				'server_id' => $this->server->id,
			]);
			return false;
		}
	}

	public function login(): bool|string
	{
		try {
			$password = Crypt::decryptString($this->server->password);

			$response = $this->client->post('login', [
				'json' => [
					'username' => $this->server->username,
					'password' => $password,
				],
				'cookies' => $this->cookieJar,
			]);

			$data = json_decode($response->getBody(), true);

			if (!($data['success'] ?? false)) {
				throw new Exception($data['msg'] ?? 'Unknown error');
			}

			// optional: return session cookie or true
			return true;
		} catch (Exception $e) {
			$this->logError('login', 'Login failed', [
				'error' => $e->getMessage(),
				'server_id' => $this->server->id,
			]);
			return false;
		}
	}

	public function getInbounds(): bool|array
	{
		try {
			if (!$this->login()) {
				return false;
			}

			$response = $this->client->get('panel/api/inbounds/list', [
				'cookies' => $this->cookieJar,
			]);

			$data = json_decode($response->getBody()->getContents(), true);

			if (isset($data['success']) && $data['success']) {
				return $data['obj'] ?? [];
			}

			throw new Exception("Failed to get inbounds. File: " . __FILE__ . " Line: " . __LINE__);

		} catch (Exception $e) {
			$this->logError('getInbounds', 'Fetching inbounds failed', [
				'error' => $e->getMessage(),
				'server_id' => $this->server->id,
			]);
			return false;
		}
	}

	public function createUser(array $payload): bool
	{
		try {
			if (!$this->login()) {
				return false;
			}

			$response = $this->client->post('xui/inbound/add', [
				'cookies' => $this->cookieJar,
				'json' => $payload,
			]);

			return $response->getStatusCode() === 200;
		} catch (Exception $e) {
			$this->logError('createUser', 'User creation failed', [
				'error' => $e->getMessage(),
				'server_id' => $this->server->id,
			]);
			return false;
		}
	}

	public function disableInbound(int $id): bool
	{
		try {
			if (!$this->login()) {
				return false;
			}

			$response = $this->client->post('xui/inbound/update', [
				'cookies' => $this->cookieJar,
				'json' => [
					'id' => $id,
					'enable' => false,
				]
			]);

			return $response->getStatusCode() === 200;
		} catch (Exception $e) {
			$this->logError('disableInbound', 'Disabling inbound failed', [
				'error' => $e->getMessage(),
				'inbound_id' => $id,
				'server_id' => $this->server->id,
			]);
			return false;
		}
	}

	public function rechargeInbound(int $id, int $expiryDays): bool
	{
		try {
			if (!$this->login()) {
				return false;
			}

			$response = $this->client->post('xui/inbound/updateClientSettings', [
				'cookies' => $this->cookieJar,
				'json' => [
					'id' => $id,
					'expiryTime' => now()->addDays($expiryDays)->timestamp * 1000,
				]
			]);

			return $response->getStatusCode() === 200;
		} catch (Exception $e) {
			$this->logError('rechargeInbound', 'Recharge failed', [
				'error' => $e->getMessage(),
				'inbound_id' => $id,
				'days' => $expiryDays,
				'server_id' => $this->server->id,
			]);
			return false;
		}
	}
}
