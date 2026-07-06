<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function prosesOcr(Request $request)
    {
        // 1. Validasi input Base64 dari Frontend
        if (!$request->has('image_base64')) {
            return response()->json(['success' => false, 'error' => 'Tidak ada gambar yang diterima.'], 400);
        }

        try {
            $rawBase64 = $request->input('image_base64');
            $mimeType = 'image/png'; // Default MIME type jika tidak terdeteksi
            $base64Data = '';

            // Metode Ekstraksi Kuat: Memecah string Base64 berdasarkan tanda koma agar terhindar dari Error 400 Bad Request
            if (str_contains($rawBase64, ',')) {
                $parts = explode(',', $rawBase64);
                
                // Ambil informasi MIME type asli dari bagian metadata pertama
                if (preg_match('/^data:(image\/\w+);base64$/', trim($parts[0]), $matches)) {
                    $mimeType = $matches[1];
                }
                
                // Ambil data biner base64 murni di bagian kedua
                $base64Data = $parts[1];
            } else {
                // Jika data dari frontend dikirim tanpa menyertakan header biner data URL
                $base64Data = $rawBase64;
            }

            // Bersihkan spasi atau baris baru ilegal yang berpotensi merusak enkripsi data gambar
            $base64Data = trim(str_replace(["\r", "\n", " "], "", $base64Data));

            // Dapatkan API Key Gemini dari file .env
            $apiKey = env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY belum di-setting di file .env'], 500);
            }

            // 2. Hubungkan ke Endpoint Resmi Google Gemini API (Menggunakan v1 Stabil dan model gemini-2.5-flash)
            $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

            // Prompt Cerdas untuk mendukung multi-fungsi aplikasi Miya OCR
            $promptText = "Kamu adalah sistem AI handal untuk aplikasi multi-fungsi bernama Miya OCR. "
                        . "Tugasmu:\n"
                        . "1. Jika gambar berisi tulisan (cetak maupun tulisan tangan), ekstraklah kata demi kata secara sangat akurat sesuai teks aslinya.\n"
                        . "2. Jika gambar berupa kode QRIS atau Barcode, bacalah data string manifes di dalamnya secara mentah.\n"
                        . "3. Jika gambar berupa objek umum atau pemandangan, deskripsikan objek itu secara singkat.\n"
                        . "Keluarkan hasil data/teks langsung secara bersih tanpa kalimat pembuka atau basa-basi.";

            // Menyusun struktur payload sesuai dokumentasi JSON Google Gemini API
            $payload = json_encode([
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $promptText],
                            [
                                "inlineData" => [
                                    "mimeType" => $mimeType,
                                    "data" => $base64Data
                                ]
                            ]
                        ]
                    ]
                ],
                "generationConfig" => [
                    "temperature" => 0.2
                ]
            ]);

            // 3. Eksekusi Request Menggunakan cURL bawaan PHP
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }
            curl_close($ch);

            if ($httpCode !== 200) {
                // Membantu proses debug jika terdapat kegagalan validasi dari server Google
                $errorDetail = json_decode($response, true);
                $errorMessage = $errorDetail['error']['message'] ?? 'Kesalahan tidak dikenal pada repositori API.';
                return response()->json(['success' => false, 'error' => "Google Gemini API Error ({$httpCode}): {$errorMessage}"], 500);
            }

            $result = json_decode($response, true);
            
            // 4. Ambil Konten Hasil Analisis Ekstraksi dari Response Gemini
            $extractedResult = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty(trim($extractedResult))) {
                return response()->json([
                    'success' => false, 
                    'error' => 'Gemini AI gagal memproses atau mengekstrak gambar ini.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'text' => trim($extractedResult)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadDokumen(Request $request)
    {
        $text = $request->input('text', '');
        $type = $request->input('type', 'txt');
        $base64Image = $request->input('image_base64', '');
        $formattedText = nl2br(e($text));
        $filename = "Hasil_MiyaOCR_" . time();

        // Download: Gambar Convert Ke Word (.doc) via Laravel Fallback
        if ($type === 'img_word') {
            $headers = ["Content-type" => "application/vnd.ms-word", "Content-Disposition" => "attachment;Filename=" . $filename . "_Gambar.doc"];
            $content = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><body style='text-align:center; padding:20px;'><h2>Berkas Hasil Convert Gambar</h2><hr/><br/><img src='{$base64Image}' style='max-width:100%;'/></body></html>";
            return response($content, 200, $headers);
        }

        // Download: Teks Murni Convert Ke Word (.doc)
        if ($type === 'word') {
            $headers = ["Content-type" => "application/vnd.ms-word", "Content-Disposition" => "attachment;Filename=" . $filename . "_Teks.doc"];
            $content = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><body style='font-family: Arial; padding: 20px;'><h2>Hasil Ekstraksi/Analisis Multi-Fungsi</h2><hr/><p>$formattedText</p></body></html>";
            return response($content, 200, $headers);
        } 
        
        // Download: Teks Murni Convert Ke PDF (.pdf)
        if ($type === 'pdf') {
            $headers = ["Content-type" => "application/pdf", "Content-Disposition" => "attachment; filename=" . $filename . "_Teks.pdf"];
            $content = "%PDF-1.4\n1 0 obj\n<< /Title (Hasil Analisis Gambar) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 595 842] /Contents 5 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>\nendobj\n5 0 obj\n<< /Length 100 >>\nstream\nBT\n/F1 12 Tf\n50 800 Td\n(" . iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(["\r", "\n"], " ", $text)) . ") Tj\nET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000054 00000 n\n0000000103 00000 n\n0000000164 00000 n\n0000000327 00000 n\ntrailer\n<< /Size 6 /Root 2 0 R >>\nstartxref\n0000000477\n%%EOF";
            return response($content, 200, $headers);
        } 

        // Download Default: File Teks Biasa (.txt)
        $headers = ["Content-type" => "text/plain", "Content-Disposition" => "attachment; filename=" . $filename . ".txt"];
        return response($text, 200, $headers);
    }
}