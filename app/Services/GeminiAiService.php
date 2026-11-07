<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    /**
     * Susun konfigurasi generationConfig yang kompatibel untuk model Gemini 1.5, 2.5, dan 3.x (termasuk 3.8 Flash).
     *
     * @param  array<string, mixed>  $extraConfig
     * @return array<string, mixed>
     */
    public static function buildGenerationConfig(string $chosenModel, array $extraConfig = []): array
    {
        $lower = strtolower($chosenModel);
        $config = array_merge([
            'temperature' => 0.1,
        ], $extraConfig);

        // Gemini 2.5 Flash mendukung thinkingBudget = 0 untuk menonaktifkan reasoning
        if (str_contains($lower, '2.5-flash')) {
            $config['thinkingConfig'] = [
                'thinkingBudget' => 0,
            ];
        }
        // Gemini 3.x (misal gemini-3.8-flash, gemini-3.7-flash) menggunakan thinkingLevel ('LOW', 'MEDIUM', 'HIGH')
        elseif (str_contains($lower, '3.') || str_contains($lower, '3-')) {
            $config['thinkingConfig'] = [
                'thinkingLevel' => 'LOW',
            ];
        }

        return $config;
    }

    /**
     * Ekstrak teks balasan utama dari candidates parts Gemini,
     * mengabaikan part internal reasoning/thought bila ada (misal pada Gemini 3.8 Flash).
     *
     * @param  array<string, mixed>|null  $candidate
     */
    public static function extractTextFromCandidate(?array $candidate): ?string
    {
        if (! $candidate || empty($candidate['content']['parts'])) {
            return null;
        }

        $parts = $candidate['content']['parts'];
        $nonThoughtTexts = [];

        foreach ($parts as $part) {
            // Pada Gemini 3.8 Flash dan model reasoning, internal thought ditandai dengan 'thought' => true
            if (empty($part['thought']) && isset($part['text']) && trim($part['text']) !== '') {
                $nonThoughtTexts[] = $part['text'];
            }
        }

        if (! empty($nonThoughtTexts)) {
            return implode("\n", $nonThoughtTexts);
        }

        // Fallback jika semua part dianggap thought atau format lama
        return $parts[count($parts) - 1]['text'] ?? ($parts[0]['text'] ?? null);
    }

    /**
     * Bersihkan teks dari format markdown ```json ... ``` agar aman di-decode oleh json_decode.
     */
    public static function cleanJsonPayload(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $clean = trim($text);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);
        }

        return trim($clean);
    }

    /**
     * Dapatkan daftar model fallback jika model utama sedang mengalami lonjakan beban (High Demand) di server Google.
     *
     * @return array<int, string>
     */
    public static function getFallbackModels(string $primaryModel): array
    {
        $fallbacks = ['gemini-2.5-flash', 'gemini-1.5-flash'];

        return array_values(array_filter($fallbacks, fn ($m) => $m !== $primaryModel));
    }

    /**
     * Cek apakah pesan error dari Google menandakan server overload / high demand.
     */
    public static function isHighDemandError(string $message, int $statusCode = 0): bool
    {
        $lower = strtolower($message);

        return $statusCode === 503
            || $statusCode === 429
            || str_contains($lower, 'high demand')
            || str_contains($lower, 'overloaded')
            || str_contains($lower, 'temporarily unavailable')
            || str_contains($lower, 'resource exhausted')
            || str_contains($lower, 'spikes in demand');
    }

    /**
     * Kirim permintaan ke Gemini API dengan dukungan auto-retry dan auto-fallback jika model utama mengalami High Demand.
     *
     * @param  array<int, array<string, mixed>>  $contents
     * @param  array<string, mixed>  $extraConfig
     * @return array{
     *   response: Response|null,
     *   model_used: string,
     *   is_fallback: bool,
     *   error: ?string
     * }
     */
    public static function postGenerateContentWithFallback(
        string $apiKey,
        string $primaryModel,
        array $contents,
        array $extraConfig = [],
        int $timeout = 60
    ): array {
        $modelsToTry = array_unique(array_merge([$primaryModel], self::getFallbackModels($primaryModel)));
        $lastError = null;

        foreach ($modelsToTry as $idx => $model) {
            $isFallback = ($model !== $primaryModel);
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $config = self::buildGenerationConfig($model, $extraConfig);

            try {
                $response = Http::timeout($timeout)->connectTimeout(15)->post($endpoint, [
                    'contents' => $contents,
                    'generationConfig' => $config,
                ]);

                if ($response->successful()) {
                    return [
                        'response' => $response,
                        'model_used' => $model,
                        'is_fallback' => $isFallback,
                        'error' => null,
                    ];
                }

                $errJson = $response->json();
                $rawMsg = $errJson['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());
                $lastError = $rawMsg;

                // Jika error adalah high demand / overload / 503 / 429, coba model cadangan berikutnya
                if (self::isHighDemandError($rawMsg, $response->status()) && isset($modelsToTry[$idx + 1])) {
                    Log::warning("Gemini model '{$model}' mengalami high demand di Google ({$rawMsg}). Beralih otomatis ke model cadangan '{$modelsToTry[$idx + 1]}'.");

                    continue;
                }

                // Jika error jenis lain (misal API key invalid 400/403), tidak perlu dicoba ke model lain
                break;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("Gemini model '{$model}' mengalami kendala jaringan: ".$e->getMessage());
                if (isset($modelsToTry[$idx + 1])) {
                    continue;
                }
            }
        }

        return [
            'response' => null,
            'model_used' => $primaryModel,
            'is_fallback' => false,
            'error' => $lastError ?? 'Gagal menghubungi server Google Gemini.',
        ];
    }

    /**
     * Uji koneksi ke Google Gemini API menggunakan API Key yang diberikan.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   model: string,
     *   is_high_demand?: bool,
     *   suggested_model?: string,
     *   raw_error?: string
     * }
     */
    public static function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $setting = AiSetting::getFirst();
        $key = $apiKey ?: $setting->api_key;
        $chosenModel = $model ?: ($setting->model ?: 'gemini-3.8-flash');

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum diisi. Silakan masukkan API Key dari Google AI Studio.',
                'model' => $chosenModel,
            ];
        }

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$key}";

            $generationConfig = self::buildGenerationConfig($chosenModel, [
                'maxOutputTokens' => 1000,
            ]);

            $response = Http::timeout(25)->connectTimeout(10)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Halo! Ini pengujian koneksi dari Sistem Sultan Banten (Kanwil Banten). Balas dengan kalimat pendek: "Koneksi Google Gemini API berhasil terhubung."'],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $candidate = $body['candidates'][0] ?? null;
                $reply = self::extractTextFromCandidate($candidate) ?? 'Koneksi berhasil.';

                return [
                    'success' => true,
                    'message' => trim($reply),
                    'model' => $chosenModel,
                ];
            }

            $errBody = $response->json();
            $errMsg = $errBody['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());
            $isHighDemand = self::isHighDemandError($errMsg, $response->status());

            if ($isHighDemand) {
                $fallbacks = self::getFallbackModels($chosenModel);
                $suggestedFallback = $fallbacks[0] ?? 'gemini-2.5-flash';

                return [
                    'success' => false,
                    'is_high_demand' => true,
                    'suggested_model' => $suggestedFallback,
                    'message' => "Model '{$chosenModel}' saat ini sedang mengalami lonjakan antrean trafik tinggi di server Google (High Demand). Lonjakan ini biasanya bersifat sementara di sisi Google.",
                    'raw_error' => $errMsg,
                    'model' => $chosenModel,
                ];
            }

            return [
                'success' => false,
                'is_high_demand' => false,
                'message' => 'Gagal terhubung ke Gemini API: '.$errMsg,
                'model' => $chosenModel,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini API testConnection failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan jaringan atau timeout: '.$e->getMessage(),
                'model' => $chosenModel,
            ];
        }
    }

    /**
     * Mengambil daftar model yang tersedia untuk API Key yang diberikan.
     *
     * @return array{success: bool, message: string, models: array<int, array{id: string, name: string, description: string}>}
     */
    public static function getAvailableModels(?string $apiKey = null): array
    {
        $setting = AiSetting::getFirst();
        $key = $apiKey ?: $setting->api_key;

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key belum diisi. Masukkan API Key terlebih dahulu untuk memuat daftar model.',
                'models' => [],
            ];
        }

        try {
            $response = Http::timeout(15)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");

            if (! $response->successful()) {
                $err = $response->json();
                $msg = $err['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil daftar model: '.$msg,
                    'models' => [],
                ];
            }

            $json = $response->json();
            $rawModels = $json['models'] ?? [];
            $models = [];

            foreach ($rawModels as $m) {
                $methods = $m['supportedGenerationMethods'] ?? [];
                if (in_array('generateContent', $methods, true)) {
                    $cleanId = str_replace('models/', '', $m['name'] ?? '');
                    $models[] = [
                        'id' => $cleanId,
                        'name' => $m['displayName'] ?? $cleanId,
                        'description' => $m['description'] ?? '',
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Berhasil memuat '.count($models).' model aktif dari Google AI Studio.',
                'models' => $models,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini getAvailableModels error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                'models' => [],
            ];
        }
    }

    /**
     * Ekstraksi rincian surat undangan sidang TPP dari berkas PDF via Gemini AI.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: array{
     *     nomor_undangan: string,
     *     tanggal_surat: string,
     *     hal: string,
     *     asal_upt: string,
     *     hari_tanggal_sidang: string,
     *     jam_mulai: string,
     *     tempat: string
     *   }
     * }
     */
    public static function extractUndanganPdf(string $pdfPath): array
    {
        $setting = AiSetting::getFirst();

        if (! $setting->is_active || empty($setting->api_key)) {
            return [
                'success' => false,
                'message' => 'Integrasi AI belum diaktifkan atau API Key belum diisi di Pengaturan Master Data. Silakan isi form secara manual.',
            ];
        }

        $fullPath = public_path($pdfPath);
        if (! file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'Berkas PDF surat undangan tidak ditemukan di server.',
            ];
        }

        if (filesize($fullPath) > 20971520) { // 20 MB
            return [
                'success' => false,
                'message' => 'Ukuran berkas PDF melebihi batas 20 MB untuk pemrosesan AI.',
            ];
        }

        try {
            $base64Data = base64_encode(file_get_contents($fullPath));
            $chosenModel = ! empty($setting->model) ? $setting->model : 'gemini-3.8-flash';
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$setting->api_key}";

            $prompt = <<<'PROMPT'
Anda adalah asisten cerdas tata naskah dinas Kementerian Imigrasi dan Pemasyarakatan RI (Ditjenpas / Kantor Wilayah).
Tugas Anda adalah membaca dan menganalisis berkas PDF Surat Undangan Sidang TPP yang dilampirkan.
Ekstrak data penting berikut dalam format JSON:
- "nomor_undangan": nomor surat undangan resmi UPT (misal: WP.12.PAS7/PK.06.05/17/2026 atau sejenisnya)
- "tanggal_surat": tanggal surat dibuat (format teks Indonesia, misal: "05 November 2026" atau format tanggal yang tertulis di surat)
- "hal": perihal surat (misal: "Undangan Sidang TPP")
- "asal_upt": nama instansi/UPT pengirim surat (misal: "Lembaga Pembinaan Khusus Anak Kelas I Tangerang" atau "Lapas Pemuda Kelas IIA Tangerang")
- "hari_tanggal_sidang": hari dan tanggal pelaksanaan kegiatan sidang TPP (misal: "Jumat, 06 November 2026")
- "jam_mulai": jam atau waktu pelaksanaan (misal: "10.00 WIB s.d selesai" atau "09.00 s.d selesai")
- "tempat": tempat, ruangan, atau tautan daring sidang (misal: "Ruang Kampus Lapas Pemuda Kelas IIA Tangerang" atau "Zoom Cloud Meeting / Virtual")

Hanya kembalikan objek JSON valid tanpa penjelasan tambahan.
PROMPT;

            $contents = [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => 'application/pdf',
                                'data' => $base64Data,
                            ],
                        ],
                    ],
                ],
            ];

            @set_time_limit(180);
            $res = self::postGenerateContentWithFallback(
                apiKey: $setting->api_key,
                primaryModel: $chosenModel,
                contents: $contents,
                extraConfig: ['responseMimeType' => 'application/json'],
                timeout: 75
            );

            if (! $res['response'] || ! $res['response']->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal memproses PDF dengan AI: '.($res['error'] ?? 'Tidak ada balasan dari server Google.'),
                ];
            }

            $resJson = $res['response']->json();
            $candidate = $resJson['candidates'][0] ?? null;
            $rawText = self::extractTextFromCandidate($candidate) ?? '';
            $cleanedJson = self::cleanJsonPayload($rawText);

            $data = json_decode($cleanedJson, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            $msg = 'Surat undangan berhasil dianalisis oleh AI.';
            if ($res['is_fallback']) {
                $msg .= " (dialihkan otomatis ke model cadangan {$res['model_used']} karena {$chosenModel} sedang antre di server Google).";
            }

            return [
                'success' => true,
                'message' => $msg,
                'data' => [
                    'nomor_undangan' => $data['nomor_undangan'] ?? '',
                    'tanggal_surat' => $data['tanggal_surat'] ?? '',
                    'hal' => $data['hal'] ?? 'Undangan Sidang TPP',
                    'asal_upt' => $data['asal_upt'] ?? '',
                    'hari_tanggal_sidang' => $data['hari_tanggal_sidang'] ?? '',
                    'jam_mulai' => $data['jam_mulai'] ?? '',
                    'tempat' => $data['tempat'] ?? '',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini extractUndanganPdf error: '.$e->getMessage());

            $errMsg = $e->getMessage();
            if (str_contains($errMsg, 'timed out') || str_contains($errMsg, 'cURL error 28')) {
                $errMsg = 'Waktu analisis AI habis (timeout). Silakan periksa ukuran PDF atau lanjutkan dengan pengisian form secara manual.';
            }

            return [
                'success' => false,
                'message' => 'Kendala Analisis AI: '.$errMsg,
            ];
        }
    }

    /**
     * Analisis cerdas berkas PDF Berita Acara (BA) Sidang TPP dari UPT via Gemini AI
     * untuk menyusun draf Laporan Pelaksanaan Sidang TPP Kantor Wilayah yang adaptif sesuai materi sidang.
     *
     * @param  array<string, mixed>  $metaData
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: array<string, mixed>
     * }
     */
    public static function analyzeBeritaAcaraPdf(string $pdfPath, array $metaData = []): array
    {
        $setting = AiSetting::getFirst();

        if (! $setting->is_active || empty($setting->api_key)) {
            return [
                'success' => false,
                'message' => 'Integrasi AI belum diaktifkan atau API Key belum diisi di Pengaturan Master Data AI.',
            ];
        }

        $fullPath = public_path($pdfPath);
        if (! file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'Berkas PDF Berita Acara sidang TPP tidak ditemukan di server.',
            ];
        }

        if (filesize($fullPath) > 20971520) { // 20 MB
            return [
                'success' => false,
                'message' => 'Ukuran berkas PDF melebihi batas 20 MB untuk pemrosesan AI.',
            ];
        }

        try {
            $base64Data = base64_encode(file_get_contents($fullPath));
            $chosenModel = ! empty($setting->model) ? $setting->model : 'gemini-3.8-flash';
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$setting->api_key}";

            $uptNama = $metaData['upt_nama'] ?? 'UPT Pemasyarakatan';
            $hariTgl = $metaData['hari_tanggal_sidang'] ?? '';
            $metode = $metaData['metode_sidang'] ?? 'virtual Zoom Meeting';
            $pengawas = $metaData['nama_pengawas'] ?? 'Perwakilan Kantor Wilayah';
            $instruksiKhusus = trim($metaData['instruksi_khusus'] ?? '');
            $gayaPenulisan = $metaData['gaya_penulisan'] ?? 'standar';

            $instruksiKhususPrompt = '';
            if (! empty($instruksiKhusus)) {
                $instruksiKhususPrompt = <<<INTRUKSI

CATATAN KHUSUS / DINAMIKA DARI OPERATOR KANWIL:
"{$instruksiKhusus}"
(PENTING: Integrasikan catatan ini secara luwes, alami, dan relevan ke dalam narasi pembahasan, butir temuan, atau rekomendasi saran).
INTRUKSI;
            }

            $gayaPenulisanPrompt = match ($gayaPenulisan) {
                'pengawasan' => 'GAYA PENULISAN: Tegas, evaluatif, menitikberatkan pada aspek audit kepatuhan regulasi, akurasi data SDP, dan mitigasi risiko keamanan.',
                'ringkas' => 'GAYA PENULISAN: Ringkas, padat, lugas (eksekutif), langsung menyajikan fakta esensial tanpa uraian berlebihan.',
                default => 'GAYA PENULISAN: Standar Kedinasan Kementerian, formal, objektif, seimbang, dan mengalir santun.',
            };

            $prompt = <<<PROMPT
Anda adalah Perancang Peraturan dan Analis Tata Naskah Dinas Senior pada Kantor Wilayah Kementerian Hukum dan HAM / Kementerian Imigrasi dan Pemasyarakatan RI.
Tugas Anda adalah membaca, menganalisis, dan menyarikan berkas PDF Berita Acara (BA) Sidang Tim Pengamat Pemasyarakatan (TPP) yang dilampirkan dari satuan kerja: "{$uptNama}".

KONTEKS DASAR KEGIATAN:
- Satuan Kerja / UPT: {$uptNama}
- Hari & Tanggal Sidang: {$hariTgl}
- Metode Pelaksanaan: {$metode}
- Pengawas / Perwakilan Kanwil: {$pengawas}
- {$gayaPenulisanPrompt}
{$instruksiKhususPrompt}

PRINSIP PENYUSUNAN NASKAH & TATA BAHASA (EYD V):
1. KEPATUHAN TATA NASKAH DINAS:
   - Susun naskah laporan kedinasan yang utuh, berwibawa, dan terstruktur sesuai Pedoman Tata Naskah Dinas resmi instansi pemerintah (Permenkumham No. 31 Tahun 2020 / Permenimipas).
   - Laporan harus mencerminkan fungsi pembinaan, monitoring, pengawasan, dan pengendalian (Bintorwasdal) teknis pemasyarakatan.
2. BAHASA BAKU, LUWES, DAN TIDAK KAKU:
   - JANGAN mengulang-ulang kalimat template klise yang kaku ("Pada hari..., Kantor Wilayah mengikuti..."). Susun kalimat dengan variasi aktif dan pasif kedinasan yang mengalir alami dan enak dibaca.
   - Hindari pleonasme ("adalah merupakan", "demi untuk").
   - Gunakan terminologi hukum dan pemasyarakatan yang presisi (misal: "persyaratan substantif dan administratif", "penilaian risiko faktor kriminogenik", "penelitian kemasyarakatan (Litmas) Bapas", "register F", "penghitungan 2/3 masa pidana", "asimilasi kerja sosial/kerja luar", "Sistem Database Pemasyarakatan (SDP)").
3. ADAPTIF TERHADAP FAKTA SUBSTANTIF BERITA ACARA:
   - Ekstrak seluruh data angka dan materi riil yang tertulis di dalam BA: apakah mengenai Usulan Hak Integrasi (PB, CB, CMB), Asimilasi, Remisi, Mutasi Narapidana, Pengangkatan Pemuka/Tamping, atau Penjatuhan Hukuman Disiplin.
   - Catat rincian jumlah narapidana per kategori usulan.
   - JIKA ADA CATATAN DINAMIKA (misal: ada usulan yang ditunda, perbaikan litmas, penjamin belum diverifikasi, atau catatan keamanan khusus), JELASKAN secara objektif dan konstruktif pada butir temuan dan rekomendasi. Jangan memaksakan klaim "semua memenuhi syarat" jika BA menyatakan ada kendala/penundaan.

Kembalikan respon DALAM FORMAT JSON VALID dengan struktur berikut:
{
  "topik_sidang": "ringkasan 1 baris topik sidang, misal: 'Pemberian Hak Integrasi (PB dan CB) dan Asimilasi di Rumah' atau 'Penjatuhan Hukuman Disiplin Pelanggaran Tata Tertib'",
  "materi_sidang_items": [
    "rincian butir 1 jenis usulan beserta jumlah orang, misal: 'Pembebasan Bersyarat (PB): 33 orang narapidana'",
    "rincian butir 2..."
  ],
  "paragraf_a1": "Narasi pembuka yang mengalir alami mengenai keikutsertaan perwakilan Kantor Wilayah dalam pelaksanaan Sidang TPP di {$uptNama} pada hari dan tanggal tersebut sebagai wujud akuntabilitas pelaksanaan fungsi pengawasan teknis.",
  "paragraf_a2": "Narasi komprehensif mengenai latar belakang pembahasan sidang, program pembinaan narapidana yang dibahas, urgensi pengusulan hak/program tersebut, serta kaitannya dengan tujuan Sistem Pemasyarakatan.",
  "paragraf_a3": "Narasi mengenai peran strategis perwakilan Kantor Wilayah sebagai unsur pembina dan pengawas dalam memastikan seluruh tahapan pengambilan keputusan berjalan transparan, akuntabel, dan bebas dari diskriminasi.",
  "paragraf_a4": "Narasi teknis mengenai proses pencermatan terhadap pemenuhan syarat administratif dan substantif, sinkronisasi data pada SDP, hasil penilaian instrumen SPPN, laporan Litmas Bapas, serta mitigasi risiko keamanan.",
  "paragraf_b1": "Narasi pengantar hasil telaahan terhadap jalannya sidang dan Berita Acara yang dihasilkan oleh Tim TPP {$uptNama}.",
  "paragraf_b2_poin": [
    "Butir temuan 1 mengenai pemenuhan syarat substantif (perilaku, kepatuhan program pembinaan, catatan disiplin narapidana).",
    "Butir temuan 2 mengenai keabsahan berkas administratif dan keterbaruan data pada Sistem Database Pemasyarakatan (SDP).",
    "Butir temuan 3 mengenai hasil rekomendasi sidang secara riil (termasuk penjelasan narapidana yang disetujui, atau catatan khusus bagi yang ditunda/bersyarat bila tercantum dalam BA)."
  ],
  "simpulan_poin": [
    "Simpulan objektif 1 mengenai kualitas penyelenggaraan sidang TPP di {$uptNama}.",
    "Simpulan objektif 2 mengenai ketepatan pertimbangan pembinaan dan keamanan terhadap materi usulan yang diajukan."
  ],
  "saran_poin": [
    "Rekomendasi tindak lanjut 1 untuk jajaran {$uptNama} terkait penyempurnaan kelengkapan berkas dan pengunggahan dokumen ke SDP.",
    "Rekomendasi tindak lanjut 2 terkait monitoring evaluasi pasca-sidang serta koordinasi berkala dengan Kantor Wilayah."
  ],
  "penutup": "Paragraf penutup naskah dinas resmi yang santun, lugas, dan akuntabel sebagai bentuk pertanggungjawaban pelaksanaan tugas kepada pimpinan."
}

PENTING:
- Buat narasinya hidup, berbobot, dan mengalir alami sesuai EYD V.
- Sesuaikan isi seluruh bagian dengan isi fakta dalam PDF Berita Acara yang dilampirkan.
- Hanya kembalikan teks JSON valid tanpa awalan atau akhiran markdown penjelasan.
PROMPT;

            $contents = [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => 'application/pdf',
                                'data' => $base64Data,
                            ],
                        ],
                    ],
                ],
            ];

            @set_time_limit(240);
            $res = self::postGenerateContentWithFallback(
                apiKey: $setting->api_key,
                primaryModel: $chosenModel,
                contents: $contents,
                extraConfig: ['responseMimeType' => 'application/json'],
                timeout: 90
            );

            if (! $res['response'] || ! $res['response']->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal memproses Berita Acara dengan AI: '.($res['error'] ?? 'Tidak ada balasan dari server Google.'),
                ];
            }

            $resJson = $res['response']->json();
            $candidate = $resJson['candidates'][0] ?? null;
            $rawText = self::extractTextFromCandidate($candidate) ?? '';
            $cleanedJson = self::cleanJsonPayload($rawText);

            $data = json_decode($cleanedJson, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            $msg = 'Berita Acara berhasil dianalisis oleh AI Gemini.';
            if ($res['is_fallback']) {
                $msg .= " (dialihkan otomatis ke model cadangan {$res['model_used']} karena {$chosenModel} sedang antre di server Google).";
            }

            return [
                'success' => true,
                'message' => $msg,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini analyzeBeritaAcaraPdf error: '.$e->getMessage());

            $errMsg = $e->getMessage();
            if (str_contains($errMsg, 'timed out') || str_contains($errMsg, 'cURL error 28')) {
                $errMsg = 'Waktu analisis AI habis (timeout). Berkas PDF Berita Acara mungkin terlalu tebal/banyak halaman, atau server Google AI sedang lambat. Silakan gunakan template draf yang sudah tersedia dan lengkapi secara manual, atau coba beberapa saat lagi.';
            }

            return [
                'success' => false,
                'message' => 'Kendala Analisis AI: '.$errMsg,
            ];
        }
    }

    /**
     * Susun draf narasi Nota Dinas Permohonan Surat Tugas cerdas via Gemini AI
     * berlandaskan Tata Naskah Dinas Kementerian dan Bahasa Indonesia Baku (EYD V).
     *
     * @param  array<string, mixed>  $params
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: array{
     *     hal_surat: string,
     *     narasi_pengantar: string,
     *     catatan_urgensi: string
     *   }
     * }
     */
    public static function draftNotaDinasNarasi(array $params): array
    {
        $setting = AiSetting::getFirst();

        if (! $setting->is_active || empty($setting->api_key)) {
            return [
                'success' => false,
                'message' => 'Integrasi AI belum diaktifkan atau API Key belum diisi di Pengaturan Master Data AI.',
            ];
        }

        $uptNama = $params['upt_nama'] ?? 'Lembaga Pemasyarakatan';
        $nomorUndangan = $params['nomor_undangan'] ?? 'WP.12.PAS...';
        $tanggalSuratUpt = $params['tanggal_surat_upt'] ?? '-';
        $halUndangan = $params['hal_undangan'] ?? 'Undangan Sidang TPP';
        $hariTanggal = $params['hari_tanggal'] ?? '-';
        $pukul = $params['pukul'] ?? '09.00 WIB s.d selesai';
        $tempat = $params['tempat'] ?? 'Ruang Sidang TPP';
        $instruksiKhusus = trim($params['instruksi_khusus'] ?? '');

        $instruksiPrompt = ! empty($instruksiKhusus)
            ? "\nCATATAN / INSTRUKSI TAMBAHAN DARI PEMOHON:\n\"{$instruksiKhusus}\"\n"
            : '';

        $prompt = <<<PROMPT
Anda adalah Perancang Peraturan dan Analis Tata Naskah Dinas Senior Kantor Wilayah Kementerian Hukum dan HAM / Kementerian Imigrasi dan Pemasyarakatan RI.
Tugas Anda adalah merumuskan redaksi resmi NOTA DINAS Permohonan Surat Tugas kepada Pimpinan / Kepala Bagian Tata Usaha dan Umum.

STRUKTUR ANATOMI TEMPLATE DOKUMEN NOTA DINAS KANWIL:
1. Bagian Metadata: Yth, Dari, Hal, Lampiran, Tanggal.
2. Narasi Pengantar / Rujukan Surat Tugas (narasi_pengantar).
3. [TABEL DAFTAR PEJABAT/PEGAWAI KANWIL YANG DIMOHONKAN SURAT TUGAS - No, Nama, NIP, Jabatan].
4. Kalimat Pengantar Jadwal Pelaksanaan (narasi_pelaksanaan).
5. [TABEL RINCIAN JADWAL & TEMPAT: Hari/tanggal, Pukul, Tempat].
6. Paragraf Penutup (penutup).
7. Kolom Tanda Tangan Pejabat.

DATA RUJUKAN KEGIATAN:
- Asal Surat / UPT: {$uptNama}
- Nomor Surat Undangan UPT: {$nomorUndangan}
- Tanggal Surat UPT: {$tanggalSuratUpt}
- Hal Surat Undangan: {$halUndangan}
- Rencana Waktu Pelaksanaan: {$hariTanggal}, Pukul {$pukul}
- Rincian Tempat Pelaksanaan: {$tempat}
{$instruksiPrompt}

PANDUAN PENYUSUNAN TATA NASKAH DINAS & EYD V:
1. 'hal_surat':
   - Rumuskan perihal surat yang lugas, resmi, dan mencerminkan tugas Bintorwasdal Sidang TPP di {$uptNama}.
   - Contoh: "Permohonan Surat Tugas Pembinaan, Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di {$uptNama}"
2. 'narasi_pengantar':
   - Paragraf ini DITEMPATKAN PERSIS DI ATAS TABEL DAFTAR PEGAWAI.
   - Paragraf HARUS mengalir utuh dan memuat 3 unsur:
     a) Rujukan surat undangan dari UPT (nomor, tanggal, perihal).
     b) Maksud & urgensi kegiatan pembinaan, pengawasan, pengendalian, dan monitoring teknis pelaksanaan Sidang TPP, DILENGKAPI DENGAN TEMPAT PELAKSANAAN ({$tempat}).
     c) Kalimat pengantar permohonan penerbitan surat tugas bagi pejabat/pegawai yang ditugaskan.
   - PENTING / KRUSIAL: Kalimat penutup paragraf pengantar HARUS menjadi kalimat penghubung langsung ke tabel susunan pegawai di bawahnya!
     Gunakan kalimat penutup seperti: "...bersama ini kami mohon perkenan Bapak/Ibu untuk dapat menerbitkan Surat Tugas bagi pejabat/pegawai Kantor Wilayah sebagai berikut:"
   - DILARANG mengakhiri paragraf pengantar dengan "yang akan diselenggarakan pada:" karena elemen tepat di bawah paragraf ini adalah TABEL DAFTAR PEGAWAI (bukan jadwal)!
