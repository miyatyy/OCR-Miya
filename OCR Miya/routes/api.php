<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/ocr', function (Request $request) {

    // =========================
    // 1. VALIDASI FILE INPUT
    // =========================
    if (!$request->hasFile('image')) {
        return response()->json([
            'status' => false,
            'message' => 'No image uploaded'
        ], 400);
    }

    $request->validate([
        'image' => 'required|image|max:5120'
    ]);

    // =========================
    // 2. SIMPAN FILE
    // =========================
    $image = $request->file('image');
    $path = $image->store('ocr');

    $fullPath = storage_path('app/' . $path);

    if (!file_exists($fullPath)) {
        return response()->json([
            'status' => false,
            'message' => 'File not saved properly'
        ], 500);
    }

    // =========================
    // 3. AMANKAN PATH UNTUK NODE
    // =========================
    $escapedPath = escapeshellarg($fullPath);

    // =========================
    // 4. EKSEKUSI OCR NODE
    // =========================
    $output = shell_exec("node ocr.js " . $escapedPath);

    if ($output === null) {
        return response()->json([
            'status' => false,
            'message' => 'OCR engine failed (node not executed)'
        ], 500);
    }

    $text = trim($output);

    // =========================
    // 5. RESPONSE FINAL
    // =========================
    return response()->json([
        'status' => true,
        'text' => $text !== '' ? $text : 'No text detected'
    ]);
});