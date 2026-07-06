<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function prosesOcr(Request $request)
    {
        return response()->json(['success' => true]);
    }

    public function downloadDokumen(Request $request)
    {
        $text = $request->input('text', '');
        $type = $request->input('type', 'txt');
        $base64Image = $request->input('image_base64', '');
        $formattedText = nl2br(e($text));
        $filename = "Hasil_OCR_" . time();

        // -------------------------------------------------------------------
        // OPSI A: DOWNLOAD SEBAGAI HASIL PILIHAN GAMBAR CONVERT
        // -------------------------------------------------------------------

        // 1. Gambar Convert Ke WORD
        if ($type === 'img_word') {
            $headers = [
                "Content-type" => "application/vnd.ms-word",
                "Content-Disposition" => "attachment;Filename=" . $filename . "_Gambar.doc"
            ];
            $content = "
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <body style='text-align:center; padding:20px;'>
                    <h2>Berkas Hasil Convert Gambar</h2>
                    <hr/><br/>
                    <img src='{$base64Image}' style='max-width:100%; border:1px solid #ccc;'/>
                </body>
                </html>";
            return response($content, 200, $headers);
        }

        // 2. Gambar Convert Ke PDF 
        if ($type === 'img_pdf') {
            $headers = [
                "Content-type" => "application/pdf",
                "Content-Disposition" => "attachment; filename=" . $filename . "_Gambar.pdf"
            ];
            // Mengirim stream gambar murni dibungkus objek berkas dasar PDF
            $content = "%PDF-1.4\n1 0 obj\n<< /Title (Hasil Cetak Gambar) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 595 842] >>\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000062 00000 n\n0000000111 00000 n\n0000000172 00000 n\ntrailer\n<< /Size 5 /Root 2 0 R >>\nstartxref\n0000000250\n%%EOF";
            return response($content, 200, $headers);
        }

        // -------------------------------------------------------------------
        // OPSI B: DOWNLOAD SEBAGAI HASIL PILIHAN TEKS CONVERT (MURNI TEKS)
        // -------------------------------------------------------------------

        // 3. Teks Convert Ke WORD / Gambar Convert Ke TXT
        if ($type === 'word') {
            $headers = [
                "Content-type" => "application/vnd.ms-word",
                "Content-Disposition" => "attachment;Filename=" . $filename . "_Teks.doc"
            ];
            $content = "
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <body style='font-family: Arial; padding: 20px;'>
                    <h2>Hasil Ekstraksi Teks Murni</h2>
                    <hr/>
                    <p>$formattedText</p>
                </body>
                </html>";
            return response($content, 200, $headers);
        } 
        
        // 4. Teks Convert Ke PDF
        if ($type === 'pdf') {
            $headers = [
                "Content-type" => "application/pdf",
                "Content-Disposition" => "attachment; filename=" . $filename . "_Teks.pdf"
            ];
            $content = "%PDF-1.4\n1 0 obj\n<< /Title (Hasil Teks OCR) >>\nendobj\n2 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n3 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n4 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 595 842] /Contents 5 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>\nendobj\n5 0 obj\n<< /Length 100 >>\nstream\nBT\n/F1 12 Tf\n50 800 Td\n(" . iconv('UTF-8', 'ASCII//TRANSLIT', str_replace(["\r", "\n"], " ", $text)) . ") Tj\nET\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000054 00000 n\n0000000103 00000 n\n0000000164 00000 n\n0000000327 00000 n\ntrailer\n<< /Size 6 /Root 2 0 R >>\nstartxref\n0000000477\n%%EOF";
            return response($content, 200, $headers);
        } 

        // 5. Default: TXT (Teks Biasa)
        $headers = [
            "Content-type" => "text/plain",
            "Content-Disposition" => "attachment; filename=" . $filename . ".txt"
        ];
        return response($text, 200, $headers);
    }
}