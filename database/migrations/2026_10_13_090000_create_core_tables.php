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
        // 1. UPT
        if (! Schema::hasTable('upt')) {
            Schema::create('upt', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_upt', 100);
                $table->text('alamat')->nullable();
                $table->integer('kapasitas')->default(0);
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamps();
            });
        }

        // 2. Instansi
        if (! Schema::hasTable('instansi')) {
            Schema::create('instansi', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_instansi', 100);
                $table->text('alamat')->nullable();
                $table->string('telepon', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('website', 100)->nullable();
                $table->boolean('batas_jam_aktif')->default(false);
                $table->time('jam_mulai')->default('08:00:00');
                $table->time('jam_selesai')->default('11:00:00');
                $table->string('logo', 255)->nullable();
                $table->text('running_text')->nullable();
                $table->string('welcome_banner', 255)->nullable();
                $table->string('favicon', 255)->nullable();
                $table->string('kop_image', 255)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('logo_kop', 255)->nullable();
                $table->string('ttd_image', 255)->nullable();
                $table->string('stempel_image', 255)->nullable();
                $table->boolean('tampilkan_ttd')->default(true);
                $table->boolean('tampilkan_qrcode')->default(true);
                $table->boolean('tampilkan_stempel')->default(true);
                $table->string('qrcode_content', 255)->nullable();
                $table->timestamps();
            });
        }

        // 3. Activity Logs
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->index('idx_user_id');
                $table->string('user_name', 100);
                $table->enum('aksi', ['login', 'logout', 'tambah', 'edit', 'hapus', 'view', 'export', 'import', 'setting', 'error'])->index('idx_aksi');
                $table->string('modul', 50)->index('idx_modul');
                $table->text('deskripsi');
                $table->text('detail')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent()->index('idx_created_at');
            });
        }

        // 4. Data Penghuni
        if (! Schema::hasTable('data_penghuni')) {
            Schema::create('data_penghuni', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('upt_id')->index('fk_penghuni_upt');
                $table->enum('klasifikasi_pidana', [
                    'Pidana Umum', 'Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna',
                    'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering',
                    'Genosida', 'WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun',
                    'Pidana Mati', 'Seumur Hidup', 'UU ITE',
                ])->index('idx_klasifikasi');
                $table->integer('tahanan_dewasa_laki')->default(0);
                $table->integer('tahanan_dewasa_perempuan')->default(0);
                $table->integer('tahanan_anak_laki')->default(0);
                $table->integer('tahanan_anak_perempuan')->default(0);
                $table->integer('narapidana_dewasa_laki')->default(0);
                $table->integer('narapidana_dewasa_perempuan')->default(0);
                $table->integer('narapidana_anak_laki')->default(0);
                $table->integer('narapidana_anak_perempuan')->default(0);
                $table->date('tanggal')->index('idx_tanggal');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['upt_id', 'tanggal', 'klasifikasi_pidana'], 'unique_upt_tgl_klasifikasi');
                $table->foreign('upt_id', 'fk_penghuni_upt')->references('id')->on('upt')->onDelete('cascade');
            });
        }

        // 5. Data Overstaying
        if (! Schema::hasTable('data_overstaying')) {
            Schema::create('data_overstaying', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('upt_id')->index('fk_overstaying_upt');
                $table->integer('tahanan_ai')->default(0);
                $table->integer('tahanan_aii')->default(0);
                $table->integer('tahanan_aiii')->default(0);
                $table->integer('tahanan_aiv')->default(0);
                $table->integer('tahanan_av')->default(0);
                $table->integer('overstaying_ai')->default(0);
                $table->integer('overstaying_aii')->default(0);
                $table->integer('overstaying_aiii')->default(0);
                $table->integer('overstaying_aiv')->default(0);
                $table->integer('overstaying_av')->default(0);
                $table->date('tanggal')->index('idx_tanggal');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['upt_id', 'tanggal'], 'idx_upt_tanggal');
                $table->foreign('upt_id', 'fk_overstaying_upt')->references('id')->on('upt')->onDelete('cascade');
            });
        }

        // 6. Data Integrasi
        if (! Schema::hasTable('data_integrasi')) {
            Schema::create('data_integrasi', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('upt_id')->index('fk_integrasi_upt');
                $table->integer('usulan_pb')->default(0);
                $table->integer('usulan_cb')->default(0);
                $table->integer('usulan_cmb')->default(0);
                $table->integer('usulan_cmk')->default(0);
                $table->integer('usulan_asimilasi')->default(0);
                $table->integer('sk_terbit_pb')->default(0);
                $table->integer('sk_terbit_cb')->default(0);
                $table->integer('sk_terbit_cmb')->default(0);
                $table->integer('sk_terbit_cmk')->default(0);
                $table->integer('sk_terbit_asimilasi')->default(0);
                $table->date('tanggal')->index('idx_tanggal');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['upt_id', 'tanggal'], 'idx_upt_tanggal');
                $table->foreign('upt_id', 'fk_integrasi_upt')->references('id')->on('upt')->onDelete('cascade');
            });
        }

        // 7. Data Bebas Harian
        if (! Schema::hasTable('data_bebas_harian')) {
            Schema::create('data_bebas_harian', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('upt_id');
                $table->date('tanggal')->index('idx_bebas_tgl');
                $table->enum('klasifikasi_pidana', [
                    'Pidana Umum', 'Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna',
                    'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering',
                    'Genosida', 'UU ITE',
                ])->index('idx_bebas_klasifikasi');
                $table->integer('bebas_murni')->default(0);
                $table->integer('pb')->default(0);
                $table->integer('cb')->default(0);
                $table->integer('cmb')->default(0);
                $table->integer('asimilasi')->default(0);
                $table->integer('bdh')->default(0);
                $table->integer('lainnya')->default(0);
                $table->string('ket_lainnya', 255)->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['upt_id', 'tanggal', 'klasifikasi_pidana'], 'unique_bebas_upt_tgl_klasifikasi');
            });
        }

        // 8. Data Asesmen
        if (! Schema::hasTable('data_asesmen')) {
            Schema::create('data_asesmen', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 255);
                $table->string('no_register', 100);
                $table->string('upt', 255)->nullable();
                $table->string('ttl', 255)->nullable();
                $table->integer('usia')->nullable();
                $table->date('tgl_asesmen')->nullable();
                $table->string('modul0_1', 20)->nullable();
                $table->string('modul0_2', 20)->nullable();
                $table->string('modul0_3', 20)->nullable();
                $table->string('modul0_4', 20)->nullable();
                $table->string('modul0_5', 20)->nullable();
                $table->string('modul0_6', 20)->nullable();
                $table->string('hasil_modul0', 50)->nullable();
                $table->string('zat_utama', 100)->nullable();
                $table->integer('usia_pertama_memakai')->nullable();
                $table->integer('lama_pemakaian')->nullable();
                $table->text('gejala_json')->nullable();
                $table->string('kategori_modul1', 50)->nullable();
                $table->string('td', 50)->nullable();
                $table->string('nadi', 50)->nullable();
                $table->string('suhu', 50)->nullable();
                $table->string('bb', 50)->nullable();
                $table->string('tb', 50)->nullable();
                $table->string('kesimpulan_modul2', 50)->nullable();
                $table->integer('modul3_1')->nullable();
                $table->integer('modul3_2')->nullable();
                $table->integer('modul3_3')->nullable();
                $table->integer('modul3_4')->nullable();
                $table->integer('modul3_5')->nullable();
                $table->string('klaster_4a', 100)->nullable();
                $table->integer('skor_4a')->nullable();
                $table->integer('skor_4b')->nullable();
                $table->integer('skor_4c')->nullable();
                $table->integer('skor_4d')->nullable();
                $table->integer('total_skor')->nullable();
                $table->string('kategori_hasil', 50)->nullable();
                $table->string('keputusan_tpp', 50)->nullable();
                $table->text('catatan')->nullable();
                $table->integer('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 9. Pengumuman
        if (! Schema::hasTable('pengumuman')) {
            Schema::create('pengumuman', function (Blueprint $table) {
                $table->increments('id');
                $table->string('judul', 255);
                $table->text('isi');
                $table->enum('kategori', ['Pengumuman', 'Berita', 'Urgent', 'Info'])->default('Pengumuman');
                $table->enum('tipe', ['Global', 'UPT'])->default('Global');
                $table->unsignedInteger('upt_id')->nullable()->index('upt_id');
                $table->string('gambar', 255)->nullable();
                $table->boolean('aktif')->default(true);
                $table->boolean('tampilkan_marquee')->default(false);
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->unsignedBigInteger('created_by')->index('created_by');
                $table->timestamps();

                $table->foreign('upt_id')->references('id')->on('upt')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 10. Pengumuman Dibaca
        if (! Schema::hasTable('pengumuman_dibaca')) {
            Schema::create('pengumuman_dibaca', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('pengumuman_id');
                $table->unsignedBigInteger('user_id')->index('user_id');
                $table->timestamp('dibaca_pada')->nullable()->useCurrent();

                $table->unique(['pengumuman_id', 'user_id'], 'unique_read');
                $table->foreign('pengumuman_id')->references('id')->on('pengumuman')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 11. WA Settings
        if (! Schema::hasTable('wa_settings')) {
            Schema::create('wa_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('gateway_name', 50)->default('fonnte')->nullable();
                $table->string('api_key', 255)->nullable();
                $table->string('api_url', 255)->default('https://api.fonnte.com/send')->nullable();
                $table->string('account_sid', 255)->nullable();
                $table->string('sender_number', 50)->nullable();
                $table->string('group_id', 50)->nullable();
                $table->string('group_id_laporan', 100)->nullable();
                $table->time('jam_reminder_pagi')->default('07:30:00')->nullable();
                $table->time('jam_reminder_siang')->default('12:00:00')->nullable();
                $table->boolean('aktif')->default(false)->nullable();
                $table->text('template_pagi')->nullable();
                $table->text('template_siang')->nullable();
                $table->timestamps();
            });
        }

        // 12. WA Pimpinan
        if (! Schema::hasTable('wa_pimpinan')) {
            Schema::create('wa_pimpinan', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 100);
                $table->string('jabatan', 100)->nullable();
                $table->string('no_wa', 20);
                $table->integer('urutan')->default(0);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        // 13. WA Scheduled Reports
        if (! Schema::hasTable('wa_scheduled_reports')) {
            Schema::create('wa_scheduled_reports', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_laporan', 100);
                $table->string('group_id', 100);
                $table->time('jam_kirim');
                $table->string('hari', 50)->default('daily');
                $table->text('template_pesan')->nullable();
                $table->boolean('kirim_ke_pimpinan')->default(false);
                $table->boolean('aktif')->default(true);
                $table->dateTime('last_sent')->nullable();
                $table->dateTime('next_send')->nullable();
                $table->timestamps();
            });
        }

        // 14. WA Notification Logs
        if (! Schema::hasTable('wa_notification_logs')) {
            Schema::create('wa_notification_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->enum('tipe', ['reminder_pagi', 'reminder_siang', 'test']);
                $table->string('target', 50);
                $table->text('pesan');
                $table->enum('status', ['success', 'failed'])->default('success');
                $table->text('response')->nullable();
                $table->integer('jumlah_penerima')->default(0);
                $table->timestamp('tanggal_kirim')->nullable()->useCurrent();
            });
        }

        // 15. WA Scheduled Report Logs
        if (! Schema::hasTable('wa_scheduled_report_logs')) {
            Schema::create('wa_scheduled_report_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('report_id')->index('report_id');
                $table->text('pesan');
                $table->enum('status', ['success', 'failed'])->default('success');
                $table->text('response')->nullable();
                $table->timestamp('tanggal_kirim')->nullable()->useCurrent();

                $table->foreign('report_id')->references('id')->on('wa_scheduled_reports')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_scheduled_report_logs');
        Schema::dropIfExists('wa_notification_logs');
        Schema::dropIfExists('wa_scheduled_reports');
        Schema::dropIfExists('wa_pimpinan');
        Schema::dropIfExists('wa_settings');
        Schema::dropIfExists('pengumuman_dibaca');
        Schema::dropIfExists('pengumuman');
        Schema::dropIfExists('data_asesmen');
        Schema::dropIfExists('data_bebas_harian');
        Schema::dropIfExists('data_integrasi');
        Schema::dropIfExists('data_overstaying');
        Schema::dropIfExists('data_penghuni');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('instansi');
        Schema::dropIfExists('upt');
    }
};
