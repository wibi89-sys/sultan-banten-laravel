<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataPenghuni;
use App\Models\Upt;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenghuniController extends Controller
{
    private $klasifikasiKeys = [
        'Pidana Umum' => 'pidana_umum',
        'Tipikor' => 'tipikor',
        'Narkoba Bandar' => 'narkoba_bandar',
        'Narkoba Pengguna' => 'narkoba_pengguna',
        'Teroris' => 'teroris',
        'Trafficking' => 'trafficking',
        'Ilegal Logging' => 'ilegal_logging',
        'Money Laundering' => 'money_laundering',
        'Genosida' => 'genosida',
        'UU ITE' => 'uu_ite',
        'WNA' => 'wna',
        'Sakit Berkepanjangan' => 'sakit_berkepanjangan',
        'Lansia >70 tahun' => 'lansia_70_tahun',
        'Pidana Mati' => 'pidana_mati',
        'Seumur Hidup' => 'seumur_hidup',
    ];

    private $klasifikasiUmum = ['Pidana Umum'];

    private $klasifikasiKhusus = ['Tipikor', 'Narkoba Bandar', 'Narkoba Pengguna', 'Teroris', 'Trafficking', 'Ilegal Logging', 'Money Laundering', 'Genosida'];

    private $klasifikasiUUITE = ['UU ITE'];

    private $klasifikasiLainnya = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('penghuni')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $selectedTab = $request->query('tab', 'all');
        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DataPenghuni::with('upt')->orderByDesc('tanggal')->orderByDesc('id');

        if ($tanggalMulai && $tanggalSampai) {
            $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSampai]);
        } elseif ($tanggalMulai) {
            $query->where('tanggal', '>=', $tanggalMulai);
        } elseif ($tanggalSampai) {
            $query->where('tanggal', '<=', $tanggalSampai);
        }

        if ($filterUpt > 0) {
            $query->where('upt_id', $filterUpt);
        }

        $klasifikasiUtama = array_merge($this->klasifikasiUmum, $this->klasifikasiKhusus, $this->klasifikasiUUITE);
        $allKlasifikasi = array_merge($klasifikasiUtama, $this->klasifikasiLainnya);

        if ($selectedTab === 'utama') {
            $query->whereIn('klasifikasi_pidana', $klasifikasiUtama);
        } elseif ($selectedTab === 'lainnya') {
            $query->whereIn('klasifikasi_pidana', $this->klasifikasiLainnya);
        } elseif ($selectedTab !== 'all' && in_array($selectedTab, $allKlasifikasi)) {
            $query->where('klasifikasi_pidana', $selectedTab);
        }

        $penghuniList = $query->paginate(50)->withQueryString();
        $klasifikasiKeys = $this->klasifikasiKeys;
        $klasifikasiUmum = $this->klasifikasiUmum;
        $klasifikasiKhusus = $this->klasifikasiKhusus;
        $klasifikasiUUITE = $this->klasifikasiUUITE;
        $klasifikasiLainnya = $this->klasifikasiLainnya;

        $timeRestriction = getTimeRestriction();
        $timeCheck = isInputTimeAllowed();
        $canInput = $timeCheck['allowed'];
        $timeRestrictionMessage = $timeCheck['message'];

        return view('penghuni.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'selectedTab',
            'tanggalMulai',
            'tanggalSampai',
            'penghuniList',
            'klasifikasiKeys',
            'klasifikasiUmum',
            'klasifikasiKhusus',
            'klasifikasiUUITE',
            'klasifikasiLainnya',
            'klasifikasiUtama',
            'timeRestriction',
            'canInput',
            'timeRestrictionMessage'
        ));
    }

    public function getPreviousData(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('penghuni')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $uptId = (int) $request->query('upt_id');
        $tanggal = $request->query('tanggal', date('Y-m-d'));

        if (! $uptId) {
            return response()->json(['success' => false, 'has_data' => false, 'message' => 'Pilih UPT terlebih dahulu.']);
        }

        // Input data tidak ada hari libur: ambil tepat data hari kemarin (H-1)
        $previousDate = date('Y-m-d', strtotime($tanggal.' - 1 day'));

        $records = DataPenghuni::where('upt_id', $uptId)
            ->whereDate('tanggal', $previousDate)
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => true,
                'has_data' => false,
                'previous_date' => $previousDate,
                'formatted_yesterday' => formatIndonesianDate($previousDate),
                'message' => 'Data hari kemarin ('.formatIndonesianDate($previousDate).') belum diinput untuk UPT ini. Silakan input data hari kemarin terlebih dahulu atau input manual dari 0.',
            ]);
        }

        $data = [];
        foreach ($records as $r) {
            $key = $this->klasifikasiKeys[$r->klasifikasi_pidana] ?? null;
            if ($key) {
                $data[$key] = [
                    'tahanan_dl' => (int) $r->tahanan_dewasa_laki,
                    'tahanan_dp' => (int) $r->tahanan_dewasa_perempuan,
                    'tahanan_al' => (int) $r->tahanan_anak_laki,
                    'tahanan_ap' => (int) $r->tahanan_anak_perempuan,
                    'narapidana_dl' => (int) $r->narapidana_dewasa_laki,
                    'narapidana_dp' => (int) $r->narapidana_dewasa_perempuan,
                    'narapidana_al' => (int) $r->narapidana_anak_laki,
                    'narapidana_ap' => (int) $r->narapidana_anak_perempuan,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'has_data' => count($data) > 0,
            'previous_date' => $previousDate,
            'formatted_date' => formatIndonesianDate($previousDate),
            'data' => $data,
        ]);
    }

    public function checkExists(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('penghuni')) {
            return response()->json(['allowed' => false, 'message' => 'Unauthorized'], 403);
        }

        $uptId = (int) $request->query('upt_id');
        $tanggal = $request->query('tanggal');
        $excludeId = (int) $request->query('exclude_id', 0);

        if (! $uptId || ! $tanggal) {
            return response()->json(['exists' => false]);
        }

        $query = DataPenghuni::where('upt_id', $uptId)->whereDate('tanggal', $tanggal);
        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();
        $upt = Upt::find($uptId);

        return response()->json([
            'exists' => $exists,
            'upt_name' => $upt?->nama_upt ?? 'UPT',
            'tanggal_formatted' => formatDate($tanggal),
            'message' => $exists
                ? 'Data penghuni untuk '.($upt?->nama_upt ?? 'UPT ini').' pada tanggal '.formatDate($tanggal).' sudah ada.'
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('penghuni')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message'])->withInput();
        }

        $request->validate([
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => 'required|date',
        ]);

        $uptId = (int) $request->input('upt_id');
        $tanggal = $request->input('tanggal');
        $forceUpdate = $request->boolean('force_update') || $request->input('force_update') === '1';

        if (! $user->isAdmin() && $user->upt_id && $uptId !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda hanya dapat menginput data untuk UPT Anda sendiri.');
        }

        $isExisting = DataPenghuni::where('upt_id', $uptId)->whereDate('tanggal', $tanggal)->exists();

        if ($isExisting && ! $forceUpdate) {
            return back()->with('error', 'Data penghuni untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan konfirmasi untuk meng-update atau batalkan.')->withInput();
        }

        DB::beginTransaction();
        try {
            $processed = 0;
            foreach ($this->klasifikasiKeys as $klasifikasi => $key) {
                DataPenghuni::updateOrCreate(
                    [
                        'upt_id' => $uptId,
                        'tanggal' => $tanggal,
                        'klasifikasi_pidana' => $klasifikasi,
                    ],
                    [
                        'tahanan_dewasa_laki' => (int) $request->input('tahanan_dl_'.$key, 0),
                        'tahanan_dewasa_perempuan' => (int) $request->input('tahanan_dp_'.$key, 0),
                        'tahanan_anak_laki' => (int) $request->input('tahanan_al_'.$key, 0),
                        'tahanan_anak_perempuan' => (int) $request->input('tahanan_ap_'.$key, 0),
                        'narapidana_dewasa_laki' => (int) $request->input('narapidana_dl_'.$key, 0),
                        'narapidana_dewasa_perempuan' => (int) $request->input('narapidana_dp_'.$key, 0),
                        'narapidana_anak_laki' => (int) $request->input('narapidana_al_'.$key, 0),
                        'narapidana_anak_perempuan' => (int) $request->input('narapidana_ap_'.$key, 0),
                        'keterangan' => $request->input('keterangan', ''),
                    ]
                );
                $processed++;
            }

            DB::commit();

            $upt = Upt::find($uptId);
            $action = $isExisting ? 'edit' : 'tambah';
            $msg = $isExisting
                ? "Memperbarui {$processed} data penghuni untuk UPT {$upt->nama_upt} tanggal {$tanggal} via form input"
                : "Menambahkan {$processed} data penghuni untuk UPT {$upt->nama_upt} tanggal {$tanggal}";

            ActivityLog::log($action, 'penghuni', $msg, ['upt_id' => $uptId, 'tanggal' => $tanggal]);

            $successMsg = $isExisting
                ? "Data penghuni untuk UPT {$upt->nama_upt} tanggal ".formatDate($tanggal).' berhasil diperbarui.'
                : "Berhasil menyimpan {$processed} data penghuni.";

            return back()->with('success', $successMsg);
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();

            return back()->with('error', 'Data penghuni untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? null) == 1062) {
                return back()->with('error', 'Data penghuni untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
            }

            return back()->with('error', 'Gagal menyimpan data penghuni: '.$e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan data: '.$e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('penghuni')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message'])->withInput();
        }

        $penghuni = DataPenghuni::findOrFail($id);
        if (! $user->isAdmin() && $user->upt_id && (int) $penghuni->upt_id !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda tidak berhak mengubah data milik UPT lain.');
        }

        $request->validate([
            'klasifikasi_pidana' => 'required',
            'tanggal' => 'required|date',
        ]);

        $tanggal = $request->input('tanggal');
        $klasifikasi = $request->input('klasifikasi_pidana');

        $existsOther = DataPenghuni::where('upt_id', $penghuni->upt_id)
            ->whereDate('tanggal', $tanggal)
            ->where('klasifikasi_pidana', $klasifikasi)
            ->where('id', '!=', $id)
            ->exists();

        if ($existsOther) {
            return back()->with('error', 'Data penghuni untuk klasifikasi '.$klasifikasi.' pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
        }

        $penghuni->update([
            'klasifikasi_pidana' => $klasifikasi,
            'tahanan_dewasa_laki' => (int) $request->input('tahanan_dewasa_laki', 0),
            'tahanan_dewasa_perempuan' => (int) $request->input('tahanan_dewasa_perempuan', 0),
            'tahanan_anak_laki' => (int) $request->input('tahanan_anak_laki', 0),
            'tahanan_anak_perempuan' => (int) $request->input('tahanan_anak_perempuan', 0),
            'narapidana_dewasa_laki' => (int) $request->input('narapidana_dewasa_laki', 0),
            'narapidana_dewasa_perempuan' => (int) $request->input('narapidana_dewasa_perempuan', 0),
            'narapidana_anak_laki' => (int) $request->input('narapidana_anak_laki', 0),
            'narapidana_anak_perempuan' => (int) $request->input('narapidana_anak_perempuan', 0),
            'tanggal' => $tanggal,
            'keterangan' => $request->input('keterangan', ''),
        ]);

        ActivityLog::log('edit', 'penghuni', "Mengubah data penghuni ID {$id} ({$penghuni->klasifikasi_pidana})", $penghuni->toArray());

        return back()->with('success', 'Data penghuni berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        if (! $user->hasAccess('penghuni')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $penghuni = DataPenghuni::findOrFail($id);
        if (! $user->isAdmin() && $user->upt_id && (int) $penghuni->upt_id !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda tidak berhak menghapus data milik UPT lain.');
        }

        $old = $penghuni->toArray();
        $penghuni->delete();

        ActivityLog::log('hapus', 'penghuni', "Menghapus data penghuni ID {$id}", $old);

        return back()->with('success', 'Data penghuni berhasil dihapus.');
    }
}
