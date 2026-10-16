<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WaGroup;
use App\Models\WaPimpinan;
use App\Models\WaScheduledReport;
use App\Models\WaSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppController extends Controller
{
    // =========================================================================
    // 1. GATEWAY & KONEKSI
    // =========================================================================

    public function gatewayIndex()
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $waSettings = WaSetting::getFirst();
        if (! $waSettings) {
            $waSettings = WaSetting::create([
                'gateway_name' => 'fonnte',
                'api_url' => 'https://api.fonnte.com/send',
                'jam_reminder_pagi' => '07:30',
                'jam_reminder_siang' => '12:00',
                'aktif' => false,
            ]);
        }

        $groups = WaGroup::where('aktif', true)->orderBy('nama_grup')->get();

        return view('wa.gateway', compact('waSettings', 'groups'));
    }

    public function gatewayUpdate(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $waSettings = WaSetting::getFirst();
        if (! $waSettings) {
            $waSettings = new WaSetting;
        }

        $waSettings->gateway_name = $request->input('gateway_name', 'fonnte');
        $waSettings->api_key = trim($request->input('api_key', ''));
        $waSettings->api_url = trim($request->input('api_url', 'https://api.fonnte.com/send'));
        $waSettings->sender_number = trim($request->input('sender_number', ''));
        $waSettings->aktif = $request->has('aktif');
        $waSettings->save();

        ActivityLog::log('setting', 'wa_gateway', 'Memperbarui konfigurasi gateway WhatsApp');

        return back()->with('success', 'Konfigurasi WhatsApp Gateway berhasil disimpan!');
    }

    public function gatewayTest(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $action = $request->input('action');

        if ($action === 'test_message') {
            $testNumber = trim($request->input('test_number', ''));
            $testMessage = trim($request->input('test_message', 'Test notification dari Sultan Banten'));

            if (! $testNumber) {
                return back()->with('error', 'Nomor tujuan harus diisi.');
            }

            $res = WhatsAppService::send($testNumber, $testMessage);

            return $res['success']
                ? back()->with('success', "✅ Pesan uji coba berhasil dikirim ke {$testNumber}")
                : back()->with('error', '❌ Gagal mengirim pesan: '.($res['message'] ?? 'Unknown error'));
        }

        if ($action === 'test_group') {
            $groupId = trim($request->input('group_id', ''));
            if (! $groupId) {
                $settings = WaSetting::getFirst();
                $groupId = trim($settings->group_id ?? '');
            }

            if (! $groupId) {
                return back()->with('error', 'Group ID belum dipilih.');
            }

            $testMessage = "📢 *TEST PESAN GRUP*\n\nIni adalah pesan uji coba dari Sultan Banten Ditjenpas.\n\n_Terkirim: ".date('d/m/Y H:i:s').'_';
            $res = WhatsAppService::send($groupId, $testMessage);

            return $res['success']
                ? back()->with('success', '✅ Pesan uji coba berhasil dikirim ke grup WhatsApp!')
                : back()->with('error', '❌ Gagal mengirim ke grup: '.($res['message'] ?? 'Unknown error'));
        }

        if ($action === 'send_morning') {
            $res = WhatsAppService::sendMorningReminder();

            return $res['success']
                ? back()->with('success', "✅ Reminder pagi berhasil diproses: {$res['message']}")
                : back()->with('error', '❌ Gagal mengirim reminder pagi: '.($res['message'] ?? 'Error'));
        }

        if ($action === 'send_afternoon') {
            $res = WhatsAppService::sendAfternoonReport();

            return $res['success']
                ? back()->with('success', '✅ Laporan siang berhasil dikirim ke grup WhatsApp!')
                : back()->with('error', '❌ Gagal mengirim laporan siang: '.($res['message'] ?? 'Error'));
        }

        return back();
    }

    // Backward-compatible aliases
    public function settingsIndex()
    {
        return $this->gatewayIndex();
    }

    public function settingsUpdate(Request $request)
    {
        return $this->gatewayUpdate($request);
    }

    public function settingsTest(Request $request)
    {
        return $this->gatewayTest($request);
    }

    // =========================================================================
    // 2. BUKU KONTAK & TARGET PENERIMA
    // =========================================================================

    public function contactsIndex()
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule') && ! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $groups = WaGroup::orderByDesc('is_default')->orderBy('nama_grup')->get();
        $pimpinanList = WaPimpinan::orderBy('urutan')->get();
        $operators = User::with('upt')
            ->where('role', 'operator')
            ->orderBy('nama_lengkap')
            ->get();

        return view('wa.contacts', compact('groups', 'pimpinanList', 'operators'));
    }

    public function groupStore(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule') && ! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'nama_grup' => 'required|string|max:100',
            'group_id' => 'required|string|max:100',
        ]);

        $isDefault = $request->has('is_default');
        if ($isDefault) {
            WaGroup::where('is_default', true)->update(['is_default' => false]);
        }

        $group = WaGroup::create([
            'nama_grup' => $request->input('nama_grup'),
            'group_id' => trim($request->input('group_id')),
            'keterangan' => $request->input('keterangan'),
            'aktif' => $request->has('aktif'),
            'is_default' => $isDefault,
        ]);

        if ($isDefault) {
            $setting = WaSetting::getFirst();
            if ($setting) {
                $setting->update(['group_id' => $group->group_id]);
            }
        }

        ActivityLog::log('tambah', 'wa_groups', "Menambahkan grup WA: {$group->nama_grup}");

        return back()->with('success', "Grup {$group->nama_grup} berhasil ditambahkan!");
    }

    public function groupUpdate(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule') && ! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $group = WaGroup::findOrFail($id);

        $request->validate([
            'nama_grup' => 'required|string|max:100',
            'group_id' => 'required|string|max:100',
        ]);

        $isDefault = $request->has('is_default');
        if ($isDefault) {
            WaGroup::where('id', '!=', $id)->update(['is_default' => false]);
            $setting = WaSetting::getFirst();
            if ($setting) {
                $setting->update(['group_id' => trim($request->input('group_id'))]);
            }
        }

        $group->update([
            'nama_grup' => $request->input('nama_grup'),
            'group_id' => trim($request->input('group_id')),
            'keterangan' => $request->input('keterangan'),
            'aktif' => $request->has('aktif'),
            'is_default' => $isDefault,
        ]);

        ActivityLog::log('edit', 'wa_groups', "Mengubah grup WA: {$group->nama_grup}");

        return back()->with('success', "Data grup {$group->nama_grup} berhasil diperbarui!");
    }

    public function groupDestroy($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule') && ! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $group = WaGroup::findOrFail($id);
        $name = $group->nama_grup;
        $group->delete();

        ActivityLog::log('hapus', 'wa_groups', "Menghapus grup WA: {$name}");

        return back()->with('success', "Grup {$name} berhasil dihapus.");
    }

    public function groupToggle($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule') && ! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $group = WaGroup::findOrFail($id);
        $group->aktif = ! $group->aktif;
        $group->save();

        return back()->with('success', "Status grup {$group->nama_grup} diubah!");
    }

    public function pimpinanIndex()
    {
        return $this->contactsIndex();
    }

    public function pimpinanStore(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'nama' => 'required|string|max:100',
            'no_wa' => 'required|string|max:20',
        ]);

        $pimpinan = WaPimpinan::create([
            'nama' => $request->input('nama'),
            'jabatan' => $request->input('jabatan'),
            'no_wa' => $request->input('no_wa'),
            'urutan' => (int) $request->input('urutan', 0),
            'aktif' => $request->has('aktif'),
        ]);

        ActivityLog::log('tambah', 'wa_pimpinan', "Menambahkan pimpinan: {$pimpinan->nama}");

        return back()->with('success', 'Data pimpinan berhasil ditambahkan.');
    }

    public function pimpinanUpdate(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $pimpinan = WaPimpinan::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:100',
            'no_wa' => 'required|string|max:20',
        ]);

        $pimpinan->update([
            'nama' => $request->input('nama'),
            'jabatan' => $request->input('jabatan'),
            'no_wa' => $request->input('no_wa'),
            'urutan' => (int) $request->input('urutan', 0),
            'aktif' => $request->has('aktif'),
        ]);

        ActivityLog::log('edit', 'wa_pimpinan', "Mengubah data pimpinan: {$pimpinan->nama}");

        return back()->with('success', 'Data pimpinan berhasil diperbarui.');
    }

    public function pimpinanDestroy($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $pimpinan = WaPimpinan::findOrFail($id);
        $name = $pimpinan->nama;
        $pimpinan->delete();

        ActivityLog::log('hapus', 'wa_pimpinan', "Menghapus pimpinan: {$name}");

        return back()->with('success', 'Data pimpinan berhasil dihapus.');
    }

    // =========================================================================
    // 3. JADWAL & TEMPLATE PESAN TERPADU
    // =========================================================================

    public function schedulesIndex()
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedules = WaScheduledReport::orderByDesc('id')->get();
        $waSettings = WaSetting::getFirst();
        $groups = WaGroup::where('aktif', true)->orderByDesc('is_default')->orderBy('nama_grup')->get();
        $groupedVariables = WhatsAppService::getGroupedTemplateVariables();
        $variables = WhatsAppService::getTemplateVariables();
        $defaultTemplate = WhatsAppService::getDefaultTemplate();

        return view('wa.schedules', compact('schedules', 'waSettings', 'groups', 'variables', 'groupedVariables', 'defaultTemplate'));
    }

    public function scheduleIndex()
    {
        return $this->schedulesIndex();
    }

    public function updateBuiltinSettings(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $type = $request->input('type');
        $setting = WaSetting::getFirst();
        if (! $setting) {
            $setting = new WaSetting;
        }

        if ($type === 'morning') {
            $setting->jam_reminder_pagi = $request->input('jam_reminder_pagi', '07:30');
            $setting->template_pagi = $request->input('template_pagi', '');
            $setting->save();
            ActivityLog::log('edit', 'wa_builtin_schedule', 'Memperbarui aturan reminder pagi');

            return back()->with('success', 'Aturan pengingat pagi berhasil diperbarui!');
        }

        if ($type === 'afternoon') {
            $setting->jam_reminder_siang = $request->input('jam_reminder_siang', '12:00');
            $setting->template_siang = $request->input('template_siang', '');
            if ($request->has('group_id')) {
                $setting->group_id = trim($request->input('group_id'));
            }
            $setting->save();
            ActivityLog::log('edit', 'wa_builtin_schedule', 'Memperbarui aturan monitoring siang');

            return back()->with('success', 'Aturan monitoring siang berhasil diperbarui!');
        }

        return back();
    }

    public function scheduleStore(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'nama_laporan' => 'required|string|max:100',
            'jam_kirim' => 'required',
        ]);

        $schedule = WaScheduledReport::create([
            'nama_laporan' => $request->input('nama_laporan'),
            'group_id' => $request->input('group_id', '-'),
            'jam_kirim' => $request->input('jam_kirim'),
            'hari' => $request->input('hari', 'daily'),
            'template_pesan' => $request->input('template_pesan'),
            'kirim_ke_pimpinan' => $request->has('kirim_ke_pimpinan'),
            'aktif' => true,
        ]);

        ActivityLog::log('tambah', 'wa_schedule', "Menambahkan jadwal: {$schedule->nama_laporan}");

        return back()->with('success', 'Jadwal laporan berhasil ditambahkan!');
    }

    public function scheduleUpdate(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = WaScheduledReport::findOrFail($id);

        $request->validate([
            'nama_laporan' => 'required|string|max:100',
            'jam_kirim' => 'required',
        ]);

        $schedule->update([
            'nama_laporan' => $request->input('nama_laporan'),
            'group_id' => $request->input('group_id', '-'),
            'jam_kirim' => $request->input('jam_kirim'),
            'hari' => $request->input('hari', 'daily'),
            'template_pesan' => $request->input('template_pesan'),
            'kirim_ke_pimpinan' => $request->has('kirim_ke_pimpinan'),
        ]);

        ActivityLog::log('edit', 'wa_schedule', "Mengubah jadwal: {$schedule->nama_laporan}");

        return back()->with('success', 'Jadwal laporan berhasil diperbarui!');
    }

    public function scheduleDestroy($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = WaScheduledReport::findOrFail($id);
        $name = $schedule->nama_laporan;
        $schedule->delete();

        ActivityLog::log('hapus', 'wa_schedule', "Menghapus jadwal: {$name}");

        return back()->with('success', 'Jadwal laporan berhasil dihapus.');
    }

    public function scheduleToggle($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = WaScheduledReport::findOrFail($id);
        $schedule->aktif = ! $schedule->aktif;
        $schedule->save();

        ActivityLog::log('edit', 'wa_schedule', "Mengubah status jadwal: {$schedule->nama_laporan} menjadi ".($schedule->aktif ? 'Aktif' : 'Nonaktif'));

        return back()->with('success', 'Status jadwal berhasil diubah!');
    }

    public function scheduleSendNow($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $res = WhatsAppService::sendScheduledReport($id);

        return $res['success']
            ? back()->with('success', '✅ Laporan berhasil dikirim sekarang!')
            : back()->with('error', '❌ Gagal mengirim laporan: '.($res['message'] ?? 'Error'));
    }

    // =========================================================================
    // 4. UNIFIED ACTIVITY LOGS & MONITORING
    // =========================================================================

    public function logsIndex(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('wa_schedule') && ! $user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $waSettings = WaSetting::getFirst();
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        // Status Pagi Hari Ini
        $morningCount = DB::table('wa_notification_logs')
            ->where('tipe', 'reminder_pagi')
            ->whereDate('tanggal_kirim', $today)
            ->count();
        $morningStatus = [
            'sent' => $morningCount > 0,
            'count' => $morningCount,
        ];

        // Status Siang Hari Ini
        $afternoonCount = DB::table('wa_notification_logs')
            ->whereIn('tipe', ['reminder_siang', 'laporan_siang'])
            ->whereDate('tanggal_kirim', $today)
            ->count();
        $afternoonStatus = [
            'sent' => $afternoonCount > 0,
            'count' => $afternoonCount,
        ];

        // Query notification logs
        $notifQuery = DB::table('wa_notification_logs')
            ->select([
                'id',
                'tipe',
                'target',
                'pesan',
                'status',
                'response',
                'tanggal_kirim',
                DB::raw("'notification' as log_source"),
                DB::raw('NULL as nama_laporan'),
            ]);

        // Query scheduled logs
        $schedQuery = DB::table('wa_scheduled_report_logs as sl')
            ->leftJoin('wa_scheduled_reports as sr', 'sl.report_id', '=', 'sr.id')
            ->select([
                'sl.id',
                DB::raw("'scheduled' as tipe"),
                DB::raw("COALESCE(sr.group_id, 'Pimpinan') as target"),
                'sl.pesan',
                'sl.status',
                'sl.response',
                'sl.tanggal_kirim',
                DB::raw("'scheduled' as log_source"),
                'sr.nama_laporan',
            ]);

        // Filters
        $typeFilter = $request->query('type');
        $statusFilter = $request->query('status');
        $dateFilter = $request->query('date');

        if ($typeFilter === 'scheduled') {
            $query = $schedQuery;
        } elseif ($typeFilter) {
            $notifQuery->where('tipe', $typeFilter);
            $query = $notifQuery;
        } else {
            $query = $notifQuery->unionAll($schedQuery);
        }

        $logs = DB::query()->fromSub($query, 'unified_logs')
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($dateFilter, fn ($q) => $q->whereDate('tanggal_kirim', $dateFilter))
            ->orderByDesc('tanggal_kirim')
            ->limit(100)
            ->get();

        return view('wa.logs', compact('logs', 'morningStatus', 'afternoonStatus', 'waSettings'));
    }
}
