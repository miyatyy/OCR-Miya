<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function prosesOcr(Request $request)
    {
        // 1. Validasi Input: Menerima berkas file gambar ATAU string Base64 dari kamera live
        if (!$request->hasFile('file_gambar') && !$request->has('kamera_base64')) {
            return response()->json(['error' => 'Tidak ada file gambar atau jepretan kamera yang diterima.'], 400);
        }

        try {
            // Jalur aplikasi Tesseract Windows
            $tesseract = 'C:\Program Files\Tesseract-OCR\tesseract.exe';

            if (!file_exists($tesseract)) {
                return response()->json(['error' => 'Aplikasi Tesseract.exe tidak terdeteksi di C:\Program Files\Tesseract-OCR.'], 500);
            }

            // Gunakan folder TEMP bawaan Windows agar aman dari kendala hak akses folder
            $tempDir = sys_get_temp_dir();
            $uniqueId = uniqid();
            $tempImage = '';

            // 2. Pemrosesan Sumber Gambar
            if ($request->hasFile('file_gambar')) {
                // SUMBER A: Menggunakan unggahan file biasa (Galeri)
                $gambar = $request->file('file_gambar');
                $tempImage = $tempDir . DIRECTORY_SEPARATOR . 'ocr_in_' . $uniqueId . '.' . $gambar->getClientOriginalExtension();
                move_uploaded_file($gambar->getRealPath(), $tempImage);
            } else if ($request->has('kamera_base64')) {
                // SUMBER B: Menggunakan jepretan kamera langsung (Base64 Stream)
                $base64Data = $request->input('kamera_base64');
                
                // Menghilangkan format header data URL jika ada (contoh: data:image/png;base64,...)
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                    $ext = strtolower($type[1]);
                } else {
                    $ext = 'png';
                }

                $rawImage = base64_decode($base64Data);
                if ($rawImage === false) {
                    return response()->json(['error' => 'Gagal membaca format data gambar dari kamera.'], 400);
                }

                $tempImage = $tempDir . DIRECTORY_SEPARATOR . 'ocr_in_' . $uniqueId . '.' . $ext;
                file_put_contents($tempImage, $rawImage);
            }

            $outputBase = $tempDir . DIRECTORY_SEPARATOR . 'ocr_out_' . $uniqueId;
            $outputFile = $outputBase . '.txt';

            // 3. Eksekusi Perintah CMD Windows yang Aman & Solid
            // Menggunakan struktur bersarang untuk mengatasi spasi di "Program Files"
            $command = "\"\"$tesseract\" \"$tempImage\" \"$outputBase\" -l eng\"";
            shell_exec($command);

            // 4. Pengembalian Respon Teks
            if (file_exists($outputFile)) {
                $text = file_get_contents($outputFile);
                
                // Bersihkan file sementara
                @unlink($tempImage);
                @unlink($outputFile);

                return response()->json([
                    'success' => true,
                    'text' => trim($text)
                ]);
            }

            // Jika file tidak terbentuk
            @unlink($tempImage);
            return response()->json([
                'error' => 'Mesin lokal gagal mendeteksi tulisan. Pastikan gambar/kamera fokus dan teks terlihat jelas.'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    // Fungsi Logika Membuat Dokumen Word dan PDF
    public function downloadDokumen(Request $request)
    {
        $text = $request->input('text', '');
        $type = $request->input('type', 'word');

        $formattedText = nl2br(e($text));

        if ($type === 'word') {
            $headers = [
                "Content-type" => "application/vnd.ms-word",
                "Content-Disposition" => "attachment;Filename=Hasil_OCR.doc",
                "Pragma" => "no-cache",
                "Expires" => "0"
            ];

            $content = "
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head><title>Hasil Eksport OCR</title></head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <h2>Hasil Ekstraksi Dokumen OCR</h2>
                    <hr/>
                    <p>$formattedText</p>
                </body>
                </html>
            ";

            return response($content, 200, $headers);
        } else {
            $headers = [
                "Content-type" => "application/pdf",
                "Content-Disposition" => "inline; filename=Hasil_OCR.pdf",
                "Pragma" => "no-cache",
                "Expires" => "0"
            ];

            $content = "%PDF-1.4\n1 0 obj\n<< /Title (Hasil OCR) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 595 842] /Contents 5 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>\nendobj\n5 0 obj\n<< /Length 100 >>\nstream\nBT\n/F1 12 Tf\n50 800 Td\n(" . iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(["\r", "\n"], " ", $text)) . ") Tj\nET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\n0000000162 00000 n\n0000000325 00000 n\ntrailer\n<< /Size 6 /Root 2 0 R >>\nstartxref\n0000000475\n%%EOF";

            return response($content, 200, $headers);
        }
    }
}