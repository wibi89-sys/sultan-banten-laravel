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
        Schema::table('sidang_tpp_kanwil', function (Blueprint $table) {
            $table->json('dokumen_custom')->nullable()->after('klien_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidang_tpp_kanwil', function (Blueprint $table) {
            $table->dropColumn('dokumen_custom');
        });
    }
};