3. 'narasi_pelaksanaan':
   - Kalimat penghubung antara Tabel Daftar Pegawai dan Tabel Rincian Jadwal di bawahnya.
   - Contoh: "Adapun pelaksanaan kegiatan pembinaan dan pengawasan sidang TPP dimaksud dilaksanakan dengan rincian jadwal dan tempat sebagai berikut :"
4. 'penutup':
   - Paragraf penutup resmi nota dinas sesuai tata naskah.
   - Contoh: "Demikian kami sampaikan, mohon perkenan dan petunjuk lebih lanjut. Atas perhatian dan kerja samanya diucapkan terima kasih."
5. 'catatan_urgensi':
   - Ringkasan singkat poin urgensi pembinaan dan verifikasi usulan hak integrasi warga binaan.

Kembalikan hasil dalam format JSON VALID:
{
  "hal_surat": "Permohonan Surat Tugas Pembinaan, Monitoring, Pengawasan dan Pengendalian Pelaksanaan Sidang TPP di {$uptNama}",
  "narasi_pengantar": "Menindaklanjuti Surat Kepala {$uptNama} Nomor {$nomorUndangan} tanggal {$tanggalSuratUpt} perihal {$halUndangan}, dalam rangka pelaksanaan pembinaan, monitoring, pengawasan, dan pengendalian pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) yang diselenggarakan di {$tempat}, bersama ini kami mohon perkenan Bapak/Ibu untuk dapat menerbitkan Surat Tugas bagi pejabat/pegawai Kantor Wilayah sebagai berikut:",
  "narasi_pelaksanaan": "Adapun pelaksanaan kegiatan pembinaan dan pengawasan sidang TPP dimaksud dilaksanakan dengan rincian jadwal dan tempat sebagai berikut :",
  "penutup": "Demikian kami sampaikan, mohon perkenan dan petunjuk lebih lanjut. Atas perhatian dan kerja samanya diucapkan terima kasih.",
  "catatan_urgensi": "Kegiatan pembinaan dan pengawasan ini penting untuk memastikan pelaksanaan sidang TPP dan verifikasi usulan hak integrasi warga binaan berjalan akuntabel sesuai peraturan perundang-undangan."
}

