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
        // 1. Tambah no_wa dan peran_tpp pada kanwil_pegawai
        if (Schema::hasTable('kanwil_pegawai')) {
            Schema::table('kanwil_pegawai', function (Blueprint $table) {
                if (! Schema::hasColumn('kanwil_pegawai', 'no_wa')) {
                    $table->string('no_wa', 30)->nullable()->after('jabatan');
                }
                if (! Schema::hasColumn('kanwil_pegawai', 'peran_tpp')) {
                    $table->enum('peran_tpp', ['ketua', 'sekretaris', 'anggota'])->default('anggota')->after('no_wa');
                }
            });
        }

        // 2. Tambah kolom khusus Klien Bapas, nomor naskah, dan foto dokumentasi pada sidang_tpp_kanwil
        if (Schema::hasTable('sidang_tpp_kanwil')) {
            Schema::table('sidang_tpp_kanwil', function (Blueprint $table) {
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'is_klien_bapas')) {
                    $table->boolean('is_klien_bapas')->default(false)->after('agenda_deskripsi');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'klien_data')) {
                    $table->json('klien_data')->nullable()->after('is_klien_bapas');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'nomor_risalah')) {
                    $table->string('nomor_risalah', 100)->nullable()->after('nomor_berita_acara');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'nomor_laporan')) {
                    $table->string('nomor_laporan', 100)->nullable()->after('nomor_risalah');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'foto_dokumentasi_1')) {
                    $table->string('foto_dokumentasi_1', 255)->nullable()->after('file_ba_signed');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'foto_dokumentasi_2')) {
                    $table->string('foto_dokumentasi_2', 255)->nullable()->after('foto_dokumentasi_1');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'file_risalah_signed')) {
                    $table->string('file_risalah_signed', 255)->nullable()->after('foto_dokumentasi_2');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'file_laporan_signed')) {
                    $table->string('file_laporan_signed', 255)->nullable()->after('file_risalah_signed');
                }
            });
        }

        // 3. Buat tabel tpp_kanwil_setting
        if (! Schema::hasTable('tpp_kanwil_setting')) {
            Schema::create('tpp_kanwil_setting', function (Blueprint $table) {
                $table->id();
                $table->string('kakanwil_nama', 150)->nullable();
                $table->string('kakanwil_nip', 50)->nullable();
                $table->string('kakanwil_pangkat', 100)->nullable();
                $table->string('kakanwil_jabatan', 150)->default('Kepala Kantor Wilayah');
                $table->string('kakanwil_ttd_image', 255)->nullable();
                $table->string('kanwil_stempel_image', 255)->nullable();
                $table->boolean('auto_embed_ttd')->default(true);
                $table->string('nomor_nota_prefix', 50)->default('WP.12.UM.01.01-');
                $table->string('nomor_risalah_prefix', 50)->default('WP.12.PK.06.05-');
                $table->string('nomor_laporan_prefix', 50)->default('WP.12.PK.06.06-');
                $table->string('tempat_sidang_default', 255)->default('Ruang Rapat Divisi Pemasyarakatan Kantor Wilayah Banten');
                $table->string('link_daring_default', 255)->nullable();
                $table->string('meeting_id_default', 100)->nullable();
                $table->string('passcode_default', 50)->nullable();
                $table->json('dasar_hukum_json')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpp_kanwil_setting');

        if (Schema::hasTable('sidang_tpp_kanwil')) {
            Schema::table('sidang_tpp_kanwil', function (Blueprint $table) {
                $cols = ['is_klien_bapas', 'klien_data', 'nomor_risalah', 'nomor_laporan', 'foto_dokumentasi_1', 'foto_dokumentasi_2', 'file_risalah_signed', 'file_laporan_signed'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('sidang_tpp_kanwil', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('kanwil_pegawai')) {
            Schema::table('kanwil_pegawai', function (Blueprint $table) {
                if (Schema::hasColumn('kanwil_pegawai', 'no_wa')) {
                    $table->dropColumn('no_wa');
                }
                if (Schema::hasColumn('kanwil_pegawai', 'peran_tpp')) {
                    $table->dropColumn('peran_tpp');
                }
            });
        }
    }
};
