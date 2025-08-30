<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('wallets', function (Blueprint $table) {
			$table->bigIncrements('id');

			// صاحب کیف پول: می‌تونه User/Client/… باشه
			$table->morphs('owner'); // owner_type, owner_id (اگر UUID داری → uuidMorphs)

			$table->string('currency', 10)->default('IRR');
			// همه‌چیز در واحد خرد (minor units) ذخیره شود؛ مثل ریال
			$table->unsignedBigInteger('balance_minor')->default(0);

			// 0=inactive, 1=active, 2=frozen
			$table->unsignedTinyInteger('status')->default(1);

			$table->json('meta')->nullable();

			$table->timestamps();

			// هر owner برای هر currency فقط یک کیف
			$table->unique(['owner_type', 'owner_id', 'currency'], 'wallet_owner_currency_unique');

			$table->index(['currency', 'status'], 'wallet_currency_status_idx');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('wallets');
	}
};
