<template>
    <div class="container-fluid" style="padding: 30px 15px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 class="fw-bold text-primary">Dashboard OCR Miya EduPro</h1>
            <p class="text-muted">Sistem Pemindai Tugas & Koreksi Lembar Jawaban Perguruan Tinggi</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0" style="padding: 20px; border-radius: 12px; background: #fff;">
                    <div class="mb-3" style="text-align: left;">
                        <label class="form-label fw-bold d-block">Pilih Sumber Gambar Murid:</label>
                        <div class="d-flex gap-2">
                            <label class="btn btn-outline-info w-50 m-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                                📸 Kamera
                                <input type="file" @change="handleFileUpload" accept="image/*" capture="environment" class="d-none">
                            </label>
                            <label class="btn btn-outline-secondary w-50 m-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                                📁 Galeri
                                <input type="file" @change="handleFileUpload" accept="image/*" class="d-none">
                            </label>
                        </div>
                    </div>

                    <div class="border rounded d-flex align-items-center justify-content-center bg-light mb-3" style="height: 230px; overflow: hidden; border-style: dashed !important;">
                        <img v-if="imagePreview" :src="imagePreview" class="img-fluid h-100 w-100" style="object-fit: contain;" alt="Preview Lembar Tugas" />
                        <div v-else class="text-muted text-center p-3 small">
                            <span>Belum ada lembar tugas murid yang dipilih</span>
                        </div>
                    </div>
                    
                    <small v-if="fileName" class="text-success d-block mb-3 text-truncate fw-bold">File: {{ fileName }}</small>
                    
                    <button @click="uploadImage" :disabled="loading || !file" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        {{ loading ? 'Sedang Memproses OCR...' : '🚀 Mulai Proses OCR' }}
                    </button>
                </div>
            </div>

            <div class="col-md-7 mb-4">
                <div class="card shadow-sm border-0" style="padding: 20px; border-radius: 12px; min-height: 365px; background: #fff;">
                    <div class="d-flex justify-content-between align-items-center mb-2" style="text-align: left;">
                        <label class="fw-bold text-success mb-0">📝 Panel Verifikasi Teks Guru/Dosen:</label>
                        
                        <div v-if="hasilTeks" class="btn-group animate__animated animate__fadeIn">
                            <button @click="downloadFile('word')" class="btn btn-sm text-white border-0 px-3 fw-bold" style="background-color: #2b579a;">
                                📥 Word (.doc)
                            </button>
                            <button @click="downloadFile('pdf')" class="btn btn-sm btn-danger text-white border-0 px-3 fw-bold">
                                📕 PDF
                            </button>
                        </div>
                    </div>

                    <div v-if="!hasilTeks && !loading" class="text-center text-muted d-flex flex-column justify-content-center align-items-center" style="min-height: 250px;">
                        <h5 class="fw-bold mb-1 text-secondary">Menunggu Pengunggahan Lembar Jawaban</h5>
                        <p class="small text-wrap" style="max-width: 400px;">
                            Jika tulisan tangan murid kurang rapi/jelek, guru dapat langsung menyunting teks hasil ekstraksi langsung di dalam panel kotak ini sebelum berkas diunduh.
                        </p>
                    </div>

                    <div v-if="hasilTeks || loading" class="w-100 animate__animated animate__fadeIn">
                        <div v-if="hasilTeks" class="alert alert-warning py-1 px-3 small mb-2 text-start" style="font-size: 13px;">
                            💡 <strong>Tips:</strong> Klik kotak teks di bawah untuk membenarkan typo tulisan tangan murid yang salah baca.
                        </div>
                        <textarea 
                            class="form-control shadow-inner font-monospace" 
                            rows="11" 
                            style="border: 2px solid #28a745; font-size: 14px; line-height: 1.6;" 
                            v-model="hasilTeks" 
                            placeholder="Hasil ekstraksi tulisan Tesseract lokal...">
                        </textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

// Variabel Reaktif (State)
const file = ref(null);
const fileName = ref('');
const imagePreview = ref('');
const hasilTeks = ref('');
const loading = ref(false);

// Menangkap Perubahan Berkas & Membuat Object URL Pratinjau Instan
const handleFileUpload = (event) => {
    const targetFile = event.target.files[0];
    if (targetFile) {
        file.value = targetFile;
        fileName.value = targetFile.name;
        imagePreview.value = URL.createObjectURL(targetFile); // Membuat preview instan
    }
};

// Mengirim File Dokumen Menuju Backend Laravel Tesseract
const uploadImage = async () => {
    if (!file.value) return alert('Silakan pilih atau ambil gambar terlebih dahulu!');
    
    loading.value = true;
    hasilTeks.value = '';

    let formData = new FormData();
    formData.append('file_gambar', file.value);

    try {
        const response = await axios.post('/api/ocr', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (response.data.success) {
            hasilTeks.value = response.data.text;
            alert('OCR Sukses! Hasil tulisan berhasil dimuat.');
        }
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.error || 'Gagal mengekstrak gambar.');
    } finally {
        loading.value = false;
    }
};

// Fungsi Mengunduh Teks yang Sudah Diverifikasi Menjadi Berkas Dokumen
const downloadFile = async (type) => {
    if (!hasilTeks.value) return;

    try {
        const response = await axios.post('/api/ocr/download', {
            text: hasilTeks.value,
            type: type
        }, { responseType: 'blob' });

        const blob = new Blob([response.data]);
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `Koreksi_Tugas_${Date.now()}.${type === 'word' ? 'doc' : 'pdf'}`;
        link.click();
        
    } catch (error) {
        console.error(error);
        alert('Gagal mengunduh file dokumen.');
    }
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
.shadow-inner {
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
}
</style>