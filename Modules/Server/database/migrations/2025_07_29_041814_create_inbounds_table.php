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
		Schema::create('inbounds', function (Blueprint $table) {
			$table->id();
			$table->foreignId('server_id')->constrained()->cascadeOnDelete();
			$table->unsignedBigInteger('panel_inbound_id'); // ID داخل پنل Sanaei
			$table->integer('port');
			$table->string('protocol');
			$table->string('stream')->nullable(); // ws, grpc, tcp...
			$table->bigInteger('up')->default(0);
			$table->bigInteger('down')->default(0);
			$table->bigInteger('total')->default(0);
			$table->boolean('enable')->default(true);
			$table->string('remark')->nullable(); // یک اسم توضیحی
			$table->json('raw')->nullable(); // JSON کامل برای fallback
			$table->timestamps();

			$table->unique(['server_id', 'panel_inbound_id']);
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbounds');
    }
};
