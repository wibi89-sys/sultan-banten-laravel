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
        if (Schema::hasTable('sidang_tpp')) {
            Schema::table('sidang_tpp', function (Blueprint $table) {
                if (! Schema::hasColumn('sidang_tpp', 'agenda_sidang')) {
                    $table->text('agenda_sidang')->nullable()->after('agenda_deskripsi');
                }
                if (! Schema::hasColumn('sidang_tpp', 'total_setuju')) {
                    $table->integer('total_setuju')->default(0)->after('total_usulan');
                }
                if (! Schema::hasColumn('sidang_tpp', 'total_tidak_setuju')) {
                    $table->integer('total_tidak_setuju')->default(0)->after('total_setuju');
                }
            });

            // Salin data agenda lama ke agenda_sidang baru
            DB::statement('UPDATE `sidang_tpp` SET `agenda_sidang` = COALESCE(`agenda_deskripsi`, `agenda_kategori`, "Sidang TPP") WHERE `agenda_sidang` IS NULL OR `agenda_sidang` = ""');

            // Ubah tipe kolom status_verifikasi_kanwil ke VARCHAR(50) agar fleksibel (Sesuai, Tidak Sesuai, Perbaikan)
            DB::statement('ALTER TABLE `sidang_tpp` MODIFY COLUMN `status_verifikasi_kanwil` VARCHAR(50) NOT NULL DEFAULT "menunggu_sidang"');

            // Petakan nilai lama ke nilai baru: terverifikasi -> sesuai, ditolak -> tidak_sesuai, perlu_perbaikan -> perbaikan
            DB::statement('UPDATE `sidang_tpp` SET `status_verifikasi_kanwil` = "sesuai" WHERE `status_verifikasi_kanwil` = "terverifikasi"');
            DB::statement('UPDATE `sidang_tpp` SET `status_verifikasi_kanwil` = "tidak_sesuai" WHERE `status_verifikasi_kanwil` = "ditolak"');
            DB::statement('UPDATE `sidang_tpp` SET `status_verifikasi_kanwil` = "perbaikan" WHERE `status_verifikasi_kanwil` = "perlu_perbaikan"');

            // Sinkronkan counter
            DB::statement('UPDATE `sidang_tpp` SET `total_setuju` = `total_disetujui`, `total_tidak_setuju` = `total_ditolak`');

            // Buat agenda_kategori dan agenda_deskripsi nullable jika masih ada
            DB::statement('ALTER TABLE `sidang_tpp` MODIFY COLUMN `agenda_kategori` VARCHAR(50) NULL');
            DB::statement('ALTER TABLE `sidang_tpp` MODIFY COLUMN `agenda_deskripsi` TEXT NULL');
        }

        if (Schema::hasTable('sidang_tpp_usulan')) {
            // Ubah kolom keputusan ke VARCHAR(50) untuk mendukung 'setuju', 'tidak_setuju', 'tunda', 'belum_putus'
            DB::statement('ALTER TABLE `sidang_tpp_usulan` MODIFY COLUMN `keputusan` VARCHAR(50) NOT NULL DEFAULT "belum_putus"');

            // Petakan nilai lama ke nilai baru
            DB::statement('UPDATE `sidang_tpp_usulan` SET `keputusan` = "setuju" WHERE `keputusan` = "disetujui"');
            DB::statement('UPDATE `sidang_tpp_usulan` SET `keputusan` = "tidak_setuju" WHERE `keputusan` = "ditolak"');
            DB::statement('UPDATE `sidang_tpp_usulan` SET `keputusan` = "tunda" WHERE `keputusan` = "ditunda"');

            // Ubah jenis_usulan ke VARCHAR(50) untuk mendukung 'Register F', dll
            DB::statement('ALTER TABLE `sidang_tpp_usulan` MODIFY COLUMN `jenis_usulan` VARCHAR(50) NOT NULL DEFAULT "PB"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sidang_tpp')) {
            Schema::table('sidang_tpp', function (Blueprint $table) {
                if (Schema::hasColumn('sidang_tpp', 'agenda_sidang')) {
                    $table->dropColumn('agenda_sidang');
                }
                if (Schema::hasColumn('sidang_tpp', 'total_setuju')) {
                    $table->dropColumn('total_setuju');
                }
                if (Schema::hasColumn('sidang_tpp', 'total_tidak_setuju')) {
                    $table->dropColumn('total_tidak_setuju');
                }
            });
        }
    }
};
