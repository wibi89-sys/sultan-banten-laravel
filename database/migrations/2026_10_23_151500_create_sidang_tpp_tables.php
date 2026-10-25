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
        if (! Schema::hasTable('sidang_tpp')) {
            Schema::create('sidang_tpp', function (Blueprint $table) {
                $table->id();
                $table->integer('upt_id')->index('idx_sidang_upt');

                // 1. Undangan ke Kantor Wilayah
                $table->string('nomor_undangan', 150);
                $table->date('tanggal_surat');
                $table->date('tanggal_sidang')->index('idx_sidang_tanggal');
                $table->time('jam_mulai');
                $table->time('jam_selesai')->nullable();
                $table->string('file_undangan', 255)->nullable();
                $table->enum('agenda_kategori', ['hak_integrasi', 'asimilasi', 'remisi', 'evaluasi_tamping', 'lainnya'])->default('hak_integrasi');
                $table->text('agenda_deskripsi');

                // 2. Link TPP Daring (Fasilitasi Supervisi Kanwil)
                $table->enum('metode_sidang', ['tatap_muka', 'daring', 'hybrid'])->default('hybrid');
                $table->text('link_daring')->nullable();
                $table->string('meeting_id', 100)->nullable();
                $table->string('passcode', 100)->nullable();

                // Personel Sidang TPP UPT
                $table->string('ketua_tpp', 150);
                $table->string('sekretaris_tpp', 150);
                $table->string('nama_pk_bapas', 150)->nullable();
                $table->json('anggota_tpp')->nullable();

                // 3. Dokumen Hasil Sidang dari UPT
                $table->string('nomor_berita_acara', 150)->nullable();
                $table->string('file_berita_acara', 255)->nullable();
                $table->text('file_dokumentasi')->nullable()->comment('Daftar file foto dokumentasi sidang JPG kompresi');

                // 4. Verifikasi & Dokumen Resmi Bertanda Tangan Ka. Kanwil (SE PAS-38.OT.02.02 Tahun 2026)
                $table->boolean('kanwil_hadir')->default(false);
                $table->string('nama_pengawas_kanwil', 150)->nullable();
                $table->text('catatan_bintorwasdal')->nullable();
                $table->string('nomor_laporan_kanwil', 150)->nullable();
                $table->date('tanggal_ttd_kakanwil')->nullable();
                $table->string('file_laporan_kakanwil', 255)->nullable();
                $table->enum('status_verifikasi_kanwil', [
                    'menunggu_sidang',
                    'menunggu_verifikasi',
                    'terverifikasi',
                    'perlu_perbaikan',
                    'ditolak',
                ])->default('menunggu_sidang')->index('idx_sidang_verifikasi');
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->dateTime('verified_at')->nullable();

                // Ringkasan Penghitungan Keputusan
                $table->integer('total_usulan')->default(0);
                $table->integer('total_disetujui')->default(0);
                $table->integer('total_ditunda')->default(0);
                $table->integer('total_ditolak')->default(0);

                // Status Siklus Sidang
                $table->enum('status_sidang', [
                    'dijadwalkan',
                    'menunggu_hasil_upt',
                    'menunggu_verifikasi_kanwil',
                    'selesai_ttd_kakanwil',
                    'dibatalkan',
                ])->default('dijadwalkan')->index('idx_sidang_status');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('upt_id')->references('id')->on('upt')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('sidang_tpp_usulan')) {
            Schema::create('sidang_tpp_usulan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sidang_tpp_id')->index('idx_usulan_sidang');
                $table->string('nama_wbp', 150);
                $table->string('nomor_register', 100);
                $table->string('perkara_pasal', 150);
                $table->string('lama_pidana', 100);
                $table->date('tanggal_ekspirasi')->nullable();
                $table->date('tanggal_setengah_pidana')->nullable();
                $table->date('tanggal_dua_pertiga')->nullable();
                $table->enum('jenis_usulan', ['PB', 'CB', 'CMB', 'Asimilasi', 'Remisi', 'Tamping', 'Lainnya'])->default('PB');

                // Syarat Substantif & Administratif
                $table->decimal('skor_sppn', 5, 2)->nullable();
                $table->enum('status_litmas', ['memenuhi_syarat', 'tidak_memenuhi_syarat', 'proses', 'tidak_perlu'])->default('memenuhi_syarat');
                $table->boolean('bebas_register_f')->default(true);

                // Hasil Putusan Sidang
                $table->enum('keputusan', ['disetujui', 'ditunda', 'ditolak', 'belum_putus'])->default('belum_putus');
                $table->text('alasan_keterangan')->nullable();

                $table->timestamps();

                $table->foreign('sidang_tpp_id')->references('id')->on('sidang_tpp')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidang_tpp_usulan');
        Schema::dropIfExists('sidang_tpp');
    }
};
