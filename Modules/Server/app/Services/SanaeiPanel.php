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

	public function login(): ?bool
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

	public function createUser(array $payload, int $clientID, $inboundIds = []): bool
	{
		try {
			if (!$this->login()) {
				return false;
			}
			foreach ($inboundIds as $inboundId)
			{
				$email = explode('-', $payload['email']);
				$payload['email'] =  $email[0] .'-' .rand(999, 9999);
				$clientData = [
					'id' => $inboundId,
					'settings' => json_encode([
						'clients' => [
							$payload
						]
					]),
				];

				$response = $this->client->post('panel/api/inbounds/addClient', [
					'cookies' 		=> $this->cookieJar,
					'form_params' 	=> $clientData,
				]);

				$data = json_decode($response->getBody()->getContents(), true);

				if (isset($data['success']) && $data['success']) {
					$this->logInfo('addClient', 'Adding client successfully', [
						'client_id' 	=> $clientID,
						'server_id' 	=> $this->server->id,
						'inbound_id' 	=> $inboundId,
					]);
					continue;
				}

				$this->logError('addClient', 'Adding client failed', [
					'error' 		=> "Failed to add client. details: " . json_encode($clientData),
					'server_id' 	=> $this->server->id,
					'client_id' 	=> $clientID,
					'inbound_id' 	=> $inboundId,
				]);
			}
			return true;

		} catch (Exception $e) {
			$this->logError('addClient', 'Adding client failed', [
				'error' 		=> $e->getMessage(),
				'server_id' 	=> $this->server->id,
				'inbound_id' 	=> $inboundId ?? json_encode($inboundIds),
				'client_id' 	=> $clientID,
			]);
			return false;
		}
	}

	public function deleteClientByUuid(int $inboundId, string $uuid): bool
	{

		try {
			if (!$this->login()) {
				return false;
			}

			$resp = $this->client->post("panel/api/inbounds/{$inboundId}/delClient/{$uuid}", [
				'cookies' => $this->cookieJar,
				'headers' => ['Accept' => 'application/json'],
			]);

			$body = json_decode($resp->getBody()->getContents(), true);
			$ok   = ($resp->getStatusCode() === 200) && ($body['success'] ?? false);

			if ($ok) {
				$this->logInfo('delClient', 'Client deleted', [
					'endpoint'   => "panel/api/inbounds/{$inboundId}/delClient/{$uuid}",
					'inbound_id' => $inboundId,
					'uuid'       => $uuid,
					'server_id'  => $this->server->id,
				]);
				return true;
			}

			$this->logError('delClient', 'Delete client returned non-success', [
				'endpoint'   => "panel/api/inbounds/{$inboundId}/delClient/{$uuid}",
				'inbound_id' => $inboundId,
				'uuid'       => $uuid,
				'server_id'  => $this->server->id,
				'api_res'    => $body,
			]);
			return false;
		} catch (\Throwable $e) {
			$this->logError('delClient', 'Delete client exception', [
				'inbound_id' => $inboundId,
				'uuid'       => $uuid,
				'server_id'  => $this->server->id,
				'error'      => $e->getMessage(),
			]);
			return false;
		}
	}

	public function generateConfig(array $inbound, array $client, array $streamSettings): string
	{
		$protocol = strtolower($inbound['protocol'] ?? 'vless');
		$port = $inbound['port'] ?? '';
		$remark = ($inbound['remark'] ?? '') . $client['email'];
		$network = strtolower($streamSettings['network'] ?? 'tcp');
		$security = strtolower($streamSettings['security'] ?? 'none');

		if ($protocol === 'vmess') {
			// Generate standard VMess base64 config
			$vmessData = [
				'v' => '2',
				'ps' => $remark,
				'add' => $this->server->ip,
				'port' => $port,
				'id' => $client['id'],
				'aid' => $client['alterId'] ?? 0,
				'scy' => $client['security'] ?? 'auto',
				'net' => $network,
				'type' => 'none', // Default, will be overridden based on network
				'host' => '',
				'path' => '',
				'tls' => $security,
				'sni' => '',
				'alpn' => '',
				'fp' => '',
			];

			// Handle network-specific settings
			switch ($network) {
				case 'tcp':
					$tcpSettings = $streamSettings['tcpSettings'] ?? [];
					$header = $tcpSettings['header'] ?? [];
					$vmessData['type'] = $header['type'] ?? 'none';
					if (($header['type'] ?? '') === 'http') {
						$httpSettings = $header['request'] ?? [];
						$vmessData['host'] = implode(',', $httpSettings['headers']['Host'] ?? []);
						$vmessData['path'] = implode(',', $httpSettings['path'] ?? ['/']);
					}
					break;

				case 'kcp':
				case 'mkcp':
					$kcpSettings = $streamSettings['kcpSettings'] ?? [];
					$vmessData['type'] = $kcpSettings['header']['type'] ?? 'none';
					break;

				case 'ws':
				case 'websocket':
					$wsSettings = $streamSettings['wsSettings'] ?? [];
					$vmessData['path'] = $wsSettings['path'] ?? '/';
					$vmessData['host'] = $wsSettings['headers']['Host'] ?? '';
					break;

				case 'http':
				case 'h2':
				case 'httpupgrade':
					$httpSettings = $streamSettings['httpSettings'] ?? [];
					$vmessData['path'] = $httpSettings['path'] ?? '/';
					$vmessData['host'] = implode(',', $httpSettings['host'] ?? []);
					break;

				case 'grpc':
					$grpcSettings = $streamSettings['grpcSettings'] ?? [];
					$vmessData['path'] = $grpcSettings['serviceName'] ?? '';
					$vmessData['type'] = $grpcSettings['multiMode'] ? 'multi' : 'gun';
					break;

				case 'quic':
					$quicSettings = $streamSettings['quicSettings'] ?? [];
					$vmessData['type'] = $quicSettings['header']['type'] ?? 'none';
					$vmessData['host'] = $quicSettings['security'] ?? 'none';
					$vmessData['path'] = $quicSettings['key'] ?? '';
					break;
			}

			// Handle TLS/セキュリティ
			if (in_array($security, ['tls', 'xtls', 'reality'])) {
				$tlsSettings = $streamSettings['tlsSettings'] ?? [];
				$vmessData['sni'] = $tlsSettings['serverName'] ?? '';
				$vmessData['alpn'] = implode(',', $tlsSettings['alpn'] ?? []);
				if ($security === 'reality') {
					$vmessData['fp'] = $tlsSettings['fingerprint'] ?? '';
					// pbk and sid not typically in VMess, but if needed, adjust
				}
			}

			// Filter out empty values
			$vmessData = array_filter($vmessData, function($value) {
				return $value !== '' && $value !== null;
			});

			$json = json_encode($vmessData);
			$base64 = base64_encode($json);
			return "vmess://{$base64}";
		}
		elseif ($protocol === 'shadowsocks') {
			// Generate standard Shadowsocks config with base64
			$method = $inbound['method'] ?? 'aes-256-gcm';
			$password = $client['password'] ?? $client['id'];
			$userinfo = base64_encode("{$method}:{$password}");
			$configUrl = "ss://{$userinfo}@{$this->server->ip}:{$port}#" . rawurlencode($remark);

			// Shadowsocks typically doesn't support complex stream settings like TLS, but if needed, append query
			$queryParams = [];
			if ($security !== 'none') {
				$queryParams['security'] = $security;
				// Add more if applicable, but SS usually uses plugin for TLS
			}
			if (!empty($queryParams)) {
				$queryString = http_build_query($queryParams);
				$configUrl .= '?' . $queryString;
			}

			return $configUrl;

		} else {
			// For VLESS and Trojan
			$baseUrl = sprintf(
				"%s://%s@%s:%d",
				$protocol,
				$protocol === 'trojan' ? ($client['password'] ?? $client['id']) : $client['id'],
				$this->server->ip,
				$port
			);

			$queryParams = [
				'type' => $network,
				'security' => $security
			];

			switch ($protocol) {
				case 'vless':
					$queryParams['encryption'] = $inbound['decryption'] ?? 'none';
					$queryParams['flow'] = $client['flow'] ?? '';
					break;

				case 'trojan':
					$queryParams['flow'] = $client['flow'] ?? '';
					break;
			}

			switch ($network) {
				case 'tcp':
					$tcpSettings = $streamSettings['tcpSettings'] ?? [];
					$header = $tcpSettings['header'] ?? [];
					$queryParams['headerType'] = $header['type'] ?? 'none';

					if (($header['type'] ?? '') === 'http') {
						$httpSettings = $header['request'] ?? [];
						$queryParams['host'] = implode(',', $httpSettings['headers']['Host'] ?? []);
						$path = $httpSettings['path'] ?? ['/'];
						$queryParams['path'] = implode(',', array_map(function($p) { return urlencode(urldecode($p)); }, (array)$path));
					}
					break;

				case 'kcp':
				case 'mkcp':
					$kcpSettings = $streamSettings['kcpSettings'] ?? [];
					$queryParams['headerType'] = $kcpSettings['header']['type'] ?? 'none';
					$queryParams['seed'] = $kcpSettings['seed'] ?? '';
					$queryParams['congestion'] = $kcpSettings['congestion'] ?? false;
					break;

				case 'ws':
				case 'websocket':
					$wsSettings = $streamSettings['wsSettings'] ?? [];
					$queryParams['path'] = $wsSettings['path'] ?? '/';
					$queryParams['host'] = $wsSettings['headers']['Host'] ?? '';

					if (isset($wsSettings['headers'])) {
						foreach ($wsSettings['headers'] as $key => $value) {
							if (strtolower($key) !== 'host') {
								$queryParams["ws-{$key}"] = $value;
							}
						}
					}
					break;

				case 'http':
				case 'h2':
				case 'httpupgrade':
					$httpSettings = $streamSettings['httpSettings'] ?? [];
					$path = $httpSettings['path'] ?? '/';
					$queryParams['path'] = urlencode(urldecode($path));
					$queryParams['host'] = implode(',', $httpSettings['host'] ?? []);
					break;

				case 'grpc':
					$grpcSettings = $streamSettings['grpcSettings'] ?? [];
					$queryParams['serviceName'] = $grpcSettings['serviceName'] ?? '';
					$queryParams['mode'] = $grpcSettings['multiMode'] ? 'multi' : 'gun';
					$queryParams['authority'] = $grpcSettings['authority'] ?? '';
					break;

				case 'quic':
					$quicSettings = $streamSettings['quicSettings'] ?? [];
					$queryParams['quicSecurity'] = $quicSettings['security'] ?? 'none';
					$queryParams['key'] = $quicSettings['key'] ?? '';
					$queryParams['headerType'] = $quicSettings['header']['type'] ?? 'none';
					break;

				case 'xhttp':
					$xhttpSettings = $streamSettings['xhttpSettings'] ?? [];
					$path = $xhttpSettings['path'] ?? '/';
					$queryParams['path'] = urlencode(urldecode($path));
					$queryParams['host'] = $xhttpSettings['host'] ?? '';
					break;
			}

			if (in_array($security, ['tls', 'xtls', 'reality'])) {
				if ($security === 'reality') {
					$realitySettings = $streamSettings['realitySettings']['settings'] ?? [];
					$queryParams['pbk'] = $realitySettings['publicKey'] ?? '';
					$queryParams['fp'] = $realitySettings['fingerprint'] ?? '';
					$queryParams['spx'] = $realitySettings['spiderX'] ?? '';
					$queryParams['sid'] = $streamSettings['realitySettings']['shortIds'][0] ?? '';
					$queryParams['sni'] = $streamSettings['realitySettings']['serverNames'][0] ?? '';
				} else {
					$tlsSettings = $streamSettings['tlsSettings'] ?? [];
					$queryParams['sni'] = $tlsSettings['serverName'] ?? '';
					$queryParams['alpn'] = implode(',', $tlsSettings['alpn'] ?? []);
					$queryParams['allowInsecure'] = $tlsSettings['allowInsecure'] ?? false;
				}

				if ($security === 'xtls' || $security === 'reality') {
					$queryParams['flow'] = $client['flow'] ?? '';
				}
			}

			// Remove encryption if 'none' for VLESS to match panel
			if ($protocol === 'vless' && ($queryParams['encryption'] ?? '') === 'none') {
				unset($queryParams['encryption']);
			}

			$queryString = http_build_query(array_filter($queryParams, function($value) {
				return $value !== null && $value !== false && $value !== '';
			}));

			$configUrl = $baseUrl . ($queryString ? '?' . $queryString : '') . '#' . rawurlencode($remark);

			return $configUrl;
		}
	}
}
