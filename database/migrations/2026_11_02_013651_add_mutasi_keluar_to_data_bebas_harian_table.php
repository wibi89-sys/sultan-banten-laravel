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
        Schema::table('data_bebas_harian', function (Blueprint $table) {
            if (! Schema::hasColumn('data_bebas_harian', 'mutasi_keluar')) {
                $table->integer('mutasi_keluar')->default(0)->nullable()->after('bdh');
            }
            if (! Schema::hasColumn('data_bebas_harian', 'ket_mutasi')) {
                $table->string('ket_mutasi', 255)->nullable()->after('mutasi_keluar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_bebas_harian', function (Blueprint $table) {
            if (Schema::hasColumn('data_bebas_harian', 'ket_mutasi')) {
                $table->dropColumn('ket_mutasi');
            }
            if (Schema::hasColumn('data_bebas_harian', 'mutasi_keluar')) {
                $table->dropColumn('mutasi_keluar');
            }
        });
    }
};
