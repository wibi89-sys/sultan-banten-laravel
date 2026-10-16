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
            $this->info('==================================================');
            $this->info(' SULTAN BANTEN - STATUS OTOMATISASI WHATSAPP');
            $this->info('==================================================');
            $this->line('Waktu Server   : '.$status['waktu_server_wib']);
            $this->line('Hari Ini       : '.$status['hari_ini']);
            $this->line('Status Master  : '.($status['notifikasi_aktif'] ? '✅ AKTIF' : '❌ NONAKTIF'));
            $this->line('Gateway API    : '.$status['gateway'].' ('.($status['api_key_terisi'] ? 'API Key Terpasang' : 'API Key KOSONG!').')');
            $this->line('Target Grup    : '.$status['group_id_target']);
            $this->line('Reminder Pagi  : '.$status['jam_reminder_pagi']);
            $this->line('Reminder Siang : '.$status['jam_reminder_siang']);
            $this->line('Operator Siap  : '.$status['total_operator_wa_siap'].' orang');
            $this->line('Pimpinan Siap  : '.$status['total_pimpinan_aktif'].' orang');
            $this->line('Jadwal Aktif   : '.$status['jadwal_aktif'].' dari '.$status['total_jadwal'].' jadwal');
            $this->info('--------------------------------------------------');

            if (! empty($status['daftar_jadwal'])) {
                $this->table(
                    ['ID', 'Nama Laporan', 'Jam', 'Frekuensi', 'Status', 'Target', 'Terakhir Kirim'],
                    $status['daftar_jadwal']
                );
            }

            return 0;
        }

        $this->info("Running WhatsApp cron task with action: {$action}");

        $result = WhatsAppService::runCron($action);

        if (! empty($result['success'])) {
            $this->info('Cron task completed successfully: '.json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->warn('Cron task returned: '.($result['message'] ?? 'Failed'));
        }

        return 0;
    }
}
