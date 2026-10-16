<?php

namespace App\Services;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\Upt;
use App\Models\User;
use App\Models\WaPimpinan;
use App\Models\WaScheduledReport;
use App\Models\WaSetting;
use Illuminate\Support\Facades\DB;

class WhatsAppService
{
    public static function send(string $target, string $message): array
    {
        $settings = WaSetting::getFirst();

        if (! $settings || empty($settings->api_key) || empty($settings->aktif)) {
            return [
                'success' => false,
                'message' => 'Notifikasi WhatsApp belum dikonfigurasi atau sedang nonaktif.',
            ];
        }

        $isGroup = preg_match('/[a-zA-Z]/', $target);
        if (! $isGroup) {
            $target = preg_replace('/^0/', '62', $target);
            $target = preg_replace('/[^0-9]/', '', $target);
        }

        $apiKey = $settings->api_key;
        $apiUrl = $settings->api_url ?: 'https://api.fonnte.com/send';
        $gateway = $settings->gateway_name ?: 'fonnte';

        $ch = curl_init();

        if ($gateway === 'fonnte') {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '62',
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: '.$apiKey,
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'target' => $target,
                    'message' => $message,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: '.$apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300) && ! $error;
        $responseData = json_decode($response, true);
        $actualMessage = 'Pesan berhasil dikirim.';

        if ($gateway === 'fonnte' && $responseData) {
            if (isset($responseData['status']) && $responseData['status'] === false) {
                $actualMessage = $responseData['reason'] ?? $responseData['message'] ?? 'Error dari Fonnte';
                $success = false;
            } elseif (isset($responseData['status']) && $responseData['status'] === true) {
                $actualMessage = 'Berhasil terkirim via Fonnte.';
            }
        }

        return [
            'success' => $success,
            'message' => $actualMessage,
            'response' => $response,
            'error' => $error,
            'http_code' => $httpCode,
            'parsed_response' => $responseData,
        ];
    }

    public static function sendMorningReminder(): array
    {
        $settings = WaSetting::getFirst();
        if (! $settings || empty($settings->api_key) || empty($settings->aktif)) {
            return [
                'success' => false,
                'message' => 'Pengaturan WA belum aktif.',
                'sent' => 0,
            ];
        }

        $today = date('Y-m-d');

        $donePenghuni = DataPenghuni::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $doneOverstaying = DataOverstaying::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $doneIntegrasi = DataIntegrasi::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $doneBebasHarian = DataBebasHarian::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();

        $timeRestriction = getTimeRestriction();
        $jamMulai = $timeRestriction['jam_mulai'];
        $jamSelesai = $timeRestriction['jam_selesai'];

        $operators = User::with('upt')
            ->where('role', 'operator')
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $responses = [];

        foreach ($operators as $op) {
            $uptId = $op->upt_id;
            $missing = [];

            if (! in_array($uptId, $donePenghuni)) {
                $missing[] = 'Data Penghuni';
            }
            if (! in_array($uptId, $doneOverstaying)) {
                $missing[] = 'Data Overstaying';
            }
            if (! in_array($uptId, $doneIntegrasi)) {
                $missing[] = 'Data Integrasi';
            }
            if (! in_array($uptId, $doneBebasHarian)) {
                $missing[] = 'Data Bebas Harian';
            }

            if (empty($missing)) {
                $skipped++;

                continue;
            }

            $missingText = implode(', ', $missing);
            $template = $settings->template_pagi ?: "🌅 *PENGINGAT INPUT DATA*\n\nYth. {nama}\nUPT: {upt}\n\n⚠️ *Belum Input:* {list_missing}\n\nMohon segera lengkapi data hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_";

            $message = str_replace(
                ['{nama}', '{upt}', '{jam_mulai}', '{jam_selesai}', '{list_missing}'],
                [$op->nama_lengkap, $op->upt?->nama_upt ?? '-', $jamMulai, $jamSelesai, $missingText],
                $template
            );

            $res = self::send($op->no_wa, $message);
            if ($res['success']) {
                $sent++;
            } else {
                $failed++;
            }

            $responses[] = [
                'name' => $op->nama_lengkap,
                'upt' => $op->upt?->nama_upt ?? '-',
                'status' => $res['success'] ? 'success' : 'failed',
            ];
        }

        try {
            DB::table('wa_notification_logs')->insert([
                'tipe' => 'reminder_pagi',
                'target' => 'operator belum input',
                'pesan' => "Smart reminder sent to {$sent} operators. {$skipped} operators skipped (already input).",
                'status' => $failed > 0 ? 'partial' : 'success',
                'jumlah_penerima' => $sent,
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {
        }

        return [
            'success' => true,
            'message' => "Reminder terkirim ke {$sent} operator, {$skipped} dilewati (sudah input).",
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'details' => $responses,
        ];
    }

    public static function sendAfternoonReport(): array
    {
        $settings = WaSetting::getFirst();
        if (! $settings || empty($settings->api_key) || empty($settings->aktif)) {
            return ['success' => false, 'message' => 'Pengaturan WA belum aktif.'];
        }

        if (empty($settings->group_id)) {
            return ['success' => false, 'message' => 'Group ID belum diatur.'];
        }

        $today = date('Y-m-d');
        $allUPTs = Upt::orderBy('nama_upt')->get();

        $penghuniUPTs = DataPenghuni::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $overstayingUPTs = DataOverstaying::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $integrasiUPTs = DataIntegrasi::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $bebasHarianUPTs = DataBebasHarian::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();

        $missingPenghuni = [];
        $missingOverstaying = [];
        $missingIntegrasi = [];
        $missingBebasHarian = [];

        foreach ($allUPTs as $upt) {
            if (! in_array($upt->id, $penghuniUPTs)) {
                $missingPenghuni[] = $upt->nama_upt;
            }
            if (! in_array($upt->id, $overstayingUPTs)) {
                $missingOverstaying[] = $upt->nama_upt;
            }
            if (! in_array($upt->id, $integrasiUPTs)) {
                $missingIntegrasi[] = $upt->nama_upt;
            }
            if (! in_array($upt->id, $bebasHarianUPTs)) {
                $missingBebasHarian[] = $upt->nama_upt;
            }
        }

        $listBelumInput = '';
        if (empty($missingPenghuni) && empty($missingOverstaying) && empty($missingIntegrasi) && empty($missingBebasHarian)) {
            $listBelumInput = '✅ *SEMUA UPT SUDAH MENGINPUT DATA LENGKAP*';
        } else {
            if (! empty($missingPenghuni)) {
                $listBelumInput .= '🔴 *Belum Input Data Penghuni* ('.count($missingPenghuni)." UPT):\n";
                foreach ($missingPenghuni as $name) {
                    $listBelumInput .= "   • $name\n";
                }
                $listBelumInput .= "\n";
            }
            if (! empty($missingOverstaying)) {
                $listBelumInput .= '🔴 *Belum Input Data Overstaying* ('.count($missingOverstaying)." UPT):\n";
                foreach ($missingOverstaying as $name) {
                    $listBelumInput .= "   • $name\n";
                }
                $listBelumInput .= "\n";
            }
            if (! empty($missingIntegrasi)) {
                $listBelumInput .= '🔴 *Belum Input Data Integrasi* ('.count($missingIntegrasi)." UPT):\n";
                foreach ($missingIntegrasi as $name) {
                    $listBelumInput .= "   • $name\n";
                }
                $listBelumInput .= "\n";
            }
            if (! empty($missingBebasHarian)) {
                $listBelumInput .= '🔴 *Belum Input Bebas Harian* ('.count($missingBebasHarian)." UPT):\n";
                foreach ($missingBebasHarian as $name) {
                    $listBelumInput .= "   • $name\n";
                }
                $listBelumInput .= "\n";
            }
        }

        $totalBelum = count($missingPenghuni) + count($missingOverstaying) + count($missingIntegrasi) + count($missingBebasHarian);

        $template = $settings->template_siang ?: "📊 *LAPORAN MONITORING INPUT DATA*\nTanggal: {tanggal}\nWaktu: {waktu} WIB\n\n{list_belum_input}\n_Sultan Banten Ditjenpas_";

        $message = str_replace(
            ['{tanggal}', '{waktu}', '{list_belum_input}', '{total_belum}'],
            [formatIndonesianDate($today), date('H:i'), trim($listBelumInput), $totalBelum],
            $template
        );

        $res = self::send($settings->group_id, $message);

        try {
            DB::table('wa_notification_logs')->insert([
                'tipe' => 'laporan_siang',
                'target' => $settings->group_id,
                'pesan' => $message,
                'status' => $res['success'] ? 'success' : 'failed',
                'jumlah_penerima' => 1,
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {
        }

        return [
            'success' => $res['success'],
            'message' => $res['message'],
            'missing_penghuni' => count($missingPenghuni),
            'missing_overstaying' => count($missingOverstaying),
            'missing_integrasi' => count($missingIntegrasi),
            'missing_bebas_harian' => count($missingBebasHarian),
        ];
    }

    public static function sendScheduledReport(int $scheduleId): array
    {
        $schedule = WaScheduledReport::find($scheduleId);
        if (! $schedule) {
            return ['success' => false, 'message' => 'Jadwal laporan tidak ditemukan.'];
        }

        $today = date('Y-m-d');
        $comprehensiveData = self::getComprehensiveReportData($today);

        $template = $schedule->template_pesan ?: self::getDefaultTemplate();
        $message = self::formatTemplateMessage($template, $comprehensiveData);

        $res = ['success' => true];
        if (! empty($schedule->group_id) && $schedule->group_id !== '-') {
            $res = self::send($schedule->group_id, $message);
        }

        if ($res['success']) {
            $schedule->update(['last_sent' => now()]);
        }

        try {
            DB::table('wa_scheduled_report_logs')->insert([
                'report_id' => $scheduleId,
                'pesan' => $message,
                'status' => $res['success'] ? 'success' : 'failed',
                'response' => $res['message'] ?? ($res['response'] ?? null),
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {
        }

        if (! empty($schedule->kirim_ke_pimpinan) && $res['success']) {
            $pimpinanList = WaPimpinan::where('aktif', 1)->orderBy('urutan')->get();
            foreach ($pimpinanList as $pimpinan) {
                if (! empty($pimpinan->no_wa)) {
                    self::send($pimpinan->no_wa, $message);
                }
            }
        }

        return $res;
    }

    public static function processScheduledReports(): array
    {
        $now = now()->timezone('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        $currentDayNum = (int) $now->format('w'); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $isWeekday = ($currentDayNum >= 1 && $currentDayNum <= 5);
        $currentDayName = strtolower($now->format('l')); // 'monday', 'friday', etc.
        $dayNameIndo = match ($currentDayNum) {
            0 => 'minggu',
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            default => '',
        };

        $schedules = WaScheduledReport::where('aktif', 1)
            ->where('jam_kirim', '<=', $currentTime)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_sent')->orWhereDate('last_sent', '<', $today);
            })
            ->get();

        $results = [];
        foreach ($schedules as $s) {
            $shouldSend = false;
            $hari = strtolower(trim((string) $s->hari));

            if ($hari === 'daily' || $hari === 'setiap hari' || $hari === '*') {
                $shouldSend = true;
            } elseif (($hari === 'weekdays' || $hari === 'hari kerja') && $isWeekday) {
                $shouldSend = true;
            } elseif ($hari === $currentDayName || $hari === $dayNameIndo) {
                $shouldSend = true;
            } elseif ((string) $hari === (string) $currentDayNum) {
                $shouldSend = true;
            }

            if ($shouldSend) {
                $res = self::sendScheduledReport($s->id);
                $results[] = [
                    'id' => $s->id,
                    'nama' => $s->nama_laporan,
                    'status' => $res['success'] ? 'success' : 'failed',
                    'message' => $res['message'] ?? '',
                ];
            }
        }

        return $results;
    }

    public static function runCron(string $action = 'run'): array
    {
        if ($action === 'status') {
            return [
                'success' => true,
                'status' => self::getStatus(),
            ];
        }

        $settings = WaSetting::getFirst();
        if (! $settings || empty($settings->aktif)) {
            return ['success' => false, 'message' => 'WhatsApp notifications are disabled'];
        }

        switch ($action) {
            case 'morning':
                return self::sendMorningReminder();
            case 'afternoon':
                return self::sendAfternoonReport();
            case 'scheduled':
                $scheduledResults = self::processScheduledReports();

                return [
                    'success' => true,
                    'message' => 'Processed '.count($scheduledResults).' schedules',
                    'details' => $scheduledResults,
                ];
            case 'run':
            default:
                $morning = ['status' => 'skipped'];
                $afternoon = ['status' => 'skipped'];
                $now = now()->timezone('Asia/Jakarta');
                $today = $now->format('Y-m-d');
                $currentTime = $now->format('H:i');

                $morningTime = substr($settings->jam_reminder_pagi ?? '07:30', 0, 5);
                if ($currentTime >= $morningTime) {
                    $sentToday = DB::table('wa_notification_logs')
                        ->where('tipe', 'reminder_pagi')
                        ->whereDate('tanggal_kirim', $today)
                        ->count();
                    $morning = $sentToday === 0 ? self::sendMorningReminder() : ['status' => 'already_sent'];
                }

                $afternoonTime = substr($settings->jam_reminder_siang ?? '12:00', 0, 5);
                if ($currentTime >= $afternoonTime) {
                    $sentToday = DB::table('wa_notification_logs')
                        ->where('tipe', 'laporan_siang')
                        ->whereDate('tanggal_kirim', $today)
                        ->count();
                    $afternoon = $sentToday === 0 ? self::sendAfternoonReport() : ['status' => 'already_sent'];
                }

                $scheduledResults = self::processScheduledReports();

                return [
                    'success' => true,
                    'morning' => $morning,
                    'afternoon' => $afternoon,
                    'scheduled' => $scheduledResults,
                ];
        }
    }

    public static function getDefaultTemplate(): string
    {
        return "📊 *LAPORAN TERPADU HARIAN (SULTAN BANTEN)*\n".
               "📅 Tanggal: {tanggal}\n".
               "⏰ Waktu: {waktu} WIB\n\n".
               "🏢 *RINGKASAN TOTAL:*\n".
               "• Total UPT: {total_upt} UPT\n".
               "• Kapasitas: {total_kapasitas} orang\n".
               "• Isi Penghuni: {total_penghuni} orang\n".
               "• Persentase Hunian: {persen_hunian}%\n\n".
               "👥 *RINCIAN PENGHUNI:*\n".
               "• Tahanan: {total_tahanan} orang (Dewasa: {tahanan_dewasa}, Anak: {tahanan_anak})\n".
               "• Narapidana: {total_napi} orang (Dewasa: {napi_dewasa}, Anak: {napi_anak})\n".
               "• Overstaying: {total_overstaying} orang\n".
               "• Integrasi: {total_integrasi} orang (Usulan: {total_usulan_integrasi}, SK Terbit: {total_sk_integrasi})\n\n".
               "🕊️ *RINCIAN BEBAS HARIAN ({total_bebas_harian} orang):*\n".
               "• Bebas Murni: {bebas_murni} orang\n".
               "• Pembebasan Bersyarat (PB): {bebas_pb} orang\n".
               "• Cuti Bersyarat (CB): {bebas_cb} orang\n".
               "• Cuti Menjelang Bebas (CMB): {bebas_cmb} orang\n".
               "• Asimilasi: {bebas_asimilasi} orang\n".
               "• Bebas Demi Hukum (BDH): {bebas_bdh} orang\n".
               "• Lainnya: {bebas_lainnya} orang\n\n".
               "⚖️ *KLASIFIKASI KHUSUS:*\n".
               "• Narkoba: {narkoba_total} orang\n".
               "• Tipikor: {tipikor} orang\n".
               "• Teroris: {teroris} orang\n\n".
               '_Laporan otomatis dari Sistem Sultan Banten Ditjenpas_';
    }

    public static function getGroupedTemplateVariables(): array
    {
        return [
            '🕊️ Pembebasan Harian' => [
                'total_bebas_harian' => 'Total seluruh warga binaan bebas hari ini (alias: {total_bebas}, {bebas})',
                'bebas_murni' => 'Jumlah Bebas Murni hari ini',
                'bebas_pb' => 'Jumlah Pembebasan Bersyarat / PB (alias: {pb})',
                'bebas_cb' => 'Jumlah Cuti Bersyarat / CB (alias: {cb})',
                'bebas_cmb' => 'Jumlah Cuti Menjelang Bebas / CMB (alias: {cmb})',
                'bebas_asimilasi' => 'Jumlah Bebas Asimilasi (alias: {asimilasi})',
                'bebas_bdh' => 'Jumlah Bebas Demi Hukum / BDH (alias: {bdh})',
                'bebas_lainnya' => 'Jumlah Bebas Lainnya (alias: {lainnya})',
            ],
            '👥 Hunian & Penghuni' => [
                'total_penghuni' => 'Total isi penghuni aktual seluruh UPT (alias: {isi}, {penghuni})',
                'total_kapasitas' => 'Total kapasitas hunian seluruh UPT (alias: {kapasitas})',
                'persen_hunian' => 'Persentase tingkat hunian terhadap kapasitas (%)',
                'total_tahanan' => 'Total seluruh tahanan (alias: {tahanan})',
                'tahanan_dewasa' => 'Rincian total tahanan dewasa',
                'tahanan_anak' => 'Rincian total tahanan anak',
                'total_napi' => 'Total seluruh narapidana (alias: {napi}, {narapidana})',
                'napi_dewasa' => 'Rincian total narapidana dewasa',
                'napi_anak' => 'Rincian total narapidana anak',
            ],
            '📑 Usulan & SK Integrasi' => [
                'total_integrasi' => 'Total seluruh usulan dan SK integrasi hari ini',
                'total_usulan_integrasi' => 'Total seluruh usulan integrasi (alias: {total_usulan})',
                'total_sk_integrasi' => 'Total seluruh SK integrasi terbit hari ini (alias: {total_sk})',
                'usulan_pb' => 'Jumlah usulan Pembebasan Bersyarat (PB)',
                'sk_pb' => 'Jumlah SK terbit Pembebasan Bersyarat (PB)',
                'usulan_cb' => 'Jumlah usulan Cuti Bersyarat (CB)',
                'sk_cb' => 'Jumlah SK terbit Cuti Bersyarat (CB)',
                'usulan_cmb' => 'Jumlah usulan Cuti Menjelang Bebas (CMB)',
                'sk_cmb' => 'Jumlah SK terbit Cuti Menjelang Bebas (CMB)',
                'usulan_cmk' => 'Jumlah usulan Cuti Mengunjungi Keluarga (CMK)',
                'sk_cmk' => 'Jumlah SK terbit Cuti Mengunjungi Keluarga (CMK)',
                'usulan_asimilasi' => 'Jumlah usulan Asimilasi',
                'sk_asimilasi' => 'Jumlah SK terbit Asimilasi',
            ],
            '⏳ Overstaying Tahanan' => [
                'total_overstaying' => 'Total seluruh tahanan overstaying (alias: {overstaying})',
                'overstaying_ai' => 'Overstaying AI (Penyidik Kepolisian)',
                'overstaying_aii' => 'Overstaying AII (Kejaksaan Negeri)',
                'overstaying_aiii' => 'Overstaying AIII (Pengadilan Negeri)',
                'overstaying_aiv' => 'Overstaying AIV (Pengadilan Tinggi)',
                'overstaying_av' => 'Overstaying AV (Mahkamah Agung)',
            ],
            '⚖️ Kasus Pidana Khusus' => [
                'narkoba_total' => 'Total kasus narkotika (bandar + pengguna) (alias: {narkoba})',
                'tipikor' => 'Total kasus tindak pidana korupsi',
                'teroris' => 'Total kasus terorisme',
            ],
            '📅 Informasi Umum & Waktu' => [
                'tanggal' => 'Tanggal laporan hari ini (contoh: 16 Oktober 2026)',
                'waktu' => 'Jam dan menit pengiriman pesan WIB (contoh: 16:00)',
                'total_upt' => 'Total jumlah satuan kerja / UPT aktif',
            ],
        ];
    }

    public static function getTemplateVariables(): array
    {
        $flat = [];
        foreach (self::getGroupedTemplateVariables() as $group) {
            foreach ($group as $tag => $desc) {
                $flat[$tag] = $desc;
            }
        }

        return $flat;
    }

    public static function formatTemplateMessage(string $template, array $data): string
    {
        foreach ($data as $var => $value) {
            $template = str_replace('{'.$var.'}', (string) $value, $template);
        }

        return $template;
    }

    public static function getComprehensiveReportData(string $tanggal): array
    {
        $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

        $totalUpt = Upt::count();
        $totalKapasitas = (int) Upt::sum('kapasitas');

        $penghuni = DataPenghuni::where('tanggal', $tanggal)
            ->whereNotIn('klasifikasi_pidana', $excludeKlasifikasi)
            ->selectRaw('
                COALESCE(SUM(tahanan_dewasa_laki), 0) as tah_dewasa_l,
                COALESCE(SUM(tahanan_dewasa_perempuan), 0) as tah_dewasa_p,
                COALESCE(SUM(tahanan_anak_laki), 0) as tah_anak_l,
                COALESCE(SUM(tahanan_anak_perempuan), 0) as tah_anak_p,
                COALESCE(SUM(narapidana_dewasa_laki), 0) as napi_dewasa_l,
                COALESCE(SUM(narapidana_dewasa_perempuan), 0) as napi_dewasa_p,
                COALESCE(SUM(narapidana_anak_laki), 0) as napi_anak_l,
                COALESCE(SUM(narapidana_anak_perempuan), 0) as napi_anak_p
            ')
            ->first();

        $tahananDewasa = (int) (($penghuni->tah_dewasa_l ?? 0) + ($penghuni->tah_dewasa_p ?? 0));
        $tahananAnak = (int) (($penghuni->tah_anak_l ?? 0) + ($penghuni->tah_anak_p ?? 0));
        $totTahanan = $tahananDewasa + $tahananAnak;

        $napiDewasa = (int) (($penghuni->napi_dewasa_l ?? 0) + ($penghuni->napi_dewasa_p ?? 0));
        $napiAnak = (int) (($penghuni->napi_anak_l ?? 0) + ($penghuni->napi_anak_p ?? 0));
        $totNapi = $napiDewasa + $napiAnak;

        $totPenghuni = $totTahanan + $totNapi;
        $persenHunian = $totalKapasitas > 0 ? round(($totPenghuni / $totalKapasitas) * 100, 1) : 0;

        $overstayingAgg = DataOverstaying::where('tanggal', $tanggal)
            ->selectRaw('
                COALESCE(SUM(overstaying_ai), 0) as over_ai,
                COALESCE(SUM(overstaying_aii), 0) as over_aii,
                COALESCE(SUM(overstaying_aiii), 0) as over_aiii,
                COALESCE(SUM(overstaying_aiv), 0) as over_aiv,
                COALESCE(SUM(overstaying_av), 0) as over_av
            ')
            ->first();

        $overAi = (int) ($overstayingAgg->over_ai ?? 0);
        $overAii = (int) ($overstayingAgg->over_aii ?? 0);
        $overAiii = (int) ($overstayingAgg->over_aiii ?? 0);
        $overAiv = (int) ($overstayingAgg->over_aiv ?? 0);
        $overAv = (int) ($overstayingAgg->over_av ?? 0);
        $totOverstaying = $overAi + $overAii + $overAiii + $overAiv + $overAv;

        $integrasiAgg = DataIntegrasi::where('tanggal', $tanggal)
            ->selectRaw('
                COALESCE(SUM(usulan_pb), 0) as usulan_pb,
                COALESCE(SUM(usulan_cb), 0) as usulan_cb,
                COALESCE(SUM(usulan_cmb), 0) as usulan_cmb,
                COALESCE(SUM(usulan_cmk), 0) as usulan_cmk,
                COALESCE(SUM(usulan_asimilasi), 0) as usulan_asimilasi,
                COALESCE(SUM(sk_terbit_pb), 0) as sk_pb,
                COALESCE(SUM(sk_terbit_cb), 0) as sk_cb,
                COALESCE(SUM(sk_terbit_cmb), 0) as sk_cmb,
                COALESCE(SUM(sk_terbit_cmk), 0) as sk_cmk,
                COALESCE(SUM(sk_terbit_asimilasi), 0) as sk_asimilasi
            ')
            ->first();

        $usulanPb = (int) ($integrasiAgg->usulan_pb ?? 0);
        $usulanCb = (int) ($integrasiAgg->usulan_cb ?? 0);
        $usulanCmb = (int) ($integrasiAgg->usulan_cmb ?? 0);
        $usulanCmk = (int) ($integrasiAgg->usulan_cmk ?? 0);
        $usulanAsimilasi = (int) ($integrasiAgg->usulan_asimilasi ?? 0);
        $totUsulan = $usulanPb + $usulanCb + $usulanCmb + $usulanCmk + $usulanAsimilasi;

        $skPb = (int) ($integrasiAgg->sk_pb ?? 0);
        $skCb = (int) ($integrasiAgg->sk_cb ?? 0);
        $skCmb = (int) ($integrasiAgg->sk_cmb ?? 0);
        $skCmk = (int) ($integrasiAgg->sk_cmk ?? 0);
        $skAsimilasi = (int) ($integrasiAgg->sk_asimilasi ?? 0);
        $totSk = $skPb + $skCb + $skCmb + $skCmk + $skAsimilasi;

        $totIntegrasi = $totUsulan + $totSk;

        $bebasAgg = DataBebasHarian::where('tanggal', $tanggal)
            ->selectRaw('
                COALESCE(SUM(bebas_murni), 0) as bebas_murni,
                COALESCE(SUM(pb), 0) as bebas_pb,
                COALESCE(SUM(cb), 0) as bebas_cb,
                COALESCE(SUM(cmb), 0) as bebas_cmb,
                COALESCE(SUM(asimilasi), 0) as bebas_asimilasi,
                COALESCE(SUM(bdh), 0) as bebas_bdh,
                COALESCE(SUM(lainnya), 0) as bebas_lainnya
            ')
            ->first();

        $bebasMurni = (int) ($bebasAgg->bebas_murni ?? 0);
        $bebasPb = (int) ($bebasAgg->bebas_pb ?? 0);
        $bebasCb = (int) ($bebasAgg->bebas_cb ?? 0);
        $bebasCmb = (int) ($bebasAgg->bebas_cmb ?? 0);
        $bebasAsimilasi = (int) ($bebasAgg->bebas_asimilasi ?? 0);
        $bebasBdh = (int) ($bebasAgg->bebas_bdh ?? 0);
        $bebasLainnya = (int) ($bebasAgg->bebas_lainnya ?? 0);
        $totBebasHarian = $bebasMurni + $bebasPb + $bebasCb + $bebasCmb + $bebasAsimilasi + $bebasBdh + $bebasLainnya;

        $narkoba = (int) DataPenghuni::where('tanggal', $tanggal)
            ->whereIn('klasifikasi_pidana', ['Narkoba Bandar', 'Narkoba Pengguna'])
            ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));

        $tipikor = (int) DataPenghuni::where('tanggal', $tanggal)
            ->where('klasifikasi_pidana', 'Tipikor')
            ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));

        $teroris = (int) DataPenghuni::where('tanggal', $tanggal)
            ->where('klasifikasi_pidana', 'Teroris')
            ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));

        return [
            // Tanggal, Waktu & Satuan Kerja
            'tanggal' => formatIndonesianDate($tanggal),
            'waktu' => now()->timezone('Asia/Jakarta')->format('H:i'),
            'total_upt' => $totalUpt,

            // Hunian & Kapasitas
            'total_kapasitas' => $totalKapasitas,
            'kapasitas' => $totalKapasitas,
            'total_penghuni' => $totPenghuni,
            'penghuni' => $totPenghuni,
            'isi' => $totPenghuni,
            'total_isi' => $totPenghuni,
            'persen_hunian' => $persenHunian,

            // Tahanan & Narapidana
            'total_tahanan' => $totTahanan,
            'tahanan' => $totTahanan,
            'total_napi' => $totNapi,
            'napi' => $totNapi,
            'narapidana' => $totNapi,
            'tahanan_dewasa' => $tahananDewasa,
            'tahanan_anak' => $tahananAnak,
            'napi_dewasa' => $napiDewasa,
            'napi_anak' => $napiAnak,

            // Overstaying
            'total_overstaying' => $totOverstaying,
            'overstaying' => $totOverstaying,
            'overstaying_ai' => $overAi,
            'overstaying_aii' => $overAii,
            'overstaying_aiii' => $overAiii,
            'overstaying_aiv' => $overAiv,
            'overstaying_av' => $overAv,

            // Integrasi (Usulan & SK)
            'total_integrasi' => $totIntegrasi,
            'total_usulan_integrasi' => $totUsulan,
            'total_usulan' => $totUsulan,
            'total_sk_integrasi' => $totSk,
            'total_sk' => $totSk,
            'usulan_pb' => $usulanPb,
            'sk_pb' => $skPb,
            'usulan_cb' => $usulanCb,
            'sk_cb' => $skCb,
            'usulan_cmb' => $usulanCmb,
            'sk_cmb' => $skCmb,
            'usulan_cmk' => $usulanCmk,
            'sk_cmk' => $skCmk,
            'usulan_asimilasi' => $usulanAsimilasi,
            'sk_asimilasi' => $skAsimilasi,

            // Bebas Harian & Rincian Jenis Bebas
            'total_bebas_harian' => $totBebasHarian,
            'total_bebas' => $totBebasHarian,
            'bebas' => $totBebasHarian,
            'bebas_murni' => $bebasMurni,
            'bebas_pb' => $bebasPb,
            'pb' => $bebasPb,
            'bebas_cb' => $bebasCb,
            'cb' => $bebasCb,
            'bebas_cmb' => $bebasCmb,
            'cmb' => $bebasCmb,
            'bebas_asimilasi' => $bebasAsimilasi,
            'asimilasi' => $bebasAsimilasi,
            'bebas_bdh' => $bebasBdh,
            'bdh' => $bebasBdh,
            'bebas_lainnya' => $bebasLainnya,
            'lainnya' => $bebasLainnya,

            // Klasifikasi Pidana Khusus
            'narkoba_total' => $narkoba,
            'narkoba' => $narkoba,
            'tipikor' => $tipikor,
            'teroris' => $teroris,
        ];
    }

    public static function getStatus(): array
    {
        $settings = WaSetting::getFirst();
        $now = now()->timezone('Asia/Jakarta');
        $schedules = WaScheduledReport::orderBy('jam_kirim')->get();
        $pimpinanCount = WaPimpinan::where('aktif', 1)->count();
        $operatorCount = User::where('role', 'operator')->whereNotNull('no_wa')->where('no_wa', '!=', '')->count();

        return [
            'waktu_server_wib' => $now->format('Y-m-d H:i:s').' WIB',
            'hari_ini' => $now->format('l').' ('.match ((int) $now->format('w')) {
                0 => 'Minggu',
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                default => '',
            }.')',
            'notifikasi_aktif' => (bool) ($settings?->aktif),
            'gateway' => $settings?->gateway_name ?? 'fonnte',
            'api_url' => $settings?->api_url ?? 'https://api.fonnte.com/send',
            'api_key_terisi' => ! empty($settings?->api_key),
            'group_id_target' => $settings?->group_id ?: '-',
            'jam_reminder_pagi' => substr($settings?->jam_reminder_pagi ?? '07:30', 0, 5).' WIB',
            'jam_reminder_siang' => substr($settings?->jam_reminder_siang ?? '12:00', 0, 5).' WIB',
            'total_jadwal' => $schedules->count(),
            'jadwal_aktif' => $schedules->where('aktif', 1)->count(),
            'total_pimpinan_aktif' => $pimpinanCount,
            'total_operator_wa_siap' => $operatorCount,
            'daftar_jadwal' => $schedules->map(fn ($s) => [
                'id' => $s->id,
                'nama' => $s->nama_laporan,
                'jam' => substr($s->jam_kirim, 0, 5).' WIB',
                'hari' => $s->hari,
                'status' => $s->aktif ? 'Aktif' : 'Nonaktif',
                'target' => $s->group_id,
                'terakhir_kirim' => $s->last_sent?->format('Y-m-d H:i:s') ?? '-',
            ])->toArray(),
        ];
    }
}
