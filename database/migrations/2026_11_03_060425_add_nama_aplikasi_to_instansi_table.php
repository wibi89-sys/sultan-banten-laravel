<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('instansi', 'nama_aplikasi')) {
            Schema::table('instansi', function (Blueprint $table) {
                $table->string('nama_aplikasi', 100)->nullable()->default('SULTAN BANTEN')->after('id');
            });
        }

        DB::table('instansi')
            ->whereNull('nama_aplikasi')
            ->orWhere('nama_aplikasi', '')
            ->update(['nama_aplikasi' => 'SULTAN BANTEN']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('instansi', 'nama_aplikasi')) {
            Schema::table('instansi', function (Blueprint $table) {
                $table->dropColumn('nama_aplikasi');
            });
        }
    }
};
