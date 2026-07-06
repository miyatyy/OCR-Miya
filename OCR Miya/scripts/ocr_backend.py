import sys
import json
import cv2
import pytesseract

# Konfigurasi Path Tesseract di Laptop Kamu
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

def main():
    # Pastikan ada argumen path gambar yang dikirim oleh Laravel
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No image path provided"}))
        return

    image_path = sys.argv[1]
    
    try:
        # Baca gambar menggunakan OpenCV
        frame = cv2.imread(image_path)
        if frame is None:
            print(json.dumps({"error": "Failed to read image"}))
            return
            
        # Ekstraksi teks menggunakan pytesseract
        text = pytesseract.image_to_string(frame, lang="eng+ind").strip()
        
        # Kembalikan hasil dalam format JSON bersih agar bisa dibaca Laravel
        print(json.dumps({"text": text}))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()