<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_histories', function (Blueprint $table) {
            $table->id();
            $table->longText('image_base64'); // Menyimpan string preview gambar
            $table->longText('extracted_text')->nullable(); // Menyimpan teks hasil scan AI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_histories');
    }
};