Hanya kembalikan objek JSON valid tanpa penjelasan tambahan.
PROMPT;

        try {
            $chosenModel = ! empty($setting->model) ? $setting->model : 'gemini-3.8-flash';
            $contents = [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ];

            $res = self::postGenerateContentWithFallback(
                apiKey: $setting->api_key,
                primaryModel: $chosenModel,
                contents: $contents,
                extraConfig: ['responseMimeType' => 'application/json'],
                timeout: 45
            );

            if (! $res['response'] || ! $res['response']->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal menyusun narasi dengan AI: '.($res['error'] ?? 'Tidak ada respon server.'),
                ];
            }

            $resJson = $res['response']->json();
            $candidate = $resJson['candidates'][0] ?? null;
            $rawText = self::extractTextFromCandidate($candidate) ?? '';
            $cleanedJson = self::cleanJsonPayload($rawText);

            $data = json_decode($cleanedJson, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Narasi Nota Dinas berhasil disusun oleh AI sesuai tata naskah dinas.',
                'data' => [
                    'hal_surat' => $data['hal_surat'] ?? '',
                    'narasi_pengantar' => $data['narasi_pengantar'] ?? '',
                    'narasi_pelaksanaan' => $data['narasi_pelaksanaan'] ?? 'Adapun pelaksanaan kegiatan pembinaan dan pengawasan sidang TPP dimaksud dilaksanakan dengan rincian jadwal dan tempat sebagai berikut :',
                    'penutup' => $data['penutup'] ?? 'Demikian kami sampaikan, mohon perkenan dan petunjuk lebih lanjut. Atas perhatian dan kerja samanya diucapkan terima kasih.',
                    'catatan_urgensi' => $data['catatan_urgensi'] ?? '',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini draftNotaDinasNarasi error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Kendala AI: '.$e->getMessage(),
            ];
        }
    }
}
