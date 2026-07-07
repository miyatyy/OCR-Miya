<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\PdfController;

// --- DASHBOARD ALL-IN-ONE (Wajib panggil method index agar variabel $histories ikut terbaca) ---
Route::get('/', [OcrController::class, 'index'])->name('dashboard');

// Jalur proses untuk masing-masing fungsi OCR & Manajemen Riwayat
Route::post('/proses-ocr', [OcrController::class, 'prosesOcr'])->name('proses.ocr');
Route::post('/download-dokumen', [OcrController::class, 'downloadDokumen'])->name('download.dokumen');
Route::delete('/history/delete/{id}', [OcrController::class, 'hapusHistory'])->name('history.delete');

// --- FITUR GABUNGKAN PDF (MERGE) ---
Route::get('/merge-pdf', [PdfController::class, 'showMergePage'])->name('pdf.merge.index');
Route::post('/proses-merge-pdf', [PdfController::class, 'processMerge'])->name('pdf.merge.proses');

// --- FITUR PISAHKAN PDF (SPLIT) ---
Route::get('/split-pdf', [PdfController::class, 'showSplitPage'])->name('pdf.split');
Route::post('/split-pdf/process', [PdfController::class, 'processSplit'])->name('pdf.split.process');