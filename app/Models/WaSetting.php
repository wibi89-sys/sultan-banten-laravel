<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WaSetting extends Model
{
    protected $table = 'wa_settings';

    protected $fillable = [
        'gateway_name',
        'api_key',
        'api_url',
        'account_sid',
        'sender_number',
        'group_id',
        'group_id_laporan',
        'jam_reminder_pagi',
        'jam_reminder_siang',
        'aktif',
        'jeda_kirim',
        'daftar_salam',
        'template_pagi',
        'template_siang',
        'template_sidang_tpp',
        'template_pengingat_sidang_ba',
        'group_id_tpp',
        'wa_notif_sidang_tpp_otomatis',
        'wa_notif_mutasi_otomatis',
        'group_id_mutasi',
        'template_mutasi_usulan',
        'template_mutasi_disetujui',
        'template_pengingat_mutasi',
        'template_pengingat_mutasi_pemindahan',
        'template_pengingat_mutasi_penerimaan',
        'template_pengingat_mutasi_bast',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'wa_notif_sidang_tpp_otomatis' => 'boolean',
            'wa_notif_mutasi_otomatis' => 'boolean',
        ];
    }

    public static function getFirst(): ?self
    {
        return static::find(1) ?? static::first();
    }

    /**
     * Dapatkan template default pengingat Berita Acara (BA) Sidang TPP.
     */
    public static function getDefaultTppBaReminderTemplate(): string
    {
        return "📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n".
               "*Divisi Pemasyarakatan*\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               "Hal: *Pengingat Unggah Berita Acara (BA) Sidang TPP*\n\n".
               "Yth. Rekan Operator & Tim TPP *{nama_upt}*,\n\n".
               "Berdasarkan jadwal pelaksanaan Sidang TPP UPT:\n".
               "• *No. Undangan Sidang:* {nomor_undangan}\n".
               "• *Tanggal Pelaksanaan:* {tanggal_sidang}\n\n".
               "Kami memohon kesediaan rekan operator untuk segera mengunggah berkas *Berita Acara (BA) Hasil Sidang TPP* ke dalam sistem SULTAN BANTEN guna penyusunan Surat Tugas dan Laporan Pembinaan Sidang Tingkat Wilayah.\n\n".
               "🔗 *Tautan Unggah BA:* \n👉 {link_sidang}\n\n".
               "Atas perhatian dan kerja samanya, diucapkan terima kasih.\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               '_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._';
    }

    /**
     * Dapatkan template default pengingat dokumen pelaksanaan mutasi narapidana.
     */
    public static function getDefaultMutasiReminderTemplate(): string
    {
        return "📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n".
               "*Divisi Pemasyarakatan*\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               "Hal: *Pengingat Unggah {nama_berkas}*\n\n".
               "Yth. Rekan Operator *{nama_upt}*,\n\n".
               "Sehubungan dengan pelaksanaan mutasi narapidana berikut:\n".
               "• *No. Surat Kanwil:* {nomor_surat}\n".
               "• *Jumlah Narapidana:* {total_wbp}\n".
               "• *Status:* Memerlukan kelengkapan berkas pelaksanaan.\n\n".
               "Mohon bantuan rekan operator untuk segera mengunggah berkas *{nama_berkas}* ke dalam Sistem SULTAN BANTEN agar proses verifikasi dan laporan rekapitulasi dapat dituntaskan.\n\n".
               "🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n".
               "Terima kasih atas dedikasi dan kerja sama yang baik.\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               '_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._';
    }

    /**
     * Template khusus pengingat Laporan Pemindahan (UPT Pengirim).
     */
    public static function getDefaultMutasiPemindahanReminderTemplate(): string
    {
        return "📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n".
               "*Divisi Pemasyarakatan*\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               "Hal: *Pengingat Unggah Laporan Pemindahan Narapidana*\n\n".
               "Yth. Rekan Operator & Tim Mutasi *{nama_upt}*,\n\n".
               "Sehubungan dengan pelaksanaan mutasi narapidana berikut:\n".
               "• *No. Surat Kanwil:* {nomor_surat}\n".
               "• *Jumlah Narapidana:* {total_wbp}\n".
               "• *Status:* Menunggu unggah Laporan Pemindahan dari UPT Pengirim.\n\n".
               "Mohon bantuan rekan operator untuk segera mengunggah berkas *Laporan Pemindahan* ke dalam Sistem SULTAN BANTEN agar proses pemindahan terpantau secara real-time.\n\n".
               "🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n".
               "Terima kasih atas dedikasi dan kerja sama yang baik.\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               '_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._';
    }

    /**
     * Template khusus pengingat Laporan Penerimaan (UPT Penerima).
     */
    public static function getDefaultMutasiPenerimaanReminderTemplate(): string
    {
        return "📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n".
               "*Divisi Pemasyarakatan*\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               "Hal: *Pengingat Unggah Laporan Penerimaan Narapidana*\n\n".
               "Yth. Rekan Operator & Tim Mutasi *{nama_upt}*,\n\n".
               "Sehubungan dengan penerimaan mutasi narapidana berikut:\n".
               "• *No. Surat Kanwil:* {nomor_surat}\n".
               "• *Jumlah Narapidana:* {total_wbp}\n".
               "• *Status:* Menunggu unggah Laporan Penerimaan dari UPT Tujuan.\n\n".
               "Mohon bantuan rekan operator untuk segera mengunggah berkas *Laporan Penerimaan* ke dalam Sistem SULTAN BANTEN sebagai bukti fisik narapidana telah diterima dengan aman.\n\n".
               "🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n".
               "Terima kasih atas perhatian dan kerja sama yang baik.\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               '_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._';
    }

    /**
     * Template khusus pengingat BAST (Kedua Belah Pihak).
     */
    public static function getDefaultMutasiBastReminderTemplate(): string
    {
        return "📢 *PENGINGAT RESMI KANWIL KEMENKUMHAM BANTEN*\n".
               "*Divisi Pemasyarakatan*\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               "Hal: *Pengingat Unggah Berita Acara Serah Terima (BAST)*\n\n".
               "Yth. Rekan Operator & Tim Mutasi *{nama_upt}*,\n\n".
               "Sehubungan dengan penyelesaian mutasi narapidana berikut:\n".
               "• *No. Surat Kanwil:* {nomor_surat}\n".
               "• *Jumlah Narapidana:* {total_wbp}\n".
               "• *Status:* Menunggu unggah Berita Acara Serah Terima (BAST).\n\n".
               "Mohon kerja sama rekan operator untuk segera mengunggah dokumen *BAST* yang telah ditandatangani kedua belah pihak ke dalam Sistem SULTAN BANTEN untuk menuntaskan status mutasi narapidana.\n\n".
               "🔗 *Tautan Unggah Dokumen:* \n👉 {link_mutasi}\n\n".
               "Terima kasih atas kerja sama yang baik.\n".
               "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
               '_Pesan otomatis dikirim melalui Sistem Informasi Terpadu Pemasyarakatan SULTAN BANTEN._';
    }

    /**
     * Memastikan kolom template_sidang_tpp, mutasi, dan toggle otomatis tersedia di database
     * untuk mencegah error jika migrasi belum sempat dieksekusi di server hosting.
     */
    public static function ensureTppColumnsExist(): void
    {
        try {
            if (Schema::hasTable('wa_settings')) {
                Schema::table('wa_settings', function (Blueprint $table) {
                    if (! Schema::hasColumn('wa_settings', 'template_sidang_tpp')) {
                        $table->text('template_sidang_tpp')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_pengingat_sidang_ba')) {
                        $table->text('template_pengingat_sidang_ba')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'group_id_tpp')) {
                        $table->string('group_id_tpp', 100)->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'wa_notif_sidang_tpp_otomatis')) {
                        $table->boolean('wa_notif_sidang_tpp_otomatis')->default(false);
                    }
                    if (! Schema::hasColumn('wa_settings', 'wa_notif_mutasi_otomatis')) {
                        $table->boolean('wa_notif_mutasi_otomatis')->default(false);
                    }
                    if (! Schema::hasColumn('wa_settings', 'group_id_mutasi')) {
                        $table->string('group_id_mutasi', 100)->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_mutasi_usulan')) {
                        $table->text('template_mutasi_usulan')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_mutasi_disetujui')) {
                        $table->text('template_mutasi_disetujui')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi')) {
                        $table->text('template_pengingat_mutasi')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi_pemindahan')) {
                        $table->text('template_pengingat_mutasi_pemindahan')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi_penerimaan')) {
                        $table->text('template_pengingat_mutasi_penerimaan')->nullable();
                    }
                    if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi_bast')) {
                        $table->text('template_pengingat_mutasi_bast')->nullable();
                    }
                });
            }
        } catch (\Throwable $e) {
            Log::warning('WaSetting auto-migration warning: '.$e->getMessage());
        }
    }
}
