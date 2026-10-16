<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class WaCronCommand extends Command
{
    protected $signature = 'wa:cron {action=run : The action to run (run, morning, afternoon, scheduled, status)}';

    protected $description = 'Run WhatsApp notification cron tasks (morning reminder, afternoon report, scheduled reports, status)';

    public function handle()
    {
        $action = $this->argument('action');

        if ($action === 'status') {
            $status = WhatsAppService::getStatus();
            $this->info('========================================================================================');
            $this->info('                  SULTAN BANTEN - STATUS OTOMATISASI WHATSAPP                           ');
            $this->info('========================================================================================');
            $this->line('Waktu Server WIB : '.$status['waktu_server_wib'].' | Hari: '.$status['hari_ini']);
            $this->line('Status Master    : '.($status['notifikasi_aktif'] ? '✅ AKTIF (Otomatisasi Berjalan)' : '❌ NONAKTIF (Otomatisasi Dijeda)'));
            $this->line('Gateway API      : '.$status['gateway'].' ('.($status['api_key_terisi'] ? 'API Key Terpasang' : 'API Key KOSONG!').')');
            $this->line('Target Grup Utama: '.$status['group_id_target']);
            $this->line('Operator Siap    : '.$status['total_operator_wa_siap'].' orang | Pimpinan Siap: '.$status['total_pimpinan_aktif'].' orang');
            $this->line('Jadwal Aktif     : '.$status['jadwal_aktif'].' dari '.$status['total_jadwal'].' total jadwal (sistem + kustom)');
            $this->info('----------------------------------------------------------------------------------------');
            $this->info(' DAFTAR SELURUH JADWAL OTOMATISASI WHATSAPP (SISTEM & KUSTOM):');
            $this->info('----------------------------------------------------------------------------------------');

            if (! empty($status['daftar_jadwal'])) {
                $this->table(
                    ['ID', 'Nama Laporan / Tugas', 'Jam', 'Frekuensi', 'Status', 'Target', 'Status Hari Ini', 'Terakhir Kirim'],
                    array_map(function ($row) {
                        return [
                            $row['id'],
                            $row['nama'],
                            $row['jam'],
                            $row['frekuensi'] ?? ($row['hari'] ?? 'Setiap Hari'),
                            $row['status'],
                            $row['target'],
                            $row['status_hari_ini'] ?? '-',
                            $row['terakhir_kirim'],
                        ];
                    }, $status['daftar_jadwal'])
                );
            }

            return 0;
        }

        $this->info("Running WhatsApp cron task with action: {$action}");

        $result = WhatsAppService::runCron($action);

        $logPath = storage_path('logs/cron-wa.log');
        $logDir = dirname($logPath);
        if (! is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logLine = sprintf(
            "[%s] ARTISAN WA:CRON - Action: %s - Result: %s\n",
            now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            $action,
            json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($logPath, $logLine, FILE_APPEND);

        if (! empty($result['success'])) {
            $this->info('Cron task completed successfully: '.json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->warn('Cron task returned: '.($result['message'] ?? 'Failed'));
        }

        return 0;
    }
}
