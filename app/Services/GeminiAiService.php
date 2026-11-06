<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    /**
     * Uji koneksi ke Google Gemini API menggunakan API Key yang diberikan.
     *
     * @return array{success: bool, message: string, model: string}
     */
    public static function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $setting = AiSetting::getFirst();
        $key = $apiKey ?: $setting->api_key;
        $chosenModel = $model ?: ($setting->model ?: 'gemini-1.5-flash');

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum diisi. Silakan masukkan API Key dari Google AI Studio.',
                'model' => $chosenModel,
            ];
        }

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$key}";

            $response = Http::timeout(15)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Halo! Ini pengujian koneksi dari Sistem Sultan Banten (Kanwil Banten). Balas dengan kalimat pendek: "Koneksi Google Gemini API berhasil terhubung."'],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 60,
                ],
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $reply = $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Koneksi berhasil.';

                return [
                    'success' => true,
                    'message' => trim($reply),
                    'model' => $chosenModel,
                ];
            }

            $errBody = $response->json();
            $errMsg = $errBody['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());

            return [
                'success' => false,
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
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$setting->model}:generateContent?key={$setting->api_key}";

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

            $payload = [
                'contents' => [
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
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ];

            $response = Http::timeout(35)->post($endpoint, $payload);

            if (! $response->successful()) {
                $err = $response->json();
                $msg = $err['error']['message'] ?? ('HTTP '.$response->status());

                return [
                    'success' => false,
                    'message' => 'Gagal memproses PDF dengan AI: '.$msg,
                ];
            }

            $resJson = $response->json();
            $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $data = json_decode($rawText, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Surat undangan berhasil dianalisis oleh AI.',
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

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ];
        }
    }
}
