<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
			$table->id();
			$table->string('role_key')->index();
			$table->unique('role_key');
			$table->string('title');
			$table->boolean('full_access')->default(0);
			$table->boolean('is_admin')->default(0);
			$table->integer('modified_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
};

// -- ----------------------------
//-- Table structure for permission
//-- ----------------------------
//DROP TABLE IF EXISTS `permission`;
//CREATE TABLE `permission`  (
//  `id` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
//  `parent` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
//  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
//  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
//  `method` enum('GET','POST','PUT','DELETE') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
//  `created_at` datetime NULL DEFAULT NULL,
//  `updated_at` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
//  PRIMARY KEY (`id`) USING BTREE,
//  INDEX `parent`(`parent`) USING BTREE
//) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;
//
//-- ----------------------------
//-- Records of permission
//-- ----------------------------
