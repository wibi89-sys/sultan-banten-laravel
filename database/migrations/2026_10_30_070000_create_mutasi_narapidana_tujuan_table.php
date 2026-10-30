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
        if (! Schema::hasTable('mutasi_narapidana_tujuan')) {
            Schema::create('mutasi_narapidana_tujuan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mutasi_id')->constrained('mutasi_narapidana')->cascadeOnDelete();
                $table->unsignedInteger('upt_tujuan_id');
                $table->integer('jumlah_disetujui_narkotika')->default(0);
                $table->integer('jumlah_disetujui_pidum')->default(0);
                $table->integer('jumlah_disetujui_korupsi')->default(0);
                $table->integer('total_disetujui')->default(0);

                // Dokumen pelaksanaan per rute/tujuan
                $table->string('file_laporan_pemindahan', 255)->nullable();
                $table->date('tanggal_pemindahan')->nullable();
                $table->string('file_laporan_penerimaan', 255)->nullable();
                $table->date('tanggal_penerimaan')->nullable();
                $table->string('file_bast', 255)->nullable();
                $table->string('nomor_bast', 100)->nullable();
                $table->date('tanggal_bast')->nullable();
                $table->date('tanggal_pelaksanaan')->nullable();
                $table->text('catatan_pelaksanaan')->nullable();
                $table->string('status', 50)->default('disetujui'); // disetujui, selesai

                $table->timestamps();

                $table->index(['mutasi_id', 'upt_tujuan_id']);
                $table->index('upt_tujuan_id');
                $table->index('status');
            });

            try {
                Schema::table('mutasi_narapidana_tujuan', function (Blueprint $table) {
                    $table->foreign('upt_tujuan_id')->references('id')->on('upt')->onDelete('cascade');
                });
            } catch (Throwable $e) {
                // Lewati jika tipe kolom id pada tabel upt tidak identik
            }

            // Migrasi otomatis data mutasi yang telah memiliki UPT tujuan sebelumnya
            $existing = DB::table('mutasi_narapidana')->whereNotNull('upt_tujuan_id')->get();
            foreach ($existing as $m) {
                DB::table('mutasi_narapidana_tujuan')->insert([
                    'mutasi_id' => $m->id,
                    'upt_tujuan_id' => $m->upt_tujuan_id,
                    'jumlah_disetujui_narkotika' => $m->jumlah_disetujui_narkotika ?? 0,
                    'jumlah_disetujui_pidum' => $m->jumlah_disetujui_pidum ?? 0,
                    'jumlah_disetujui_korupsi' => $m->jumlah_disetujui_korupsi ?? 0,
                    'total_disetujui' => $m->total_disetujui ?? 0,
                    'file_laporan_pemindahan' => $m->file_laporan_pemindahan ?? null,
                    'tanggal_pemindahan' => $m->tanggal_pemindahan ?? null,
                    'file_laporan_penerimaan' => $m->file_laporan_penerimaan ?? null,
                    'tanggal_penerimaan' => $m->tanggal_penerimaan ?? null,
                    'file_bast' => $m->file_bast ?? null,
                    'nomor_bast' => $m->nomor_bast ?? null,
                    'tanggal_bast' => $m->tanggal_bast ?? null,
                    'tanggal_pelaksanaan' => $m->tanggal_pelaksanaan ?? null,
                    'status' => $m->status === 'selesai' ? 'selesai' : 'disetujui',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_narapidana_tujuan');
    }
};
