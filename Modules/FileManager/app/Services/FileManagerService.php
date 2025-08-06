<?php

namespace Modules\FileManager\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\FileManager\Models\FileManager;

class FileManagerService
{
	protected string $defaultDisk = 'public';

	public function store(
		$item,
		UploadedFile|array $files,
		string $directory = 'uploads',
		?string $disk = null,
		?array $meta = null
	): array {
		if (!is_array($files)) {
			$files = [$files];
		}

		$disk = $disk ?? $this->defaultDisk;
		$storedFiles = [];

		foreach ($files as $file) {
			if (!$file->isValid()) {
				continue;
			}

			$originalName = $file->getClientOriginalName();
			$extension = $file->getClientOriginalExtension();
			$mimeType = $file->getMimeType();
			$size = $file->getSize();

			// Generate unique filename
			$filename = $this->generateFilename($originalName, $extension);
			$path = $file->storeAs($directory, $filename, ['disk' => $disk]);

			$fileManager = FileManager::create([
				'item_type' => get_class($item),
				'item_id' => $item->id,
				'filepath' => $path,
				'original_name' => $originalName,
				'mime_type' => $mimeType,
				'size' => $size,
				'disk' => $disk,
				'directory' => $directory,
				'extension' => $extension,
				'meta' => $meta,
			]);

			$storedFiles[] = $fileManager;
		}

		return $storedFiles;
	}

	public function delete(FileManager $file): bool
	{
		Storage::disk($file->disk)->delete($file->filepath);
		return $file->delete();
	}

	public function deleteForItem($item): bool
	{
		$files = FileManager::where('item_type', get_class($item))
			->where('item_id', $item->id)
			->get();

		foreach ($files as $file) {
			$this->delete($file);
		}

		return true;
	}

	protected function generateFilename(string $originalName, string $extension): string
	{
		$name = pathinfo($originalName, PATHINFO_FILENAME);
		$slug = Str::slug($name);
		return $slug . '-' . uniqid() . '.' . $extension;
	}
}
