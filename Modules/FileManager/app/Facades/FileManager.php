<?php

namespace Modules\FileManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static FileManager[] store($item, \Illuminate\Http\UploadedFile|array $files, string $directory = 'uploads', ?string $disk = null, ?array $meta = null)
 * @method static bool delete(FileManager $file)
 * @method static bool deleteForItem($item)
 *
 * @see FileManagerService
 */
class FileManager extends Facade
{
	protected static function getFacadeAccessor(): string
	{
		return 'file-manager';
	}
}
