<?php

namespace Modules\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class FileManager extends Model
{

	protected $fillable = [
		'item_type',
		'item_id',
		'filepath',
		'original_name',
		'mime_type',
		'size',
		'disk',
		'directory',
		'extension',
		'meta',
	];

	protected $casts = [
		'meta' => 'array',
	];

	public function item()
	{
		return $this->morphTo();
	}

	public function getFullPathAttribute()
	{
		return $this->disk === 'local'
			? storage_path('app/'.$this->filepath)
			: $this->filepath;
	}

	public function getUrlAttribute()
	{
		return $this->disk === 'public'
			? asset('storage/'.ltrim($this->filepath, 'public/'))
			: Storage::disk($this->disk)->url($this->filepath);
	}
}
