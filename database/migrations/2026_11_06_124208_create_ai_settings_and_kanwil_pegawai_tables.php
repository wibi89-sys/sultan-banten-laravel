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
        if (! Schema::hasTable('ai_settings')) {
            Schema::create('ai_settings', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->default('gemini');
                $table->text('api_key')->nullable();
                $table->string('model', 100)->default('gemini-1.5-flash');
                $table->boolean('is_active')->default(false);

                // Default Tata Naskah Nota Dinas
                $table->string('nomor_surat_prefix', 100)->default('WP.12-UM.01.01-');
                $table->string('yth_tujuan', 200)->default('Kepala Bagian Tata Usaha dan Umum');
                $table->string('dari_pengirim', 200)->default('Kepala Bidang Pelayanan dan Pembinaan');
                $table->string('hal_surat', 255)->default('Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP');

                // Penandatangan Nota Dinas
                $table->string('penandatangan_nama', 150)->default('Surya Dharma');
                $table->string('penandatangan_nip', 50)->nullable();
                $table->string('penandatangan_jabatan', 200)->default('Kepala Bidang Pelayanan dan Pembinaan');
                $table->boolean('tampilkan_nip')->default(false)->comment('Default false sesuai kebutuhan saat ini');

                $table->timestamps();
            });

            // Insert initial default setting
            DB::table('ai_settings')->insert([
                'provider' => 'gemini',
                'api_key' => null,
                'model' => 'gemini-1.5-flash',
                'is_active' => false,
                'nomor_surat_prefix' => 'WP.12-UM.01.01-',
                'yth_tujuan' => 'Kepala Bagian Tata Usaha dan Umum',
                'dari_pengirim' => 'Kepala Bidang Pelayanan dan Pembinaan',
                'hal_surat' => 'Permohonan Surat Tugas Pembinaan Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP',
                'penandatangan_nama' => 'Surya Dharma',
                'penandatangan_nip' => null,
                'penandatangan_jabatan' => 'Kepala Bidang Pelayanan dan Pembinaan',
                'tampilkan_nip' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('kanwil_pegawai')) {
            Schema::create('kanwil_pegawai', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 150);
                $table->string('nip', 50)->nullable();
                $table->string('jabatan', 150);
                $table->string('pangkat_golongan', 100)->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insert default initial employees matching sample document
            DB::table('kanwil_pegawai')->insert([
                [
                    'nama' => 'Rizal Rivai, S.H',
                    'nip' => '199209122010121001',
                    'jabatan' => 'Pengadministrasi Perkantoran',
                    'pangkat_golongan' => 'Penata Muda (III/a)',
                    'urutan' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nama' => 'Gunawan Wibisono',
                    'nip' => '198903192012121001',
                    'jabatan' => 'Pengadministrasi Perkantoran',
                    'pangkat_golongan' => 'Pengatur (II/c)',
                    'urutan' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nama' => 'Indra Sanjaya Putra',
                    'nip' => '199006162017121001',
                    'jabatan' => 'Pengaman Pemasyarakatan Pemula',
                    'pangkat_golongan' => 'Pengatur Muda (II/a)',
                    'urutan' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanwil_pegawai');
        Schema::dropIfExists('ai_settings');
    }
};
