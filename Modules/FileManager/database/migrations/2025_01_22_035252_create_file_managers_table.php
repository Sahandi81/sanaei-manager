<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('file_managers', function (Blueprint $table) {
			$table->id();
			$table->string('item_type')->index();
			$table->bigInteger('item_id')->index();
			$table->string('filepath');
			$table->string('original_name');
			$table->string('mime_type');
			$table->unsignedBigInteger('size')->default(0);
			$table->string('disk')->default('public');
			$table->string('directory');
			$table->string('extension');
			$table->json('meta')->nullable();
			$table->timestamps();

			$table->index(['item_type', 'item_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('file_managers');
	}
};
