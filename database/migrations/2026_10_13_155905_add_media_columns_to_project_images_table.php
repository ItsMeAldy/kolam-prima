<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom media agar gambar bisa diunggah ke storage,
     * bukan hanya memakai URL eksternal.
     */
    public function up(): void
    {
        Schema::table('project_images', function (Blueprint $table) {
            $table->string('image_url')->nullable()->change();
            $table->string('path')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_images', function (Blueprint $table) {
            $table->dropColumn('path');
            $table->string('image_url')->nullable(false)->change();
        });
    }
};
