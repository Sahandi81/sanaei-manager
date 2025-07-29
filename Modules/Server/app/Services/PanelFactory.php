<?php

namespace Modules\Server\Services;


use Modules\Server\Contracts\PanelInterface;
use Modules\Server\Models\Server;

class PanelFactory
{
	public static function make(Server $server): PanelInterface
	{
		return match ($server->panel_type) {
			'sanaei' => new SanaeiPanel($server),
			// 'xui'     => new XUIPanel($server),
			default   => throw new \InvalidArgumentException("Unsupported panel type: {$server->panel_type}")
		};
	}
}
