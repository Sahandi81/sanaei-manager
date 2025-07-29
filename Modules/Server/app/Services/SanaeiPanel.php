<?php

namespace Modules\Server\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Crypt;
use Modules\Logging\Services\LoggingService;
use Modules\Server\Contracts\PanelInterface;
use Modules\Server\Models\Server;

class SanaeiPanel implements PanelInterface
{
	protected Server $server;
	protected Client $client;
	protected LoggingService $logger;
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
		$this->logger = app(LoggingService::class);
	}

	public function testConnection(): bool
	{
		try {
			$response = $this->client->get('xui/inbounds');
			return $response->getStatusCode() === 200;
		} catch (Exception $e) {
			$this->logger->logError('Sanaei', 'testConnection', 'Connection failed', [
				'error' => $e->getMessage(),
				'server' => $this->server->id,
			]);
			return false;
		}
	}

	public function login(): bool
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
			return true;
		} catch (Exception $e) {
			$this->logger->logError('Sanaei', 'login', 'Login failed', [
				'error' => $e->getMessage(),
				'server' => $this->server->id,
			]);
			return false;
		}
	}

	public function getInbounds(): array
	{
		try {
			if (!$this->login()) {
				return [];
			}

			$response = $this->client->get('xui/inbounds', [
				'cookies' => $this->cookieJar,
			]);

			return json_decode($response->getBody(), true)['obj'] ?? [];
		} catch (Exception $e) {
			$this->logger->logError('Sanaei', 'getInbounds', 'Failed to get inbounds', ['error' => $e->getMessage()]);
			return [];
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
			$this->logger->logError('Sanaei', 'createUser', 'User creation failed', ['error' => $e->getMessage()]);
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
			$this->logger->logError('Sanaei', 'disableInbound', 'Disabling failed', ['error' => $e->getMessage()]);
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
			$this->logger->logError('Sanaei', 'rechargeInbound', 'Recharge failed', ['error' => $e->getMessage()]);
			return false;
		}
	}
}
