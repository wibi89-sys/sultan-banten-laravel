<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataIntegrasi;
use App\Models\Upt;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntegrasiController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        if (! $user->hasAccess('integrasi')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DataIntegrasi::with('upt')->orderByDesc('tanggal')->orderByDesc('id');

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

        $integrasiList = $query->paginate(50)->withQueryString();

        $timeRestriction = getTimeRestriction();
        $timeCheck = isInputTimeAllowed();
        $canInput = $timeCheck['allowed'];
        $timeRestrictionMessage = $timeCheck['message'];

        return view('integrasi.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'tanggalMulai',
            'tanggalSampai',
            'integrasiList',
            'timeRestriction',
            'canInput',
            'timeRestrictionMessage'
        ));
    }

    public function checkExists(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('integrasi')) {
            return response()->json(['allowed' => false, 'message' => 'Unauthorized'], 403);
        }

        $uptId = (int) $request->query('upt_id');
        $rawTanggal = $request->query('tanggal');
        $excludeId = (int) $request->query('exclude_id', 0);

        if (! $uptId || ! $rawTanggal) {
            return response()->json(['exists' => false]);
        }

        try {
            $tanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception) {
            return response()->json(['exists' => false]);
        }

        $query = DataIntegrasi::where('upt_id', $uptId)->where('tanggal', $tanggal);
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
                ? 'Data integrasi untuk '.($upt?->nama_upt ?? 'UPT ini').' pada tanggal '.formatDate($tanggal).' sudah ada.'
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('integrasi')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message'])->withInput();
        }

        $uptId = (int) $request->input('upt_id');
        $rawTanggal = $request->input('tanggal');
        $forceUpdate = $request->boolean('force_update') || $request->input('force_update') === '1';

        if (! $user->isAdmin() && $user->upt_id && $uptId !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda hanya dapat menginput data untuk UPT Anda sendiri.');
        }

        try {
            $tanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception) {
            return back()->with('error', 'Format tanggal tidak valid.')->withInput();
        }

        if (! $forceUpdate) {
            $validator = Validator::make(array_merge($request->all(), ['tanggal' => $tanggal]), [
                'upt_id' => 'required|exists:upt,id',
                'tanggal' => [
                    'required',
                    'date',
                    Rule::unique('data_integrasi', 'tanggal')->where(fn ($query) => $query->where('upt_id', $uptId)),
                ],
            ], [
                'tanggal.unique' => 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first())->withInput();
            }
        } else {
            $validator = Validator::make(array_merge($request->all(), ['tanggal' => $tanggal]), [
                'upt_id' => 'required|exists:upt,id',
                'tanggal' => 'required|date',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first())->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $existing = DataIntegrasi::where('upt_id', $uptId)->where('tanggal', $tanggal)->lockForUpdate()->first();

            if ($existing && ! $forceUpdate) {
                DB::rollBack();

                return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan konfirmasi untuk meng-update atau batalkan.')->withInput();
            }

            $isUpdate = (bool) $existing;

            $payload = [
                'usulan_pb' => (int) $request->input('usulan_pb', 0),
                'usulan_cb' => (int) $request->input('usulan_cb', 0),
                'usulan_cmb' => (int) $request->input('usulan_cmb', 0),
                'usulan_cmk' => (int) $request->input('usulan_cmk', 0),
                'usulan_asimilasi' => (int) $request->input('usulan_asimilasi', 0),
                'sk_terbit_pb' => (int) $request->input('sk_terbit_pb', 0),
                'sk_terbit_cb' => (int) $request->input('sk_terbit_cb', 0),
                'sk_terbit_cmb' => (int) $request->input('sk_terbit_cmb', 0),
                'sk_terbit_cmk' => (int) $request->input('sk_terbit_cmk', 0),
                'sk_terbit_asimilasi' => (int) $request->input('sk_terbit_asimilasi', 0),
                'keterangan' => $request->input('keterangan', ''),
            ];

            $data = DataIntegrasi::updateOrCreate(
                [
                    'upt_id' => $uptId,
                    'tanggal' => $tanggal,
                ],
                $payload
            );

            DB::commit();

            $upt = Upt::find($uptId);
            $uptName = $upt?->nama_upt ?? 'UPT';
            $action = $isUpdate ? 'edit' : 'tambah';
            $logMsg = $isUpdate
                ? "Memperbarui data integrasi UPT {$uptName} tanggal {$tanggal} via form input"
                : "Menambahkan data integrasi UPT {$uptName} tanggal {$tanggal}";

            ActivityLog::log($action, 'integrasi', $logMsg, ['upt_id' => $uptId, 'tanggal' => $tanggal]);

            $successMsg = $isUpdate
                ? "Data integrasi untuk UPT {$uptName} tanggal ".formatDate($tanggal).' berhasil diperbarui.'
                : 'Data integrasi berhasil ditambahkan.';

            return back()->with('success', $successMsg);
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();

            return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan konfirmasi untuk meng-update atau batalkan.')->withInput();
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? null) == 1062) {
                return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan konfirmasi untuk meng-update atau batalkan.')->withInput();
            }

            return back()->with('error', 'Gagal menyimpan data integrasi: '.$e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('integrasi')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message'])->withInput();
        }

        $data = DataIntegrasi::findOrFail($id);
        if (! $user->isAdmin() && $user->upt_id && ((int) $data->upt_id !== (int) $user->upt_id || (int) $request->input('upt_id') !== (int) $user->upt_id)) {
            abort(403, 'Akses ditolak. Anda tidak berhak mengubah data milik UPT lain.');
        }

        $uptId = (int) $request->input('upt_id');
        $rawTanggal = $request->input('tanggal');

        try {
            $tanggal = Carbon::parse($rawTanggal)->format('Y-m-d');
        } catch (\Exception) {
            return back()->with('error', 'Format tanggal tidak valid.')->withInput();
        }

        $validator = Validator::make(array_merge($request->all(), ['tanggal' => $tanggal]), [
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => [
                'required',
                'date',
                Rule::unique('data_integrasi', 'tanggal')
                    ->where(fn ($query) => $query->where('upt_id', $uptId))
                    ->ignore($id),
            ],
        ], [
            'tanggal.unique' => 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        if (DataIntegrasi::where('upt_id', $uptId)->where('tanggal', $tanggal)->where('id', '!=', $id)->exists()) {
            return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan pilih tanggal lain.')->withInput();
        }

        try {
            $data->update([
                'upt_id' => $uptId,
                'usulan_pb' => (int) $request->input('usulan_pb', 0),
                'usulan_cb' => (int) $request->input('usulan_cb', 0),
                'usulan_cmb' => (int) $request->input('usulan_cmb', 0),
                'usulan_cmk' => (int) $request->input('usulan_cmk', 0),
                'usulan_asimilasi' => (int) $request->input('usulan_asimilasi', 0),
                'sk_terbit_pb' => (int) $request->input('sk_terbit_pb', 0),
                'sk_terbit_cb' => (int) $request->input('sk_terbit_cb', 0),
                'sk_terbit_cmb' => (int) $request->input('sk_terbit_cmb', 0),
                'sk_terbit_cmk' => (int) $request->input('sk_terbit_cmk', 0),
                'sk_terbit_asimilasi' => (int) $request->input('sk_terbit_asimilasi', 0),
                'tanggal' => $tanggal,
                'keterangan' => $request->input('keterangan', ''),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
        } catch (QueryException $e) {
            if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? null) == 1062) {
                return back()->with('error', 'Data integrasi untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
            }

            return back()->with('error', 'Gagal memperbarui data: '.$e->getMessage())->withInput();
        }

        ActivityLog::log('edit', 'integrasi', "Mengubah data integrasi ID {$id}", $data->toArray());

        return back()->with('success', 'Data integrasi berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('integrasi')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $data = DataIntegrasi::findOrFail($id);
        if (! $user->isAdmin() && $user->upt_id && (int) $data->upt_id !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda tidak berhak menghapus data milik UPT lain.');
        }

        $old = $data->toArray();
        $data->delete();

        ActivityLog::log('hapus', 'integrasi', "Menghapus data integrasi ID {$id}", $old);

        return back()->with('success', 'Data integrasi berhasil dihapus.');
    }
}
