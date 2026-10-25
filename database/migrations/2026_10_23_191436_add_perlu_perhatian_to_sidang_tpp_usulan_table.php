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
        if (Schema::hasTable('sidang_tpp_usulan')) {
            Schema::table('sidang_tpp_usulan', function (Blueprint $table) {
                if (! Schema::hasColumn('sidang_tpp_usulan', 'perlu_perhatian')) {
                    $table->boolean('perlu_perhatian')->default(false)->after('jenis_usulan');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'catatan_perhatian')) {
                    $table->text('catatan_perhatian')->nullable()->after('perlu_perhatian');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sidang_tpp_usulan')) {
            Schema::table('sidang_tpp_usulan', function (Blueprint $table) {
                if (Schema::hasColumn('sidang_tpp_usulan', 'catatan_perhatian')) {
                    $table->dropColumn('catatan_perhatian');
                }
                if (Schema::hasColumn('sidang_tpp_usulan', 'perlu_perhatian')) {
                    $table->dropColumn('perlu_perhatian');
                }
            });
        }
    }
};
