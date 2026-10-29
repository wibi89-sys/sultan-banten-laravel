<?php

namespace App\Services;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\MutasiNarapidana;
use App\Models\Upt;
use App\Models\User;
use App\Models\WaGroup;
use App\Models\WaPimpinan;
use App\Models\WaScheduledReport;
use App\Models\WaSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
                    'delay' => '2',
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

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $cacheKey = 'wa_reminder_pagi_sent_'.$today;
        if (Cache::has($cacheKey)) {
            return [
                'success' => true,
                'message' => 'Reminder pagi sudah pernah terkirim hari ini (cached).',
                'sent' => 0,
            ];
        }

        // Lock early to prevent duplicate concurrent runs
        Cache::put($cacheKey, true, now()->addDays(2));

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

        $rawSalam = trim((string) ($settings->daftar_salam ?? ''));
        $salamList = array_values(array_filter(array_map('trim', explode(',', $rawSalam ?: 'Selamat pagi, Semangat pagi, Yth., Halo Rekan, Salam hangat'))));
        $delayBase = (int) ($settings->jeda_kirim ?? 4);
        if ($delayBase < 1) {
            $delayBase = 1;
        }

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

            // Anti-Spam: Beri jeda acak manusiawi (jitter: delayBase +/- 1 detik) antar pengiriman pesan ke operator
            if ($sent > 0 || $failed > 0) {
                $jitter = random_int(max(1, $delayBase - 1), $delayBase + 2);
                sleep($jitter);
            }

            $missingText = implode(', ', $missing);
            $template = $settings->template_pagi ?: "🌅 *PENGINGAT INPUT DATA*\n\n{salam} {nama}\nUPT: {upt}\n\n⚠️ *Belum Input:* {list_missing}\n\nMohon segera lengkapi data hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_";

            // Pilih salam pembuka acak untuk nomor operator ini
            $randomSalam = ! empty($salamList) ? $salamList[array_rand($salamList)] : 'Selamat pagi';

            $message = str_replace(
                ['{nama}', '{upt}', '{jam_mulai}', '{jam_selesai}', '{list_missing}', '{salam}'],
                [$op->nama_lengkap, $op->upt?->nama_upt ?? '-', $jamMulai, $jamSelesai, $missingText, $randomSalam],
                $template
            );

            // Terapkan spintax acak {opsi1|opsi2|opsi3} jika ada di template
            $message = self::parseSpintax($message);

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
                'status' => $sent > 0 ? 'success' : ($failed > 0 ? 'failed' : 'success'),
                'jumlah_penerima' => $sent,
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mencatat log reminder_pagi: '.$e->getMessage());
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

        $targetGroupId = trim((string) ($settings->group_id ?? ''));
        if (empty($targetGroupId) && Schema::hasTable('wa_groups')) {
            $defaultGroup = WaGroup::where('aktif', true)->where('is_default', true)->first()
                ?? WaGroup::where('aktif', true)->first();
            $targetGroupId = trim((string) ($defaultGroup?->group_id ?? ''));
        }

        if (empty($targetGroupId)) {
            return ['success' => false, 'message' => 'Group ID belum diatur.'];
        }

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $cacheKey = 'wa_reminder_siang_sent_'.$today;
        if (Cache::has($cacheKey)) {
            return [
                'success' => true,
                'message' => 'Laporan siang sudah pernah terkirim hari ini (cached).',
            ];
        }

        // Lock early to prevent duplicate sending
        Cache::put($cacheKey, true, now()->addDays(2));

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

        $totalBelum = count($missingPenghuni) + count($missingOverstaying) + count($missingIntegrasi) + count($missingBebasHarian);

        // Jika semua UPT sudah lengkap menginput data, tidak perlu kirim ke grup WA
        if ($totalBelum === 0) {
            try {
                DB::table('wa_notification_logs')->insert([
                    'tipe' => 'reminder_siang',
                    'target' => $targetGroupId,
                    'pesan' => 'Semua UPT ('.count($allUPTs).' UPT) sudah lengkap menginput data harian (Penghuni, Overstaying, Integrasi, Bebas Harian). Pengiriman laporan siang ke grup dilewati.',
                    'status' => 'success',
                    'jumlah_penerima' => 0,
                    'tanggal_kirim' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal mencatat log reminder_siang: '.$e->getMessage());
            }

            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Semua UPT sudah lengkap mengisi data hari ini. Pengiriman laporan siang ke grup dilewati.',
                'missing_penghuni' => 0,
                'missing_overstaying' => 0,
                'missing_integrasi' => 0,
                'missing_bebas_harian' => 0,
            ];
        }

        $listBelumInput = '';
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

        $template = $settings->template_siang ?: "📊 *LAPORAN MONITORING INPUT DATA*\nTanggal: {tanggal}\nWaktu: {waktu} WIB\n\n{list_belum_input}\n_Sultan Banten Ditjenpas_";

        $message = str_replace(
            ['{tanggal}', '{waktu}', '{list_belum_input}', '{total_belum}'],
            [formatIndonesianDate($today), now()->timezone('Asia/Jakarta')->format('H:i'), trim($listBelumInput), $totalBelum],
            $template
        );

        $message = self::parseSpintax($message);

        $res = self::send($targetGroupId, $message);

        try {
            DB::table('wa_notification_logs')->insert([
                'tipe' => 'reminder_siang',
                'target' => $targetGroupId,
                'pesan' => $message,
                'status' => $res['success'] ? 'success' : 'failed',
                'jumlah_penerima' => 1,
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mencatat log reminder_siang: '.$e->getMessage());
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

        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $cacheKey = "wa_schedule_sent_{$scheduleId}_{$today}";
        if (Cache::has($cacheKey)) {
            return ['success' => true, 'message' => 'Jadwal laporan ini sudah pernah terkirim hari ini (cached).'];
        }

        // Lock early and update last_sent immediately to avoid infinite repeat loops
        Cache::put($cacheKey, true, now()->addDays(2));
        $schedule->update(['last_sent' => now()->timezone('Asia/Jakarta')]);

        $comprehensiveData = self::getComprehensiveReportData($today);

        $template = $schedule->template_pesan ?: self::getDefaultTemplate();
        $message = self::formatTemplateMessage($template, $comprehensiveData);

        $res = ['success' => true];
        if (! empty($schedule->group_id) && $schedule->group_id !== '-') {
            $res = self::send($schedule->group_id, $message);
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
            Log::error('Gagal mencatat log wa_scheduled_report_logs: '.$e->getMessage());
        }

        if (! empty($schedule->kirim_ke_pimpinan) && $res['success']) {
            $pimpinanList = WaPimpinan::where('aktif', 1)->orderBy('urutan')->get();
            $pimpinanSent = 0;
            $settings = WaSetting::getFirst();
            $delayBase = (int) ($settings?->jeda_kirim ?? 4);
            if ($delayBase < 1) {
                $delayBase = 1;
            }

            foreach ($pimpinanList as $pimpinan) {
                if (! empty($pimpinan->no_wa)) {
                    if ($pimpinanSent > 0) {
                        $jitter = random_int(max(1, $delayBase - 1), $delayBase + 2);
                        sleep($jitter);
                    }
                    self::send($pimpinan->no_wa, $message);
                    $pimpinanSent++;
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
            ->get()
            ->filter(function ($s) use ($today) {
                // Skip if already sent today in cache
                if (Cache::has("wa_schedule_sent_{$s->id}_{$today}")) {
                    return false;
                }

                if (empty($s->last_sent)) {
                    return true;
                }

                $lastSentDate = Carbon::parse($s->last_sent)->timezone('Asia/Jakarta')->format('Y-m-d');

                return $lastSentDate < $today;
            });

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

                // 1. Morning Reminder (HANYA dijalankan pada jendela pagi: jam_reminder_pagi s/d 11:30 WIB)
                $morningTime = substr($settings->jam_reminder_pagi ?? '07:30', 0, 5);
                if ($currentTime >= $morningTime && $currentTime <= '11:30') {
                    $morningSentCache = Cache::has('wa_reminder_pagi_sent_'.$today);
                    $morningSentDb = DB::table('wa_notification_logs')
                        ->where('tipe', 'reminder_pagi')
                        ->whereDate('tanggal_kirim', $today)
                        ->exists();

                    if (! $morningSentCache && ! $morningSentDb) {
                        $morning = self::sendMorningReminder();
                    } else {
                        $morning = ['status' => 'already_sent'];
                    }
                } else {
                    $morning = ['status' => 'outside_morning_window'];
                }

                // 2. Afternoon Report (HANYA dijalankan pada jendela siang/sore: jam_reminder_siang s/d 17:30 WIB)
                $afternoonTime = substr($settings->jam_reminder_siang ?? '12:00', 0, 5);
                if ($currentTime >= $afternoonTime && $currentTime <= '17:30') {
                    $afternoonSentCache = Cache::has('wa_reminder_siang_sent_'.$today);
                    $afternoonSentDb = DB::table('wa_notification_logs')
                        ->whereIn('tipe', ['reminder_siang', 'laporan_siang'])
                        ->whereDate('tanggal_kirim', $today)
                        ->exists();

                    if (! $afternoonSentCache && ! $afternoonSentDb) {
                        $afternoon = self::sendAfternoonReport();
                    } else {
                        $afternoon = ['status' => 'already_sent'];
                    }
                } else {
                    $afternoon = ['status' => 'outside_afternoon_window'];
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
                'over_kapasitas' => 'Jumlah selisih kelebihan penghuni di atas kapasitas (alias: {overkapasitas})',
                'persen_over_kapasitas' => 'Persentase kelebihan kapasitas / overkapasitas (%) (alias: {persen_overkapasitas})',
                'selisih_kapasitas' => 'Selisih matematis antara isi penghuni dan kapasitas',
                'status_kapasitas' => 'Keterangan status kapasitas (Over Kapasitas +X orang atau Normal)',
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
                'salam' => 'Salam pembuka acak (Selamat pagi, Semangat pagi, Yth., Halo Rekan, dsb)',
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

        return self::parseSpintax($template);
    }

    /**
     * Parse spintax format {option1|option2|option3} into a random option.
     * Preserves normal template variables like {nama}, {upt}, etc.
     */
    public static function parseSpintax(string $text): string
    {
        while (preg_match('/\{([^{}]*\|[^{}]*)\}/', $text)) {
            $text = (string) preg_replace_callback('/\{([^{}]*\|[^{}]*)\}/', function ($matches) {
                $options = explode('|', $matches[1]);

                return trim($options[array_rand($options)]);
            }, $text);
        }

        return $text;
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
        $selisihKapasitas = $totPenghuni - $totalKapasitas;
        $overKapasitas = max(0, $selisihKapasitas);
        $persenOverKapasitas = $totalKapasitas > 0 ? max(0, round(($selisihKapasitas / $totalKapasitas) * 100, 1)) : 0;
        $statusKapasitas = $totPenghuni > $totalKapasitas
            ? "Over Kapasitas +{$overKapasitas} orang ({$persenOverKapasitas}%)"
            : ($totPenghuni === $totalKapasitas ? 'Sesuai Kapasitas (100%)' : 'Di Bawah Kapasitas (Tersedia '.abs($selisihKapasitas).' tempat)');

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
            'over_kapasitas' => $overKapasitas,
            'overkapasitas' => $overKapasitas,
            'persen_over_kapasitas' => $persenOverKapasitas,
            'persen_overkapasitas' => $persenOverKapasitas,
            'selisih_kapasitas' => $selisihKapasitas,
            'status_kapasitas' => $statusKapasitas,

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
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        $isMasterAktif = (bool) ($settings?->aktif);

        // Terakhir kirim Reminder Pagi
        $lastMorning = DB::table('wa_notification_logs')
            ->where('tipe', 'reminder_pagi')
            ->orderByDesc('tanggal_kirim')
            ->first();

        $morningSentToday = Cache::has('wa_reminder_pagi_sent_'.$today) || DB::table('wa_notification_logs')
            ->where('tipe', 'reminder_pagi')
            ->whereDate('tanggal_kirim', $today)
            ->exists();

        $morningTodayLog = DB::table('wa_notification_logs')
            ->where('tipe', 'reminder_pagi')
            ->whereDate('tanggal_kirim', $today)
            ->first();

        // Terakhir kirim Monitoring Siang
        $lastAfternoon = DB::table('wa_notification_logs')
            ->whereIn('tipe', ['reminder_siang', 'laporan_siang'])
            ->orderByDesc('tanggal_kirim')
            ->first();

        $afternoonSentToday = Cache::has('wa_reminder_siang_sent_'.$today) || DB::table('wa_notification_logs')
            ->whereIn('tipe', ['reminder_siang', 'laporan_siang'])
            ->whereDate('tanggal_kirim', $today)
            ->exists();

        $afternoonTodayLog = DB::table('wa_notification_logs')
            ->whereIn('tipe', ['reminder_siang', 'laporan_siang'])
            ->whereDate('tanggal_kirim', $today)
            ->first();

        $schedules = WaScheduledReport::orderBy('jam_kirim')->get();
        $pimpinanCount = WaPimpinan::where('aktif', 1)->count();
        $operatorCount = User::where('role', 'operator')->whereNotNull('no_wa')->where('no_wa', '!=', '')->count();

        $jamPagi = substr($settings?->jam_reminder_pagi ?? '07:30', 0, 5);
        $jamSiang = substr($settings?->jam_reminder_siang ?? '12:00', 0, 5);

        // Keterangan status hari ini untuk Reminder Pagi
        if ($morningSentToday) {
            $morningStatusHariIni = '✅ Sudah Diproses ('.($morningTodayLog ? date('H:i', strtotime($morningTodayLog->tanggal_kirim)).' WIB' : 'Hari Ini').')';
        } elseif (! $isMasterAktif) {
            $morningStatusHariIni = '⏸️ Master Nonaktif';
        } elseif ($currentTime < $jamPagi) {
            $morningStatusHariIni = '⏳ Menunggu Jam '.$jamPagi.' WIB';
        } elseif ($currentTime > '11:30') {
            $morningStatusHariIni = '⚠️ Lewat Jendela Pagi (11:30)';
        } else {
            $morningStatusHariIni = '🚀 Siap Terkirim';
        }

        // Keterangan status hari ini untuk Monitoring Siang
        if ($afternoonSentToday) {
            $isSkipped = ($afternoonTodayLog && $afternoonTodayLog->jumlah_penerima === 0);
            $afternoonStatusHariIni = $isSkipped
                ? '✅ Dilewati (Semua UPT Lengkap)'
                : '✅ Sudah Terkirim ('.($afternoonTodayLog ? date('H:i', strtotime($afternoonTodayLog->tanggal_kirim)).' WIB' : 'Hari Ini').')';
        } elseif (! $isMasterAktif) {
            $afternoonStatusHariIni = '⏸️ Master Nonaktif';
        } elseif ($currentTime < $jamSiang) {
            $afternoonStatusHariIni = '⏳ Menunggu Jam '.$jamSiang.' WIB';
        } elseif ($currentTime > '17:30') {
            $afternoonStatusHariIni = '⚠️ Lewat Jendela Siang (17:30)';
        } else {
            $afternoonStatusHariIni = '🚀 Siap Terkirim';
        }

        // Target grup display
        $targetGrupDisplay = $settings?->group_id ?: '-';
        if (Schema::hasTable('wa_groups')) {
            $grp = WaGroup::where('group_id', $targetGrupDisplay)->first();
            if ($grp) {
                $targetGrupDisplay = "{$grp->nama_grup} ({$grp->group_id})";
            }
        }

        $daftarJadwal = [
            [
                'id' => 'SYS-1',
                'nama' => 'Reminder Pagi (Pengingat Operator)',
                'jam' => $jamPagi.' WIB',
                'frekuensi' => 'Setiap Hari',
                'status' => $isMasterAktif ? 'Aktif' : 'Nonaktif',
                'target' => 'Operator Belum Input',
                'status_hari_ini' => $morningStatusHariIni,
                'terakhir_kirim' => $lastMorning?->tanggal_kirim ? date('Y-m-d H:i:s', strtotime($lastMorning->tanggal_kirim)) : '-',
            ],
            [
                'id' => 'SYS-2',
                'nama' => 'Monitoring Siang (Rekap Progres UPT)',
                'jam' => $jamSiang.' WIB',
                'frekuensi' => 'Setiap Hari',
                'status' => $isMasterAktif ? 'Aktif' : 'Nonaktif',
                'target' => Str::limit($targetGrupDisplay, 28),
                'status_hari_ini' => $afternoonStatusHariIni,
                'terakhir_kirim' => $lastAfternoon?->tanggal_kirim ? date('Y-m-d H:i:s', strtotime($lastAfternoon->tanggal_kirim)) : '-',
            ],
        ];

        foreach ($schedules as $s) {
            $schedSentToday = Cache::has("wa_schedule_sent_{$s->id}_{$today}")
                || ($s->last_sent && Carbon::parse($s->last_sent)->timezone('Asia/Jakarta')->format('Y-m-d') === $today);

            $schedStatusHariIni = $schedSentToday
                ? '✅ Sudah Terkirim ('.($s->last_sent ? $s->last_sent->timezone('Asia/Jakarta')->format('H:i').' WIB' : 'Hari Ini').')'
                : ($s->aktif ? '⏳ Menunggu Jadwal' : '⏸️ Nonaktif');

            $daftarJadwal[] = [
                'id' => (string) $s->id,
                'nama' => $s->nama_laporan,
                'jam' => substr($s->jam_kirim, 0, 5).' WIB',
                'frekuensi' => $s->hari ?: 'daily',
                'status' => ($isMasterAktif && $s->aktif) ? 'Aktif' : 'Nonaktif',
                'target' => Str::limit($s->group_id ?: '-', 28),
                'status_hari_ini' => $schedStatusHariIni,
                'terakhir_kirim' => $s->last_sent ? $s->last_sent->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            ];
        }

        $totalJadwalSemua = count($daftarJadwal);
        $totalAktifSemua = count(array_filter($daftarJadwal, fn ($j) => $j['status'] === 'Aktif'));

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
            'notifikasi_aktif' => $isMasterAktif,
            'gateway' => $settings?->gateway_name ?? 'fonnte',
            'api_url' => $settings?->api_url ?? 'https://api.fonnte.com/send',
            'api_key_terisi' => ! empty($settings?->api_key),
            'group_id_target' => $targetGrupDisplay,
            'jam_reminder_pagi' => $jamPagi.' WIB',
            'jam_reminder_siang' => $jamSiang.' WIB',
            'total_jadwal' => $totalJadwalSemua,
            'jadwal_aktif' => $totalAktifSemua,
            'total_pimpinan_aktif' => $pimpinanCount,
            'total_operator_wa_siap' => $operatorCount,
            'daftar_jadwal' => $daftarJadwal,
        ];
    }

    public static function notifyNewMutasiUsulan(MutasiNarapidana $mutasi): array
    {
        try {
            $defaultGroup = WaGroup::where('aktif', true)->where('is_default', true)->first()
                ?? WaGroup::where('aktif', true)->first();

            if (! $defaultGroup || empty($defaultGroup->group_id)) {
                return ['success' => false, 'message' => 'Grup WhatsApp aktif tidak ditemukan'];
            }

            $uptAsal = $mutasi->uptAsal?->nama_upt ?? 'UPT Pemohon';
            $tglSurat = $mutasi->tanggal_surat ? $mutasi->tanggal_surat->format('d/m/Y') : '-';

            $msg = "📬 *USULAN MUTASI NARAPIDANA BARU*\n\n";
            $msg .= "UPT Pemohon: *{$uptAsal}*\n";
            $msg .= "No. Surat: {$mutasi->nomor_surat}\n";
            $msg .= "Tanggal Surat: {$tglSurat}\n";
            $msg .= "Kategori: *{$mutasi->kategori_mutasi}*\n\n";
            $msg .= "👥 *Rincian Jumlah Narapidana Diusulkan:*\n";
            $msg .= "• Narkotika: {$mutasi->jumlah_narkotika} orang\n";
            $msg .= "• Pidana Umum: {$mutasi->jumlah_pidum} orang\n";
            $msg .= "• Korupsi (Tipikor): {$mutasi->jumlah_korupsi} orang\n";
            $msg .= "👉 *Total Usulan: {$mutasi->total_usulan} orang*\n\n";
            if ($mutasi->keterangan) {
                $msg .= "Keterangan: {$mutasi->keterangan}\n\n";
            }
            $msg .= "_Mohon kesediaan Tim Kanwil Kemenkumham Banten untuk menelaah berkas persyaratan dan menetapkan UPT Tujuan._\n";
            $msg .= '_Sultan Banten Ditjenpas_';

            return self::send($defaultGroup->group_id, $msg);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function notifyMutasiDisetujui(MutasiNarapidana $mutasi): array
    {
        try {
            $defaultGroup = WaGroup::where('aktif', true)->where('is_default', true)->first()
                ?? WaGroup::where('aktif', true)->first();

            if (! $defaultGroup || empty($defaultGroup->group_id)) {
                return ['success' => false, 'message' => 'Grup WhatsApp aktif tidak ditemukan'];
            }

            $uptAsal = $mutasi->uptAsal?->nama_upt ?? 'UPT Pemohon';
            $uptTujuan = $mutasi->uptTujuan?->nama_upt ?? 'UPT Tujuan';
            $tglSuratKanwil = $mutasi->tanggal_surat_kanwil ? $mutasi->tanggal_surat_kanwil->format('d/m/Y') : date('d/m/Y');

            $msg = "✅ *SURAT PERSETUJUAN MUTASI KANWIL TERBIT*\n\n";
            $msg .= "UPT Pemohon: *{$uptAsal}*\n";
            $msg .= "UPT Penerima Ditunjuk: *{$uptTujuan}*\n";
            $msg .= "No. Surat Kanwil: {$mutasi->nomor_surat_kanwil}\n";
            $msg .= "Tanggal: {$tglSuratKanwil}\n\n";
            $msg .= "👥 *Total WBP Disetujui: {$mutasi->total_disetujui} orang*\n";
            $msg .= "• Narkotika: {$mutasi->jumlah_disetujui_narkotika} orang\n";
            $msg .= "• Pidana Umum: {$mutasi->jumlah_disetujui_pidum} orang\n";
            $msg .= "• Korupsi: {$mutasi->jumlah_disetujui_korupsi} orang\n\n";
            if ($mutasi->catatan_kanwil) {
                $msg .= "Catatan Kanwil: {$mutasi->catatan_kanwil}\n\n";
            }
            $msg .= "_Surat Persetujuan resmi sudah dapat diunduh di aplikasi Sultan Banten oleh UPT Pemohon dan UPT Penerima._\n";
            $msg .= '_Sultan Banten Ditjenpas_';

            return self::send($defaultGroup->group_id, $msg);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
