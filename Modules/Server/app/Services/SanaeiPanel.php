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
					'api_response' 	=> $data
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
		// Normalize: decode JSON strings if needed
		$inboundSettings = $inbound['settings'] ?? [];
		if (is_string($inboundSettings)) {
			$inboundSettings = json_decode($inboundSettings, true) ?: [];
		}

		if (is_string($streamSettings)) {
			$streamSettings = json_decode($streamSettings, true) ?: [];
		}

		$protocol = strtolower($inbound['protocol'] ?? 'vless');
		$port     = (int)($inbound['port'] ?? 0);
		$remark   = trim(($inbound['remark'] ?? '') . ' ' . ($client['email'] ?? ''));
		$network  = strtolower($streamSettings['network'] ?? 'tcp');
		$security = strtolower($streamSettings['security'] ?? 'none');

		// ✅ host resolution (fix): prefer externalProxy.dest, else TLS/REALITY serverName, else server IP
		$dest = $this->resolveDestination($streamSettings, $security);

		if ($protocol === 'vmess') {
			// Build VMess JSON then base64
			$vmess = [
				'v'   => '2',
				'ps'  => $remark,
				'add' => $dest,
				'port'=> $port,
				'id'  => $client['id'] ?? '',
				'aid' => $client['alterId'] ?? 0,
				'scy' => $client['security'] ?? 'auto',
				'net' => $network,
				'type'=> 'none',
				'host'=> '',
				'path'=> '',
				'tls' => $security,
				'sni' => '',
				'alpn'=> '',
				'fp'  => '',
			];

			switch ($network) {
				case 'tcp':
					$tcp = $streamSettings['tcpSettings'] ?? [];
					$hdr = $tcp['header'] ?? [];
					$vmess['type'] = $hdr['type'] ?? 'none';
					if (($hdr['type'] ?? '') === 'http') {
						$req = $hdr['request'] ?? [];
						$vmess['host'] = implode(',', $req['headers']['Host'] ?? []);
						$vmess['path'] = implode(',', $req['path'] ?? ['/']);
					}
					break;

				case 'kcp':
				case 'mkcp':
					$kcp = $streamSettings['kcpSettings'] ?? [];
					$vmess['type'] = $kcp['header']['type'] ?? 'none';
					break;

				case 'ws':
				case 'websocket':
					$ws = $streamSettings['wsSettings'] ?? [];
					$vmess['path'] = $ws['path'] ?? '/';
					$vmess['host'] = $ws['headers']['Host'] ?? '';
					break;

				case 'http':
				case 'h2':
				case 'httpupgrade':
					$h2 = $streamSettings['httpSettings'] ?? [];
					$vmess['path'] = $h2['path'] ?? '/';
					$vmess['host'] = implode(',', $h2['host'] ?? []);
					break;

				case 'grpc':
					$grpc = $streamSettings['grpcSettings'] ?? [];
					$vmess['path'] = $grpc['serviceName'] ?? '';
					$vmess['type'] = !empty($grpc['multiMode']) ? 'multi' : 'gun';
					break;

				case 'quic':
					$quic = $streamSettings['quicSettings'] ?? [];
					$vmess['type'] = $quic['header']['type'] ?? 'none';
					$vmess['host'] = $quic['security'] ?? 'none';
					$vmess['path'] = $quic['key'] ?? '';
					break;
			}

			if (in_array($security, ['tls','xtls','reality'], true)) {
				$tls = $streamSettings['tlsSettings'] ?? [];
				$vmess['sni']  = $tls['serverName'] ?? '';
				$vmess['alpn'] = implode(',', $tls['alpn'] ?? []);
				if ($security === 'reality') {
					$vmess['fp'] = $tls['fingerprint'] ?? '';
				}
			}

			// Filter only truly empty values (keep 0/false)
			$vmess = array_filter($vmess, static fn($v) => $v !== '' && $v !== null);
			return 'vmess://' . base64_encode(json_encode($vmess));
		}

		if ($protocol === 'shadowsocks') {
			$method   = $inbound['method'] ?? 'aes-256-gcm';
			$password = $client['password'] ?? ($client['id'] ?? '');
			$userinfo = base64_encode("{$method}:{$password}");

			$url = "ss://{$userinfo}@{$dest}:{$port}#" . rawurlencode($remark);

			// (SS usually via plugins; keep minimal)
			$q = [];
			if ($security !== 'none') {
				$q['security'] = $security;
			}
			if (!empty($q)) {
				$url .= '?' . http_build_query($q);
			}
			return $url;
		}

		// VLESS / Trojan
		$userPart = $protocol === 'trojan'
			? ($client['password'] ?? ($client['id'] ?? ''))
			: ($client['id'] ?? '');

		$baseUrl = sprintf('%s://%s@%s:%d', $protocol, $userPart, $dest, $port);

		$q = [
			'type'     => $network,
			'security' => $security,
		];

		if ($protocol === 'vless') {
			// NOTE: decryption/encryption is inside inbound.settings
			$q['encryption'] = $inboundSettings['decryption'] ?? 'none';
			$q['flow']       = $client['flow'] ?? '';
		} elseif ($protocol === 'trojan') {
			$q['flow']       = $client['flow'] ?? '';
		}

		switch ($network) {
			case 'tcp':
				$tcp = $streamSettings['tcpSettings'] ?? [];
				$hdr = $tcp['header'] ?? [];
				$q['headerType'] = $hdr['type'] ?? 'none';
				if (($hdr['type'] ?? '') === 'http') {
					$req = $hdr['request'] ?? [];
					$q['host'] = implode(',', $req['headers']['Host'] ?? []);
					$paths = $req['path'] ?? ['/'];
					$q['path'] = implode(',', array_map(
						static fn($p) => urlencode(urldecode($p)),
						(array)$paths
					));
				}
				break;

			case 'kcp':
			case 'mkcp':
				$kcp = $streamSettings['kcpSettings'] ?? [];
				$q['headerType'] = $kcp['header']['type'] ?? 'none';
				$q['seed']       = $kcp['seed'] ?? '';
				$q['congestion'] = $kcp['congestion'] ?? false;
				break;

			case 'ws':
			case 'websocket':
				$ws = $streamSettings['wsSettings'] ?? [];
				$q['path'] = $ws['path'] ?? '/';
				$q['host'] = $ws['headers']['Host'] ?? '';
				if (!empty($ws['headers'])) {
					foreach ($ws['headers'] as $k => $v) {
						if (strtolower($k) !== 'host') {
							$q["ws-{$k}"] = $v;
						}
					}
				}
				break;

			case 'http':
			case 'h2':
			case 'httpupgrade':
				$h2 = $streamSettings['httpSettings'] ?? [];
				$q['path'] = urlencode(urldecode($h2['path'] ?? '/'));
				$q['host'] = implode(',', $h2['host'] ?? []);
				break;

			case 'grpc':
				$grpc = $streamSettings['grpcSettings'] ?? [];
				$q['serviceName'] = $grpc['serviceName'] ?? '';
				$q['mode']        = !empty($grpc['multiMode']) ? 'multi' : 'gun';
				$q['authority']   = $grpc['authority'] ?? '';
				break;

			case 'quic':
				$quic = $streamSettings['quicSettings'] ?? [];
				$q['quicSecurity'] = $quic['security'] ?? 'none';
				$q['key']          = $quic['key'] ?? '';
				$q['headerType']   = $quic['header']['type'] ?? 'none';
				break;

			case 'xhttp':
				$x = $streamSettings['xhttpSettings'] ?? [];
				$q['path'] = urlencode(urldecode($x['path'] ?? '/'));
				$q['host'] = $x['host'] ?? '';
				break;
		}

		if (in_array($security, ['tls','xtls','reality'], true)) {
			if ($security === 'reality') {
				$reality = $streamSettings['realitySettings'] ?? [];
				$settings = $reality['settings'] ?? [];
				$q['pbk'] = $settings['publicKey'] ?? '';
				$q['fp']  = $settings['fingerprint'] ?? '';
				$q['spx'] = $settings['spiderX'] ?? '';
				$q['sid'] = ($reality['shortIds'][0] ?? '');
				$q['sni'] = ($reality['serverNames'][0] ?? '');
			} else {
				$tls = $streamSettings['tlsSettings'] ?? [];
				$q['sni']           = $tls['serverName'] ?? '';
				$q['alpn']          = implode(',', $tls['alpn'] ?? []);
				$q['allowInsecure'] = $tls['settings']['allowInsecure'] ?? ($tls['allowInsecure'] ?? false);
			}

			if (in_array($security, ['xtls','reality'], true)) {
				$q['flow'] = $client['flow'] ?? '';
			}
		}

		// For VLESS: remove explicit "encryption=none" to match common practice
		if ($protocol === 'vless' && ($q['encryption'] ?? '') === 'none') {
			unset($q['encryption']);
		}

		$query = http_build_query(array_filter($q, static fn($v) => $v !== '' && $v !== null && $v !== false));
		return $baseUrl . ($query ? "?{$query}" : '') . '#' . rawurlencode($remark);
	}

	/**
	 * Prefer externalProxy.dest → TLS/REALITY serverName → server IP
	 */
	protected function resolveDestination(array $streamSettings, string $security): string
	{
		$externalProxy = $streamSettings['externalProxy'] ?? [];
		if (is_array($externalProxy) && !empty($externalProxy)) {
			$last = end($externalProxy);
			$dest = $last['dest'] ?? '';
			if (!empty($dest)) {
				return $dest;
			}
		}

		if (in_array($security, ['tls','xtls','reality'], true)) {
			if ($security === 'reality') {
				$reality = $streamSettings['realitySettings'] ?? [];
				$serverNames = $reality['serverNames'] ?? [];
				if (!empty($serverNames[0])) {
					return $this->server->ip;
				}
			}

			$tls = $streamSettings['tlsSettings'] ?? [];
			if (!empty($tls['serverName'])) {
				return $tls['serverName'];
			}
		}

		return $this->server->ip;
	}
}
