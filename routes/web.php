<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\PdfController;

// --- DASHBOARD ALL-IN-ONE (HALAMAN AWAL) ---
// Halaman utama sekarang langsung menampilkan dashboard lengkap
Route::get('/', function () {
    return view('ocr_dashboard');
});

// Jalur proses untuk masing-masing fungsi
Route::post('/proses-ocr', [OcrController::class, 'prosesOcr'])->name('proses.ocr');
Route::post('/download-dokumen', [OcrController::class, 'downloadDokumen'])->name('download.dokumen');

Route::post('/proses-merge-pdf', [PdfController::class, 'processMerge'])->name('pdf.merge.proses');