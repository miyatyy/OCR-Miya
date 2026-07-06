import cv2
import pytesseract
import requests
import sys

# 1. KONFIGURASI TESSERACT & API LARAVEL
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
LARAVEL_API_URL = "http://127.0.0.1:8000/api/save-ocr"

print("Mencari webcam yang tersedia...")

# 2. DETEKSI KAMERA OTOMATIS
cap = None
indeks_terpilih = -1

# Mencoba beberapa indeks kamera yang umum (0, 1, 2)
for index in [0, 1, 2]:
    cap = cv2.VideoCapture(index)
    if cap.isOpened():
        # Lakukan tes membaca 1 frame untuk memastikan kamera benar-benar mengirim gambar
        ret, frame = cap.read()
        if ret:
            indeks_terpilih = index
            print(f"-> Berhasil terhubung ke Kamera pada indeks: {indeks_terpilih}")
            break
    cap.release()

# Jika masih gagal, coba paksa pakai backend DirectShow (DSHOW)
if indeks_terpilih == -1:
    for index in [0, 1, 2]:
        cap = cv2.VideoCapture(index, cv2.CAP_DSHOW)
        if cap.isOpened():
            ret, frame = cap.read()
            if ret:
                indeks_terpilih = index
                print(f"-> Berhasil terhubung ke Kamera (DSHOW) pada indeks: {indeks_terpilih}")
                break
        cap.release()

# JIKA SEMUA PERCOBAAN GAGAL
if indeks_terpilih == -1 or cap is None or not cap.isOpened():
    print("\n==================================================================")
    print("[EROR BESAR] Python tidak bisa mengakses kamera kamu sama sekali!")
    print("Kemungkinan penyebab:")
    print("1. Kamera sedang dipakai aplikasi lain (Zoom, WA Desktop, Browser, dll).")
    print("2. Driver webcam kamu bermasalah atau belum diizinkan di Privacy Settings Windows.")
    print("==================================================================")
    sys.exit()

print("\nKamera aktif! Hadapkan teks ke kamera.")
print("Tekan [SPACE] untuk scan OCR & kirim ke Laravel.")
print("Tekan [ESC] untuk keluar aplikasi.\n")

# 3. LOOPING UTAMA KAMERA
while True:
    ret, frame = cap.read()

    if not ret:
        print("[Peringatan] Gagal mengambil gambar dari frame kamera.")
        break

    # Tampilkan jendela kamera
    cv2.imshow("OCR Camera", frame)
    
    # Menunggu input tombol dari user
    key = cv2.waitKey(1) & 0xFF

    if key == 32:  # Tombol SPACE (Spasi)
        print("\nMemproses OCR, mohon tunggu...")
        try:
            text = pytesseract.image_to_string(frame, lang="eng+ind").strip()
            
            print("===== HASIL SCAN OCR =====")
            print(text if text else "[Tidak ada teks terdeteksi]")
            print("==========================")
            
            if text:
                # Kirim data ke backend Laravel
                payload = {'text_result': text}
                response = requests.post(LARAVEL_API_URL, json=payload)
                
                if response.status_code == 200:
                    print("-> BERHASIL: Data sukses dikirim ke Laravel ->", response.json())
                else:
                    print(f"-> GAGAL API: Kode Status {response.status_code}. Respon: {response.text}")
        except Exception as e:
            print(f"-> EROR PROSES: {str(e)}")

    elif key == 27:  # Tombol ESC
        print("\nMenutup aplikasi kamera...")
        break

# Bersihkan resources setelah keluar
cap.release()
cv2.destroyAllWindows()