@extends('layouts.app')

@section('title', 'Buat Usulan Mutasi Narapidana - Sultan Banten')
@section('page_title')
    <i class="fas fa-file-medical me-2 text-primary"></i>Buat Usulan Mutasi Narapidana Antar-UPT
@endsection

@section('content')
<div class="container-fluid px-0" style="max-width: 1050px;">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('mutasi.index') }}" class="text-decoration-none">Mutasi Narapidana</a></li>
            <li class="breadcrumb-item active" aria-current="page">Buat Usulan Baru</li>
        </ol>
    </nav>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 shadow-sm" role="alert">
        <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Mohon lengkapi data berikut:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Info Box: Ketentuan Alur Kanwil -->
    <div class="alert alert-primary bg-primary bg-opacity-10 border-primary border-opacity-25 d-flex align-items-center mb-3 shadow-sm" role="alert">
        <i class="fas fa-info-circle fa-2x text-primary me-3 flex-shrink-0"></i>
        <div class="small">
            <strong class="text-primary d-block mb-1">Ketentuan Penetapan UPT Tujuan Mutasi:</strong>
            UPT Pemohon mengajukan data surat, rincian jumlah narapidana per perkara, dan kelengkapan berkas pendukung. <strong>Penetapan UPT Penerima (Tujuan) dan kuota persetujuan sepenuhnya ditentukan oleh Kantor Wilayah</strong> berdasarkan analisis kapasitas riil dan pertimbangan keamanan se-Banten.
        </div>
    </div>

    <form action="{{ route('mutasi.store') }}" method="POST" enctype="multipart/form-data" id="formMutasi">
        @csrf

        <!-- CARD 1: INFORMASI SURAT USULAN & UPT -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-envelope-open-text me-2 text-primary"></i>1. Identitas Surat Usulan UPT</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">UPT Pengusul (Pemohon) <span class="text-danger">*</span></label>
                        @if($user->isOperator() && $user->upt_id)
                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $user->upt?->nama_upt }}" readonly>
                            <input type="hidden" name="upt_asal_id" value="{{ $user->upt_id }}">
                        @else
                            <select name="upt_asal_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih UPT Pengusul --</option>
                                @foreach($uptList as $u)
                                <option value="{{ $u->id }}" {{ old('upt_asal_id') == $u->id ? 'selected' : '' }}>{{ $u->nama_upt }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Kategori Usulan Mutasi <span class="text-danger">*</span></label>
                        <select name="kategori_mutasi" id="kategoriMutasi" class="form-select form-select-sm" required>
                            <option value="Pembinaan" {{ old('kategori_mutasi', 'Pembinaan') == 'Pembinaan' ? 'selected' : '' }}>Pembinaan</option>
                            <option value="Keamanan" {{ old('kategori_mutasi') == 'Keamanan' ? 'selected' : '' }}>Keamanan</option>
                            <option value="Overkapasitas" {{ old('kategori_mutasi') == 'Overkapasitas' ? 'selected' : '' }}>Overkapasitas</option>
                            <option value="Lainnya" {{ old('kategori_mutasi') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        <div id="divKategoriLainnya" class="mt-2 {{ old('kategori_mutasi') == 'Lainnya' ? '' : 'd-none' }}">
                            <label class="form-label small fw-semibold text-primary">
                                <i class="fas fa-pen me-1"></i>Sebutkan Kategori Mutasi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="kategori_lainnya" id="kategoriLainnya" class="form-control form-control-sm" placeholder="Contoh: Perawatan Medis / Rujukan RS" value="{{ old('kategori_lainnya') }}" maxlength="100" {{ old('kategori_mutasi') == 'Lainnya' ? 'required' : '' }}>
                            <small class="text-muted" style="font-size: 11px;">Ketik rincian kategori usulan mutasi secara manual.</small>
                        </div>
                        <div id="alertBapNotice" class="alert alert-warning py-1 px-2 mt-2 small d-none">
                            <i class="fas fa-shield-alt me-1"></i><strong>Perhatian:</strong> Untuk kategori <strong>Keamanan</strong>, Berita Acara Pemeriksaan (BAP) wajib dilampirkan.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Nomor Surat Pengusulan UPT <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_surat" class="form-control form-control-sm font-monospace" placeholder="Contoh: W.12.PAS.PAS.1-PK.01.05.02-1234" value="{{ old('nomor_surat') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Tanggal Surat Usulan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_surat" class="form-control form-control-sm" value="{{ old('tanggal_surat', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Keterangan / Alasan Ringkas Pengusulan</label>
                        <textarea name="keterangan" class="form-control form-control-sm" rows="3" placeholder="Jelaskan secara singkat latar belakang dan alasan mutasi narapidana ini...">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: PENDATAAN JUMLAH NARAPIDANA PER PERKARA -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-users me-2 text-primary"></i>2. Pendataan Jumlah Narapidana per Klasifikasi Perkara</h6>
                <span class="badge bg-primary fs-6 px-3 py-1" id="badgeTotalUsulan">Total: 0 Orang</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Isi jumlah narapidana yang diusulkan untuk dipindahkan berdasarkan jenis tindak pidananya:</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border bg-light p-3 rounded-3 text-center">
                            <label class="form-label small fw-bold text-danger mb-2">
                                <i class="fas fa-pills me-1"></i> Perkara Narkotika
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="0" name="jumlah_narkotika" id="jumlahNarkotika" class="form-control text-center fs-5 fw-bold text-danger" value="{{ old('jumlah_narkotika', 0) }}" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border bg-light p-3 rounded-3 text-center">
                            <label class="form-label small fw-bold text-primary mb-2">
                                <i class="fas fa-gavel me-1"></i> Pidana Umum (Pidum)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="0" name="jumlah_pidum" id="jumlahPidum" class="form-control text-center fs-5 fw-bold text-primary" value="{{ old('jumlah_pidum', 0) }}" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border bg-light p-3 rounded-3 text-center">
                            <label class="form-label small fw-bold text-warning text-dark mb-2">
                                <i class="fas fa-balance-scale me-1"></i> Korupsi (Tipikor)
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="0" name="jumlah_korupsi" id="jumlahKorupsi" class="form-control text-center fs-5 fw-bold text-warning text-dark" value="{{ old('jumlah_korupsi', 0) }}" required>
                                <span class="input-group-text">Orang</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: KELENGKAPAN BERKAS PERSYARATAN -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-paperclip me-2 text-primary"></i>3. Upload Kelengkapan Berkas Persyaratan</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">1. Surat Usulan Resmi UPT <span class="text-danger">*</span></label>
                        <input type="file" name="file_surat_usulan" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Format: PDF/Gambar (Maks. 10 MB)</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">2. Asesmen Penempatan Narapidana</label>
                        <input type="file" name="file_asesmen" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Hasil asesmen risiko &amp; kebutuhan (PDF, Maks. 10 MB)</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            3. Berita Acara Pemeriksaan (BAP)
                            <span class="text-danger" id="reqStarBap" style="display: none;">*</span>
                        </label>
                        <input type="file" name="file_bap" id="inputFileBap" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Wajib diisi jika mutasi karena alasan <strong>Keamanan</strong></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">4. Buku Catatan Pelanggaran Disiplin (Register F)</label>
                        <input type="file" name="file_reg_f" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Catatan riwayat disiplin WBP (PDF, Maks. 10 MB)</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">5. Lampiran Daftar Nominatif WBP yang Diusulkan</label>
                        <input type="file" name="file_lampiran_wbp" class="form-control form-control-sm" accept=".pdf,.xls,.xlsx,.csv">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Daftar nama, nomor register, perkara, ekspirasi, dan sisa pidana (PDF / Excel / CSV, Maks. 10 MB)</small>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
                <a href="{{ route('mutasi.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fas fa-arrow-left me-1"></i> Batal / Kembali
                </a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="btnSubmitMutasi">
                    <i class="fas fa-paper-plane me-1"></i> Ajukan Usulan ke Kanwil
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputNarkotika = document.getElementById('jumlahNarkotika');
        const inputPidum = document.getElementById('jumlahPidum');
        const inputKorupsi = document.getElementById('jumlahKorupsi');
        const badgeTotal = document.getElementById('badgeTotalUsulan');
        const selectKategori = document.getElementById('kategoriMutasi');
        const divKategoriLainnya = document.getElementById('divKategoriLainnya');
        const inputKategoriLainnya = document.getElementById('kategoriLainnya');
        const alertBap = document.getElementById('alertBapNotice');
        const reqStarBap = document.getElementById('reqStarBap');
        const inputBap = document.getElementById('inputFileBap');

        function updateTotal() {
            const narkotika = parseInt(inputNarkotika.value) || 0;
            const pidum = parseInt(inputPidum.value) || 0;
            const korupsi = parseInt(inputKorupsi.value) || 0;
            const total = narkotika + pidum + korupsi;

            badgeTotal.textContent = 'Total: ' + total + ' Orang';
            if (total > 0) {
                badgeTotal.className = 'badge bg-success fs-6 px-3 py-1';
            } else {
                badgeTotal.className = 'badge bg-secondary fs-6 px-3 py-1';
            }
        }

        function checkKategori(focusInput = false) {
            if (selectKategori.value === 'Keamanan') {
                alertBap.classList.remove('d-none');
                reqStarBap.style.display = 'inline';
                inputBap.setAttribute('required', 'required');
            } else {
                alertBap.classList.add('d-none');
                reqStarBap.style.display = 'none';
                inputBap.removeAttribute('required');
            }

            if (selectKategori.value === 'Lainnya') {
                divKategoriLainnya.classList.remove('d-none');
                inputKategoriLainnya.setAttribute('required', 'required');
                if (focusInput) {
                    inputKategoriLainnya.focus();
                }
            } else {
                divKategoriLainnya.classList.add('d-none');
                inputKategoriLainnya.removeAttribute('required');
            }
        }

        inputNarkotika.addEventListener('input', updateTotal);
        inputPidum.addEventListener('input', updateTotal);
        inputKorupsi.addEventListener('input', updateTotal);
        selectKategori.addEventListener('change', function () {
            checkKategori(true);
        });

        updateTotal();
        checkKategori(false);
    });
</script>
@endpush
