<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\OcrHistory;

class OcrController extends Controller
{
    // 1. Menampilkan Halaman Dashboard Utama & Riwayat
    public function index()
    {
        $histories = OcrHistory::latest()->get();
        return view('ocr_dashboard', compact('histories'));
    }

    // 2. Memproses Ekstraksi Gambar Menggunakan Google Gemini API Secara Nyata
    public function prosesOcr(Request $request)
    {
        if (!$request->has('image_base64') || empty($request->image_base64)) {
            return response()->json(['success' => false, 'error' => 'Berkas gambar biner tidak ditemukan.']);
        }

        try {
            $rawBase64 = $request->image_base64;
            
            // Deteksi MimeType Gambar
            $mimeType = 'image/png';
            if (str_contains($rawBase64, 'data:image/jpeg') || str_contains($rawBase64, 'data:image/jpg')) {
                $mimeType = 'image/jpeg';
            }

            // Ekstrak data biner bersih dari string base64
            $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $rawBase64);
            $cleanBase64 = str_replace([' ', "\r", "\n"], ['+', '', ''], $cleanBase64);

            // 🎯 PENGIKAT KUNCI ABSOLUT: Menggunakan Kunci Resmi Milik Nurmiyaty Langsung dari Google AI Studio
            $apiKey = env('GEMINI_API_KEY', 'AQ.Ab8RN6Kslb21KPhM84HneWSmCB-Nadm5iLWC2JKn6pz7LFl2qw'); 
            
            // URL Endpoint Google Gemini 1.5 Flash Resmi
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

            // Payload Multimodal resmi untuk Google Vision API
            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "text" => "Tolong baca tulisan yang ada di dalam gambar ini secara sangat akurat dan ketik ulang seluruh hasilnya tanpa ada tambahan kalimat penjelasan atau komentar pembuka apa pun."
                            ],
                            [
                                "inlineData" => [
                                    "mimeType" => $mimeType,
                                    "data" => $cleanBase64
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            // Mengirimkan request ke server Google Cloud dengan bypass SSL lokal Windows XAMPP secara sempurna
            $response = Http::withoutVerifying()
                            ->withHeaders(['Content-Type' => 'application/json'])
                            ->timeout(45)
                            ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                // Menembus struktur JSON Candidates milik Google Gemini API
                $extractedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $extractedText = trim($extractedText);
                
                if (!empty($extractedText)) {
                    // Menyimpan data asli ke database lokal MariaDB
                    $history = OcrHistory::create([
                        'image_base64' => $rawBase64,
                        'extracted_text' => $extractedText
                    ]);

                    return response()->json([
                        'success' => true,
                        'text' => $extractedText,
                        'html_row' => $this->renderHistoryRow($history)
                    ]);
                }
            }

            // Jika API merespon tapi ada kesalahan parameter atau kuota limits harian
            $apiErrorMessage = $response->json()['error']['message'] ?? 'Respons API Google Studio tidak dikenali.';
            return response()->json(['success' => false, 'error' => 'API Google Menolak: ' . $apiErrorMessage]);

        } catch (\Exception $e) {
            // Mengembalikan pesan eror transmisi secara transparan ke browser
            return response()->json(['success' => false, 'error' => 'Kendala Jaringan Jembatan: ' . $e->getMessage()]);
        }
    }

    // 3. Menghapus Item Riwayat via AJAX
    public function hapusHistory($id)
    {
        $history = OcrHistory::find($id);
        if ($history) { 
            $history->delete(); 
            return response()->json(['success' => true]); 
        }
        return response()->json(['success' => false]);
    }

    // 4. Memproses Konversi Ekspor File Dokumen (TXT & Word)
    public function downloadDokumen(Request $request)
    {
        $text = $request->input('text', '');
        $type = $request->input('type', 'txt');
        $filename = "MiyaOCR_Export_" . time();

        if ($type === 'txt') {
            return response($text, 200)
                ->header('Content-Type', 'text/plain; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
        }
        if ($type === 'word') {
            $wordTemplate = "<html><head><meta charset='utf-8'></head><body><p>" . nl2br(e($text)) . "</p></body></html>";
            return response($wordTemplate, 200)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.doc"');
        }
    }

    // Komponen baris log riwayat scan UI
    private function renderHistoryRow($history)
    {
        $safeText = str_replace(["\r", "\n"], '\n', addslashes($history->extracted_text));
        return '
        <div id="history-row-'.$history->id.'" class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl text-xs shadow-sm">
            <div class="flex items-center gap-3 truncate max-w-xs">
                <img src="'.$history->image_base64.'" class="w-10 h-10 object-cover rounded-lg border border-slate-200">
                <p class="truncate text-slate-600 font-medium">'.(e(substr($history->extracted_text, 0, 40)) ?: "[Gambar Tanpa Teks]").'...</p>
            </div>
            <div class="flex gap-1">
                <button onclick="loadHistoryToText(`'.$safeText.'`,`'.$history->image_base64.'`)" class="p-1 px-2.5 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg font-semibold transition-all">Muat</button>
                <button onclick="deleteHistory('.$history->id.')" class="p-1 px-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg font-semibold transition-all">Hapus</button>
            </div>
        </div>';
    }
}