<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Instansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InstansiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (! $user->hasAccess('instansi')) {
            abort(403);
        }

        $instansi = Instansi::getFirst();

        return view('instansi.index', compact('instansi'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('instansi')) {
            abort(403);
        }

        $request->validate([
            'nama_instansi' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:255',
            'running_text' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'favicon' => 'nullable|mimes:ico,png,jpg,jpeg,svg|max:1024',
            'welcome_banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'kop_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'logo_kop' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ttd_image' => 'nullable|image|mimes:png,webp|max:2048',
            'stempel_image' => 'nullable|image|mimes:png,webp|max:2048',
        ]);

        $instansi = Instansi::first();
        if (! $instansi) {
            $instansi = new Instansi;
        }

        $instansi->nama_instansi = $request->input('nama_instansi');
        $instansi->alamat = $request->input('alamat');
        $instansi->telepon = $request->input('telepon');
        $instansi->email = $request->input('email');
        $instansi->website = $request->input('website');
        $instansi->running_text = $request->input('running_text');

        // Batas Jam Input
        $instansi->batas_jam_aktif = $request->has('batas_jam_aktif');
        $instansi->jam_mulai = $request->input('jam_mulai', '06:00:00');
        $instansi->jam_selesai = $request->input('jam_selesai', '08:00:00');

        // File uploads
        $imageFields = ['logo', 'favicon', 'welcome_banner', 'kop_image', 'logo_kop', 'ttd_image', 'stempel_image'];
        $uploadPath = public_path('uploads');
        if (! File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        foreach ($imageFields as $field) {
            // Delete request
            if ($request->has("delete_{$field}")) {
                if ($instansi->{$field} && File::exists(public_path($instansi->{$field}))) {
                    File::delete(public_path($instansi->{$field}));
                    $prevWebp = preg_replace('/\.[^.]+$/', '.webp', public_path($instansi->{$field}));
                    if (File::exists($prevWebp)) {
                        File::delete($prevWebp);
                    }
                }
                $instansi->{$field} = null;
            }

            // Upload request
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
                // Cegah ekstensi berbahaya
                if (in_array($ext, ['php', 'phtml', 'phar', 'php5', 'php7', 'blade', 'exe', 'sh', 'bat'])) {
                    return back()->with('error', 'Format file tidak diizinkan untuk alasan keamanan.');
                }
                $filename = "{$field}_".time().'_'.bin2hex(random_bytes(8)).'.'.$ext;
                $file->move($uploadPath, $filename);

                // Remove previous file
                if ($instansi->{$field} && File::exists(public_path($instansi->{$field}))) {
                    File::delete(public_path($instansi->{$field}));
                    $prevWebp = preg_replace('/\.[^.]+$/', '.webp', public_path($instansi->{$field}));
                    if (File::exists($prevWebp)) {
                        File::delete($prevWebp);
                    }
                    $prevThumb = preg_replace('/\.[^.]+$/', '_thumb.webp', public_path($instansi->{$field}));
                    if (File::exists($prevThumb)) {
                        File::delete($prevThumb);
                    }
                }
                $instansi->{$field} = "uploads/{$filename}";

                // Auto-generate WebP version for high performance
                if (function_exists('imagewebp') && in_array($ext, ['png', 'jpg', 'jpeg'])) {
                    $srcFilePath = $uploadPath.DIRECTORY_SEPARATOR.$filename;
                    $webpFilename = preg_replace('/\.'.preg_quote($ext, '/').'$/i', '.webp', $filename);
                    $webpFilePath = $uploadPath.DIRECTORY_SEPARATOR.$webpFilename;
                    try {
                        $sourceImg = ($ext === 'png') ? @imagecreatefrompng($srcFilePath) : @imagecreatefromjpeg($srcFilePath);
                        if ($sourceImg) {
                            if ($ext === 'png') {
                                imagepalettetotruecolor($sourceImg);
                                imagealphablending($sourceImg, true);
                                imagesavealpha($sourceImg, true);
                            }
                            imagewebp($sourceImg, $webpFilePath, 82);

                            // For logos, also generate small 90x90 thumbnail
                            if (in_array($field, ['logo', 'logo_kop'])) {
                                $origW = imagesx($sourceImg);
                                $origH = imagesy($sourceImg);
                                $thumbW = 90;
                                $thumbH = (int) round(($origH / max(1, $origW)) * $thumbW);
                                $thumbImg = imagecreatetruecolor($thumbW, $thumbH);
                                imagealphablending($thumbImg, false);
                                imagesavealpha($thumbImg, true);
                                $transparent = imagecolorallocatealpha($thumbImg, 255, 255, 255, 127);
                                imagefilledrectangle($thumbImg, 0, 0, $thumbW, $thumbH, $transparent);
                                imagecopyresampled($thumbImg, $sourceImg, 0, 0, 0, 0, $thumbW, $thumbH, $origW, $origH);
                                $thumbFilename = preg_replace('/\.'.preg_quote($ext, '/').'$/i', '_thumb.webp', $filename);
                                imagewebp($thumbImg, $uploadPath.DIRECTORY_SEPARATOR.$thumbFilename, 85);
                                imagedestroy($thumbImg);
                            }

                            imagedestroy($sourceImg);
                        }
                    } catch (\Throwable $e) {
                        // ignore and keep original
                    }
                }
            }
        }

        $instansi->save();
        ActivityLog::log('edit', 'instansi', 'Memperbarui data dan pengaturan instansi', $instansi->toArray());

        return back()->with('success', 'Pengaturan instansi berhasil diperbarui.');
    }
}
