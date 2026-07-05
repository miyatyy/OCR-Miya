<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OcrController;



Route::get('/', function () {
    return view('ocr_dashboard');
});


Route::post('/proses-ocr', [OcrController::class, 'prosesOcr'])->name('proses.ocr');


Route::post('/download-dokumen', [OcrController::class, 'downloadDokumen'])->name('download.dokumen');