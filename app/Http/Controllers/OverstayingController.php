<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DataOverstaying;
use App\Models\Upt;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OverstayingController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        if (! $user->hasAccess('overstaying')) {
            abort(403);
        }

        $uptList = Upt::orderBy('nama_upt')->get();
        $userUptFilter = $user->getUserUptFilter();

        $tanggalMulai = $request->query('tanggal_mulai', '');
        $tanggalSampai = $request->query('tanggal_sampai', '');
        $filterUpt = $userUptFilter ?: (int) $request->query('upt_id', 0);

        $query = DataOverstaying::with('upt')->orderByDesc('tanggal')->orderByDesc('id');

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

        $overstayingList = $query->paginate(50)->withQueryString();

        $timeRestriction = getTimeRestriction();
        $timeCheck = isInputTimeAllowed();
        $canInput = $timeCheck['allowed'];
        $timeRestrictionMessage = $timeCheck['message'];

        return view('overstaying.index', compact(
            'uptList',
            'userUptFilter',
            'filterUpt',
            'tanggalMulai',
            'tanggalSampai',
            'overstayingList',
            'timeRestriction',
            'canInput',
            'timeRestrictionMessage'
        ));
    }

    public function checkExists(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('overstaying')) {
            return response()->json(['allowed' => false, 'message' => 'Unauthorized'], 403);
        }

        $uptId = (int) $request->query('upt_id');
        $tanggal = $request->query('tanggal');
        $excludeId = (int) $request->query('exclude_id', 0);

        if (! $uptId || ! $tanggal) {
            return response()->json(['exists' => false]);
        }

        $query = DataOverstaying::where('upt_id', $uptId)->whereDate('tanggal', $tanggal);
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
                ? 'Data overstaying untuk '.($upt?->nama_upt ?? 'UPT ini').' pada tanggal '.formatDate($tanggal).' sudah ada.'
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('overstaying')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message'])->withInput();
        }

        $uptId = (int) $request->input('upt_id');
        $tanggal = $request->input('tanggal');
        $forceUpdate = $request->boolean('force_update') || $request->input('force_update') === '1';

        if (! $user->isAdmin() && $user->upt_id && $uptId !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda hanya dapat menginput data untuk UPT Anda sendiri.');
        }

        if (! $forceUpdate) {
            $validator = Validator::make($request->all(), [
                'upt_id' => 'required|exists:upt,id',
                'tanggal' => [
                    'required',
                    'date',
                    Rule::unique('data_overstaying', 'tanggal')->where(fn ($query) => $query->where('upt_id', $uptId)),
                ],
            ], [
                'tanggal.unique' => 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first())->withInput();
            }
        } else {
            $validator = Validator::make($request->all(), [
                'upt_id' => 'required|exists:upt,id',
                'tanggal' => 'required|date',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first())->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $existing = DataOverstaying::where('upt_id', $uptId)->whereDate('tanggal', $tanggal)->lockForUpdate()->first();

            if ($existing) {
                if (! $forceUpdate) {
                    DB::rollBack();

                    return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan konfirmasi untuk meng-update atau batalkan.')->withInput();
                }

                $existing->update([
                    'tahanan_ai' => (int) $request->input('tahanan_ai', 0),
                    'tahanan_aii' => (int) $request->input('tahanan_aii', 0),
                    'tahanan_aiii' => (int) $request->input('tahanan_aiii', 0),
                    'tahanan_aiv' => (int) $request->input('tahanan_aiv', 0),
                    'tahanan_av' => (int) $request->input('tahanan_av', 0),
                    'overstaying_ai' => (int) $request->input('overstaying_ai', 0),
                    'overstaying_aii' => (int) $request->input('overstaying_aii', 0),
                    'overstaying_aiii' => (int) $request->input('overstaying_aiii', 0),
                    'overstaying_aiv' => (int) $request->input('overstaying_aiv', 0),
                    'overstaying_av' => (int) $request->input('overstaying_av', 0),
                    'keterangan' => $request->input('keterangan', ''),
                ]);

                DB::commit();

                $upt = Upt::find($uptId);
                ActivityLog::log('edit', 'overstaying', "Memperbarui data overstaying UPT {$upt->nama_upt} tanggal {$tanggal} via form input", ['upt_id' => $uptId, 'tanggal' => $tanggal]);

                return back()->with('success', "Data overstaying untuk UPT {$upt->nama_upt} tanggal ".formatDate($tanggal).' berhasil diperbarui.');
            }

            $data = DataOverstaying::create([
                'upt_id' => $uptId,
                'tahanan_ai' => (int) $request->input('tahanan_ai', 0),
                'tahanan_aii' => (int) $request->input('tahanan_aii', 0),
                'tahanan_aiii' => (int) $request->input('tahanan_aiii', 0),
                'tahanan_aiv' => (int) $request->input('tahanan_aiv', 0),
                'tahanan_av' => (int) $request->input('tahanan_av', 0),
                'overstaying_ai' => (int) $request->input('overstaying_ai', 0),
                'overstaying_aii' => (int) $request->input('overstaying_aii', 0),
                'overstaying_aiii' => (int) $request->input('overstaying_aiii', 0),
                'overstaying_aiv' => (int) $request->input('overstaying_aiv', 0),
                'overstaying_av' => (int) $request->input('overstaying_av', 0),
                'tanggal' => $tanggal,
                'keterangan' => $request->input('keterangan', ''),
            ]);

            DB::commit();
        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();

            return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? null) == 1062) {
                return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
            }

            return back()->with('error', 'Gagal menyimpan data overstaying: '.$e->getMessage())->withInput();
        }

        $upt = Upt::find($uptId);
        ActivityLog::log('tambah', 'overstaying', "Menambahkan data overstaying UPT {$upt->nama_upt} tanggal {$tanggal}", ['upt_id' => $uptId, 'tanggal' => $tanggal]);

        return back()->with('success', 'Data overstaying berhasil ditambahkan.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('overstaying')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message'])->withInput();
        }

        $data = DataOverstaying::findOrFail($id);
        if (! $user->isAdmin() && $user->upt_id && ((int) $data->upt_id !== (int) $user->upt_id || (int) $request->input('upt_id') !== (int) $user->upt_id)) {
            abort(403, 'Akses ditolak. Anda tidak berhak mengubah data milik UPT lain.');
        }

        $uptId = (int) $request->input('upt_id');
        $tanggal = $request->input('tanggal');

        $validator = Validator::make($request->all(), [
            'upt_id' => 'required|exists:upt,id',
            'tanggal' => [
                'required',
                'date',
                Rule::unique('data_overstaying', 'tanggal')
                    ->where(fn ($query) => $query->where('upt_id', $uptId))
                    ->ignore($id),
            ],
        ], [
            'tanggal.unique' => 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        if (DataOverstaying::where('upt_id', $uptId)->whereDate('tanggal', $tanggal)->where('id', '!=', $id)->exists()) {
            return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada. Silakan pilih tanggal lain atau gunakan data yang sudah ada.')->withInput();
        }

        try {
            $data->update([
                'upt_id' => $uptId,
                'tahanan_ai' => (int) $request->input('tahanan_ai', 0),
                'tahanan_aii' => (int) $request->input('tahanan_aii', 0),
                'tahanan_aiii' => (int) $request->input('tahanan_aiii', 0),
                'tahanan_aiv' => (int) $request->input('tahanan_aiv', 0),
                'tahanan_av' => (int) $request->input('tahanan_av', 0),
                'overstaying_ai' => (int) $request->input('overstaying_ai', 0),
                'overstaying_aii' => (int) $request->input('overstaying_aii', 0),
                'overstaying_aiii' => (int) $request->input('overstaying_aiii', 0),
                'overstaying_aiv' => (int) $request->input('overstaying_aiv', 0),
                'overstaying_av' => (int) $request->input('overstaying_av', 0),
                'tanggal' => $tanggal,
                'keterangan' => $request->input('keterangan', ''),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
        } catch (QueryException $e) {
            if ($e->getCode() == 23000 || ($e->errorInfo[1] ?? null) == 1062) {
                return back()->with('error', 'Data overstaying untuk UPT ini pada tanggal '.formatDate($tanggal).' sudah ada.')->withInput();
            }

            return back()->with('error', 'Gagal memperbarui data: '.$e->getMessage())->withInput();
        }

        ActivityLog::log('edit', 'overstaying', "Mengubah data overstaying ID {$id}", $data->toArray());

        return back()->with('success', 'Data overstaying berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->hasAccess('overstaying')) {
            abort(403);
        }

        $timeCheck = isInputTimeAllowed();
        if (! $timeCheck['allowed']) {
            return back()->with('error', $timeCheck['message']);
        }

        $data = DataOverstaying::findOrFail($id);
        if (! $user->isAdmin() && $user->upt_id && (int) $data->upt_id !== (int) $user->upt_id) {
            abort(403, 'Akses ditolak. Anda tidak berhak menghapus data milik UPT lain.');
        }

        $old = $data->toArray();
        $data->delete();

        ActivityLog::log('hapus', 'overstaying', "Menghapus data overstaying ID {$id}", $old);

        return back()->with('success', 'Data overstaying berhasil dihapus.');
    }
}
