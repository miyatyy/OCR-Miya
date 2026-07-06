<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class PdfController extends Controller
{
    // Fungsi untuk menampilkan halaman form upload PDF
    public function showMergePage()
    {
        return view('merge-pdf');
    }

    // Fungsi inti untuk memproses penggabungan beberapa file PDF dengan sistem Preview AJAX
    public function processMerge(Request $request)
    {
        // 1. Validasi input dasar dari Frontend
        $request->validate([
            'pdf_files' => 'required',
            'pdf_files.*' => 'mimes:pdf|max:20480' // Maksimal ukuran per file 20MB
        ], [
            'pdf_files.required' => 'Silakan pilih file PDF terlebih dahulu.',
            'pdf_files.*.mimes' => 'Semua berkas yang diunggah harus berformat PDF.',
            'pdf_files.*.max' => 'Ukuran file PDF maksimal adalah 20MB.'
        ]);

        $files = $request->file('pdf_files');

        if (count($files) < 2) {
            return response()->json([
                'success' => false, 
                'message' => 'Minimal pilih 2 file PDF untuk digabungkan!'
            ]);
        }

        try {
            // 2. Inisialisasi object FPDI (Alat penggabung dokumen)
            $pdf = new Fpdi();
            $validFileCount = 0;

            // 3. Looping / Telusuri file PDF satu per satu yang di-upload user
            foreach ($files as $file) {
                
                // --- PROTEKSI FILE KOSONG (0.00 MB) ---
                if ($file->getSize() <= 0) {
                    continue; // Abaikan file kosong/rusak ini agar FPDI tidak error xref
                }

                $filePath = $file->getPathname();
                
                // Cek header file mentah untuk memastikan biner dokumen adalah PDF valid
                $handle = fopen($filePath, 'r');
                $firstLine = fgets($handle);
                fclose($handle);

                if (!str_contains($firstLine, '%PDF')) {
                    continue; // Lewati berkas jika tidak mengandung struktur header %PDF asli
                }
                // --------------------------------------

                // Hitung total halaman di dalam file PDF tersebut
                $pageCount = $pdf->setSourceFile($filePath);
                $validFileCount++;

                // Masukkan halaman satu per satu ke dalam PDF utama
                for ($i = 1; $i <= $pageCount; $i++) {
                    // Ambil halaman ke-$i
                    $templateId = $pdf->importPage($i);
                    // Dapatkan ukuran & orientasi halaman asli (Potrait/Landscape)
                    $size = $pdf->getTemplateSize($templateId);

                    // Tambahkan halaman baru dengan ukuran sesuai aslinya
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    // Tempelkan isinya
                    $pdf->useTemplate($templateId);
                }
            }

            // Validasi jika setelah disaring berkas yang sehat ternyata kurang dari 2
            if ($validFileCount < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menggabungkan. Pastikan dokumen yang kamu pilih dalam kondisi sehat dan tidak berukuran 0.00 MB.'
                ]);
            }

            // 4. Simpan berkas hasil gabungan ke folder public/temp_pdf secara sementara
            $filename = "MiyaPDF_Merge_" . time() . ".pdf";
            $tempFolder = public_path('temp_pdf');
            
            // Buat folder temp_pdf otomatis jika belum tersedia di direktori proyek
            if (!file_exists($tempFolder)) {
                mkdir($tempFolder, 0777, true);
            }

            // Simpan bentuk fisik file PDF ke dalam server lokal aplikasi
            $pdf->Output('F', $tempFolder . '/' . $filename);

            // 5. Kembalikan respon sukses beserta URL file agar bisa dibaca oleh UI Pratinjau Dashboard
            return response()->json([
                'success' => true,
                'file_url' => asset('temp_pdf/' . $filename),
                'file_name' => $filename
            ]);

        } catch (\Exception $e) {
            // Menangkap kesalahan struktur xref terkompresi atau enkripsi password dokumen
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan struktur dokumen: Salah satu file PDF yang diunggah dilindungi kata sandi atau versinya terlalu tinggi bagi parser standar.'
            ]);
        }
    }
}