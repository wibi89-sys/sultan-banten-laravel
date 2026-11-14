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
        Schema::create('sidang_tpp_kanwil_materi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sidang_tpp_kanwil_id');
            $table->unsignedInteger('urutan')->default(1);
            $table->string('kategori_materi', 50)->default('lainnya'); // mutasi_narapidana, izin_berobat, klien_bapas, lainnya
            $table->string('judul_materi', 255);
            $table->text('deskripsi_materi')->nullable();
            $table->unsignedBigInteger('mutasi_id')->nullable();
            $table->integer('upt_tujuan_rekomendasi_id')->nullable();
            $table->json('narapidana_data')->nullable();
            $table->json('klien_data')->nullable();
            $table->string('file_bahan_sidang', 255)->nullable();
            $table->json('dokumen_tambahan')->nullable();
            $table->string('rekomendasi_sidang', 50)->nullable(); // disetujui, disetujui_bersyarat, ditolak, perlu_pendalaman
            $table->text('catatan_rekomendasi')->nullable();
            $table->timestamps();

            $table->foreign('sidang_tpp_kanwil_id', 'fk_tpp_materi_sidang')
                ->references('id')->on('sidang_tpp_kanwil')->onDelete('cascade');
            $table->foreign('mutasi_id', 'fk_tpp_materi_mutasi')
                ->references('id')->on('mutasi_narapidana')->nullOnDelete();

            $table->index(['sidang_tpp_kanwil_id', 'urutan']);
            $table->index('kategori_materi');
            $table->index('upt_tujuan_rekomendasi_id');
        });

        Schema::create('sidang_tpp_kanwil_materi_pendapat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sidang_tpp_kanwil_materi_id');
            $table->unsignedBigInteger('sidang_tpp_kanwil_anggota_id');
            $table->enum('status_pendapat', ['belum_mengisi', 'setuju', 'setuju_catatan', 'tolak', 'tunda'])->default('belum_mengisi');
            $table->text('catatan_pendapat')->nullable();
            $table->timestamps();

            $table->foreign('sidang_tpp_kanwil_materi_id', 'fk_materi_pendapat_m')
                ->references('id')->on('sidang_tpp_kanwil_materi')->onDelete('cascade');
            $table->foreign('sidang_tpp_kanwil_anggota_id', 'fk_materi_pendapat_a')
                ->references('id')->on('sidang_tpp_kanwil_anggota')->onDelete('cascade');

            $table->unique(['sidang_tpp_kanwil_materi_id', 'sidang_tpp_kanwil_anggota_id'], 'uq_materi_anggota_pendapat');
        });

        // Migrasi data lama dari tabel sidang_tpp_kanwil ke sidang_tpp_kanwil_materi (Backfill)
        $sidangs = DB::table('sidang_tpp_kanwil')->get();
        foreach ($sidangs as $sidang) {
            $kategori = 'lainnya';
            if (! empty($sidang->subjek_tipe)) {
                $kategori = match ($sidang->subjek_tipe) {
                    'mutasi_narapidana' => 'mutasi_narapidana',
                    'izin_berobat' => 'izin_berobat',
                    'klien_umroh' => 'klien_bapas',
                    default => 'lainnya',
                };
            } elseif (! empty($sidang->agenda_kategori)) {
                $catLower = strtolower($sidang->agenda_kategori);
                if (str_contains($catLower, 'mutasi')) {
                    $kategori = 'mutasi_narapidana';
                } elseif (str_contains($catLower, 'berobat')) {
                    $kategori = 'izin_berobat';
                } elseif (str_contains($catLower, 'klien') || str_contains($catLower, 'umroh') || str_contains($catLower, 'bapas')) {
                    $kategori = 'klien_bapas';
                }
            }

            DB::table('sidang_tpp_kanwil_materi')->insert([
                'sidang_tpp_kanwil_id' => $sidang->id,
                'urutan' => 1,
                'kategori_materi' => $kategori,
                'judul_materi' => $sidang->agenda_judul ?: ($sidang->agenda_kategori ?: 'Agenda Sidang TPP'),
                'deskripsi_materi' => $sidang->agenda_deskripsi,
                'mutasi_id' => $sidang->mutasi_id,
                'upt_tujuan_rekomendasi_id' => $sidang->upt_tujuan_rekomendasi_id,
                'narapidana_data' => $sidang->narapidana_data,
                'klien_data' => $sidang->klien_data,
                'file_bahan_sidang' => $sidang->file_bahan_sidang,
                'dokumen_tambahan' => null,
                'rekomendasi_sidang' => null,
                'catatan_rekomendasi' => null,
                'created_at' => $sidang->created_at ?: now(),
                'updated_at' => $sidang->updated_at ?: now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidang_tpp_kanwil_materi_pendapat');
        Schema::dropIfExists('sidang_tpp_kanwil_materi');
    }
};
