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
        Schema::table('sidang_tpp', function (Blueprint $table) {
            if (! Schema::hasColumn('sidang_tpp', 'nomor_surat_tugas')) {
                $table->string('nomor_surat_tugas', 150)->nullable()->after('file_undangan');
            }
            if (! Schema::hasColumn('sidang_tpp', 'tanggal_surat_tugas')) {
                $table->date('tanggal_surat_tugas')->nullable()->after('nomor_surat_tugas');
            }
            if (! Schema::hasColumn('sidang_tpp', 'file_surat_tugas')) {
                $table->string('file_surat_tugas', 255)->nullable()->after('tanggal_surat_tugas');
            }
        });

        if (Schema::hasTable('tpp_naskah_kanwil')) {
            DB::statement('ALTER TABLE `tpp_naskah_kanwil` MODIFY COLUMN `jenis_dokumen` VARCHAR(50) NOT NULL DEFAULT "nota_dinas"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidang_tpp', function (Blueprint $table) {
            if (Schema::hasColumn('sidang_tpp', 'file_surat_tugas')) {
                $table->dropColumn('file_surat_tugas');
            }
            if (Schema::hasColumn('sidang_tpp', 'tanggal_surat_tugas')) {
                $table->dropColumn('tanggal_surat_tugas');
            }
            if (Schema::hasColumn('sidang_tpp', 'nomor_surat_tugas')) {
                $table->dropColumn('nomor_surat_tugas');
            }
        });
    }
};
