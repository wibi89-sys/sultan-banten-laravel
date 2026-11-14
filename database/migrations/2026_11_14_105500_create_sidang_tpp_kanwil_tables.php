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
        Schema::create('sidang_tpp_kanwil', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sidang', 100);
            $table->date('tanggal_sidang');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->string('tempat', 255);
            $table->enum('metode', ['luring', 'daring', 'hybrid'])->default('luring');
            $table->string('link_daring', 255)->nullable();
            $table->string('meeting_id', 100)->nullable();
            $table->string('passcode', 100)->nullable();

            // Nota Dinas Undangan (Input Manual)
            $table->string('nomor_nota_dinas', 150)->nullable();
            $table->date('tanggal_nota_dinas')->nullable();
            $table->string('file_nota_dinas', 255)->nullable();

            // Agenda Sidang
            $table->string('agenda_kategori', 100);
            $table->string('agenda_judul', 255);
            $table->text('agenda_deskripsi')->nullable();
            $table->string('file_bahan_sidang', 255)->nullable();

            // Pimpinan Sidang
            $table->unsignedBigInteger('ketua_id')->nullable();
            $table->string('ketua_nama', 150);
            $table->string('ketua_nip', 50)->nullable();
            $table->string('ketua_jabatan', 200)->nullable();

            $table->unsignedBigInteger('sekretaris_id')->nullable();
            $table->string('sekretaris_nama', 150);
            $table->string('sekretaris_nip', 50)->nullable();
            $table->string('sekretaris_jabatan', 200)->nullable();

            // Statistik Kehadiran & Rekomendasi
            $table->integer('total_anggota')->default(0);
            $table->integer('total_hadir')->default(0);
            $table->integer('total_setuju')->default(0);
            $table->integer('total_setuju_catatan')->default(0);
            $table->integer('total_tolak')->default(0);
            $table->integer('total_tunda')->default(0);
            $table->text('kesimpulan_sidang')->nullable();

            // Berita Acara & Pemberkasan Fisik
            $table->string('nomor_berita_acara', 150)->nullable();
            $table->string('file_ba_draft', 255)->nullable();
            $table->string('file_ba_signed', 255)->nullable();

            // Tanggapan / Disposisi Kakanwil (Fisik)
            $table->enum('tanggapan_kakanwil_status', ['menunggu', 'disetujui', 'disetujui_bersyarat', 'ditolak', 'perlu_pendalaman'])->default('menunggu');
            $table->text('tanggapan_kakanwil_catatan')->nullable();
            $table->date('tanggapan_kakanwil_tanggal')->nullable();
            $table->string('kakanwil_nama', 150)->nullable();
            $table->string('kakanwil_nip', 50)->nullable();
            $table->string('file_disposisi_kakanwil', 255)->nullable();

            $table->enum('status_sidang', ['draft', 'undangan_terbit', 'berlangsung', 'menunggu_kakanwil', 'selesai'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('tanggal_sidang', 'idx_tpp_kanwil_tanggal');
            $table->index('status_sidang', 'idx_tpp_kanwil_status');
        });

        Schema::create('sidang_tpp_kanwil_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_tpp_kanwil_id')->constrained('sidang_tpp_kanwil')->onDelete('cascade');
            $table->unsignedBigInteger('pegawai_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('token_akses', 64)->unique();
            $table->string('nama', 150);
            $table->string('nip', 50)->nullable();
            $table->string('jabatan', 200);
            $table->string('no_wa', 30)->nullable();
            $table->enum('peran_sidang', ['Ketua', 'Sekretaris', 'Anggota', 'PK Bapas', 'Narasumber'])->default('Anggota');

            // Presensi
            $table->enum('status_kehadiran', ['belum_konfirmasi', 'hadir_luring', 'hadir_daring', 'izin', 'sakit', 'tidak_hadir'])->default('belum_konfirmasi');
            $table->string('keterangan_kehadiran', 255)->nullable();
            $table->dateTime('waktu_kehadiran')->nullable();

            // Pendapat / Rekomendasi
            $table->enum('status_pendapat', ['belum_mengisi', 'setuju', 'setuju_catatan', 'tolak', 'tunda'])->default('belum_mengisi');
            $table->text('catatan_pendapat')->nullable();
            $table->dateTime('waktu_pendapat')->nullable();

            // Status Pengiriman WhatsApp
            $table->enum('wa_sent_status', ['pending', 'terkirim', 'gagal'])->default('pending');
            $table->dateTime('wa_sent_at')->nullable();
            $table->string('wa_error_message', 255)->nullable();

            $table->timestamps();

            $table->index('token_akses', 'idx_tpp_kanwil_token');
            $table->index(['sidang_tpp_kanwil_id', 'status_kehadiran'], 'idx_tpp_kanwil_kehadiran');
            $table->index(['sidang_tpp_kanwil_id', 'status_pendapat'], 'idx_tpp_kanwil_pendapat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidang_tpp_kanwil_anggota');
        Schema::dropIfExists('sidang_tpp_kanwil');
    }
};
