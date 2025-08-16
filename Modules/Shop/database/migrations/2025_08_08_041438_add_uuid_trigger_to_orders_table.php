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
        \Illuminate\Support\Facades\DB::statement("DROP TRIGGER IF EXISTS `generate_uuid_before_insert`");
        \Illuminate\Support\Facades\DB::statement("CREATE TRIGGER `generate_uuid_before_insert` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
  IF NEW.uuid IS NULL OR NEW.uuid = '' THEN
    SET NEW.uuid = UUID();
  END IF;
END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("DROP TRIGGER IF EXISTS `generate_uuid_before_insert`");
    }
};
