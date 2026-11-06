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
        Schema::create('tpp_naskah_kanwil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_tpp_id')->constrained('sidang_tpp')->onDelete('cascade');
            $table->enum('jenis_dokumen', ['nota_dinas', 'laporan_tpp'])->default('nota_dinas');
            $table->string('nomor_dokumen', 150)->nullable();
            $table->date('tanggal_dokumen')->nullable();
            $table->string('judul_naskah', 255)->nullable();
            $table->text('materi_agenda')->nullable();
            $table->json('konten_data')->nullable();

            // Pihak Pengawas / Perwakilan Kanwil
            $table->string('pengawas_nama', 150)->nullable();
            $table->string('pengawas_nip', 50)->nullable();
            $table->string('pengawas_jabatan', 150)->nullable();

            // Pihak Penandatangan (Kakanwil / Kadivpas / Kabid)
            $table->string('penandatangan_nama', 150)->nullable();
            $table->string('penandatangan_nip', 50)->nullable();
            $table->string('penandatangan_jabatan', 150)->nullable();
            $table->boolean('tampilkan_nip')->default(false);

            // Otentifikasi Dokumen
            $table->enum('metode_ttd', ['manual', 'digital_qr'])->default('digital_qr');
            $table->string('kode_verifikasi', 64)->nullable()->unique();

            // Berkas Terkait
            $table->string('file_word', 255)->nullable();
            $table->string('file_pdf', 255)->nullable();
            $table->string('file_signed', 255)->nullable();

            // Status Siklus
            $table->enum('status', ['draf', 'generated', 'signed', 'verified'])->default('draf');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();

            $table->index(['sidang_tpp_id', 'jenis_dokumen'], 'idx_naskah_sidang_jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpp_naskah_kanwil');
    }
};
