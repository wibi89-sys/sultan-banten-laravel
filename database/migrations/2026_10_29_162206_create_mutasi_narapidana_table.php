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
        if (! Schema::hasTable('mutasi_narapidana')) {
            Schema::create('mutasi_narapidana', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_surat', 100);
                $table->date('tanggal_surat');
                $table->unsignedInteger('upt_asal_id');
                $table->enum('kategori_mutasi', ['Pembinaan', 'Keamanan', 'Overkapasitas', 'Lainnya'])->default('Pembinaan');
                $table->text('keterangan')->nullable();

                // Pendataan jumlah perkara
                $table->integer('jumlah_narkotika')->default(0);
                $table->integer('jumlah_pidum')->default(0);
                $table->integer('jumlah_korupsi')->default(0);
                $table->integer('total_usulan')->default(0);

                // Berkas pengajuan UPT
                $table->string('file_surat_usulan', 255)->nullable();
                $table->string('file_asesmen', 255)->nullable();
                $table->string('file_bap', 255)->nullable();
                $table->string('file_reg_f', 255)->nullable();
                $table->string('file_lampiran_wbp', 255)->nullable();

                // Status alur
                $table->string('status', 50)->default('diajukan'); // diajukan, disetujui, ditolak, perlu_perbaikan, selesai

                // Keputusan Kanwil & Penentuan UPT Tujuan
                $table->unsignedInteger('upt_tujuan_id')->nullable();
                $table->string('nomor_surat_kanwil', 100)->nullable();
                $table->date('tanggal_surat_kanwil')->nullable();
                $table->string('file_persetujuan_kanwil', 255)->nullable();
                $table->text('catatan_kanwil')->nullable();
                $table->integer('jumlah_disetujui_narkotika')->nullable();
                $table->integer('jumlah_disetujui_pidum')->nullable();
                $table->integer('jumlah_disetujui_korupsi')->nullable();
                $table->integer('total_disetujui')->nullable();
                $table->unsignedBigInteger('diverifikasi_oleh')->nullable();
                $table->timestamp('tanggal_verifikasi')->nullable();

                // Laporan Pemindahan (UPT Pengusul/Asal)
                $table->string('file_laporan_pemindahan', 255)->nullable();
                $table->date('tanggal_pemindahan')->nullable();
                $table->text('catatan_pemindahan')->nullable();

                // Laporan Penerimaan (UPT Penerima/Tujuan)
                $table->string('file_laporan_penerimaan', 255)->nullable();
                $table->date('tanggal_penerimaan')->nullable();
                $table->text('catatan_penerimaan')->nullable();

                // Berita Acara Serah Terima (BAST Bersama)
                $table->string('nomor_bast', 100)->nullable();
                $table->date('tanggal_bast')->nullable();
                $table->date('tanggal_pelaksanaan')->nullable();
                $table->string('file_bast', 255)->nullable();
                $table->text('catatan_pelaksanaan')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['status', 'tanggal_surat']);
                $table->index('upt_asal_id');
                $table->index('upt_tujuan_id');
            });

            try {
                Schema::table('mutasi_narapidana', function (Blueprint $table) {
                    $table->foreign('upt_asal_id')->references('id')->on('upt')->onDelete('cascade');
                    $table->foreign('upt_tujuan_id')->references('id')->on('upt')->onDelete('set null');
                    $table->foreign('diverifikasi_oleh')->references('id')->on('users')->onDelete('set null');
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                });
            } catch (Throwable $e) {
                // Ignore constraint error if existing tables lack compatible indexes
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_narapidana');
    }
};
