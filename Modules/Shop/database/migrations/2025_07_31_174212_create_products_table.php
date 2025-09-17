<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
		Schema::create('products', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->decimal('traffic_gb', 10, 3);
			$table->float('duration_days'); // به خاطر تست
			$table->unsignedBigInteger('price');
			$table->unsignedTinyInteger('user_limit')->default(1);
			$table->boolean('is_active')->default(true);
			$table->boolean('is_test')->default(false);
			$table->unsignedBigInteger('parent_id')->nullable();
			$table->timestamps();

			$table->foreign('parent_id')->references('id')->on('products')->onDelete('set null');
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
