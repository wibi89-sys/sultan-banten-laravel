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
        // 1. Sesuaikan / Tambah kolom di tabel sidang_tpp_kanwil
        if (Schema::hasTable('sidang_tpp_kanwil')) {
            Schema::table('sidang_tpp_kanwil', function (Blueprint $table) {
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'subjek_tipe')) {
                    $table->string('subjek_tipe', 50)->default('umum')->after('agenda_deskripsi');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'mutasi_id')) {
                    $table->foreignId('mutasi_id')->nullable()->after('subjek_tipe')->constrained('mutasi_narapidana')->nullOnDelete();
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'narapidana_data')) {
                    $table->json('narapidana_data')->nullable()->after('mutasi_id');
                }
                if (! Schema::hasColumn('sidang_tpp_kanwil', 'upt_tujuan_rekomendasi_id')) {
                    $table->unsignedInteger('upt_tujuan_rekomendasi_id')->nullable()->after('narapidana_data');
                } else {
                    $table->unsignedInteger('upt_tujuan_rekomendasi_id')->nullable()->change();
                }
            });
        }

        // 2. Tambah kolom di tabel mutasi_narapidana
        if (Schema::hasTable('mutasi_narapidana')) {
            Schema::table('mutasi_narapidana', function (Blueprint $table) {
                if (! Schema::hasColumn('mutasi_narapidana', 'sidang_tpp_kanwil_id')) {
                    $table->foreignId('sidang_tpp_kanwil_id')->nullable()->after('upt_tujuan_id')->constrained('sidang_tpp_kanwil')->nullOnDelete();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'sidang_tpp_upt_id')) {
                    $table->foreignId('sidang_tpp_upt_id')->nullable()->after('sidang_tpp_kanwil_id')->constrained('sidang_tpp')->nullOnDelete();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'nomor_ba_tpp_upt')) {
                    $table->string('nomor_ba_tpp_upt', 150)->nullable()->after('file_lampiran_wbp');
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'file_ba_tpp_upt')) {
                    $table->string('file_ba_tpp_upt', 255)->nullable()->after('nomor_ba_tpp_upt');
                }
            });
        }

        // 3. Tambah kolom di tabel sidang_tpp_usulan (Sidang TPP UPT)
        if (Schema::hasTable('sidang_tpp_usulan')) {
            Schema::table('sidang_tpp_usulan', function (Blueprint $table) {
                if (! Schema::hasColumn('sidang_tpp_usulan', 'subjek_tipe')) {
                    $table->string('subjek_tipe', 50)->default('integrasi')->after('jenis_usulan');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'upt_tujuan_id')) {
                    $table->unsignedInteger('upt_tujuan_id')->nullable()->after('subjek_tipe');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'alasan_pemindahan')) {
                    $table->string('alasan_pemindahan', 255)->nullable()->after('upt_tujuan_id');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'penjamin_nama')) {
                    $table->string('penjamin_nama', 150)->nullable()->after('alasan_pemindahan');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'penjamin_hubungan')) {
                    $table->string('penjamin_hubungan', 100)->nullable()->after('penjamin_nama');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'penjamin_alamat')) {
                    $table->text('penjamin_alamat')->nullable()->after('penjamin_hubungan');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'diagnosa_medis')) {
                    $table->text('diagnosa_medis')->nullable()->after('penjamin_alamat');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'dokter_penanggung_jawab')) {
                    $table->string('dokter_penanggung_jawab', 150)->nullable()->after('diagnosa_medis');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'rs_rujukan_nama')) {
                    $table->string('rs_rujukan_nama', 200)->nullable()->after('dokter_penanggung_jawab');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'rs_rujukan_kota')) {
                    $table->string('rs_rujukan_kota', 100)->nullable()->after('rs_rujukan_nama');
                }
                if (! Schema::hasColumn('sidang_tpp_usulan', 'berkas_khusus')) {
                    $table->json('berkas_khusus')->nullable()->after('rs_rujukan_kota');
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
                $table->dropColumn([
                    'subjek_tipe',
                    'upt_tujuan_id',
                    'alasan_pemindahan',
                    'penjamin_nama',
                    'penjamin_hubungan',
                    'penjamin_alamat',
                    'diagnosa_medis',
                    'dokter_penanggung_jawab',
                    'rs_rujukan_nama',
                    'rs_rujukan_kota',
                    'berkas_khusus',
                ]);
            });
        }

        if (Schema::hasTable('mutasi_narapidana')) {
            Schema::table('mutasi_narapidana', function (Blueprint $table) {
                if (Schema::hasColumn('mutasi_narapidana', 'sidang_tpp_kanwil_id')) {
                    $table->dropForeign(['sidang_tpp_kanwil_id']);
                }
                if (Schema::hasColumn('mutasi_narapidana', 'sidang_tpp_upt_id')) {
                    $table->dropForeign(['sidang_tpp_upt_id']);
                }
                $table->dropColumn([
                    'sidang_tpp_kanwil_id',
                    'sidang_tpp_upt_id',
                    'nomor_ba_tpp_upt',
                    'file_ba_tpp_upt',
                ]);
            });
        }

        if (Schema::hasTable('sidang_tpp_kanwil')) {
            Schema::table('sidang_tpp_kanwil', function (Blueprint $table) {
                if (Schema::hasColumn('sidang_tpp_kanwil', 'mutasi_id')) {
                    $table->dropForeign(['mutasi_id']);
                }
                $table->dropColumn([
                    'subjek_tipe',
                    'mutasi_id',
                    'narapidana_data',
                    'upt_tujuan_rekomendasi_id',
                ]);
            });
        }
    }
};
