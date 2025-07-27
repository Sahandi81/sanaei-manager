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
		Schema::create('servers', function (Blueprint $table) {
			$table->id();
			$table->bigInteger('creator_id');
			$table->bigInteger('user_id')->nullable();
			$table->string('name');
			$table->string('ip');
			$table->string('location')->nullable();
			$table->enum('panel_type', ['sanaei', /* or .etc */])->default('sanaei');
			$table->string('api_url');
			$table->string('api_key')->nullable();
			$table->integer('status')->default(0);
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
