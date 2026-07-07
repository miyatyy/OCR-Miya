<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miya OCR - Real Live AI Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght=300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #F3F0EC; }
        .pastel-pink { background-color: #FFDFD3; }
        .pastel-purple { background-color: #E2ECE9; }
        .pastel-blue { background-color: #DCE5E7; }
        .pastel-yellow { background-color: #FFF5E4; }
        .pastel-green { background-color: #E8F0E6; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-6 text-gray-700">

    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-xl p-8 border border-amber-100">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-slate-600 tracking-wide">✨ Miya OCR App (Super AI) ✨</h1>
            <p class="text-sm text-slate-400 mt-1">Mendukung pembacaan tulisan cetak, kaligrafi, dan gaya tulisan tangan apa pun secara live otomatis</p>
        </div>

        <div id="sectionOcr" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="flex flex-col gap-6">
                <div class="flex gap-4 justify-center">
                    <button id="btnModeCamera" onclick="switchMode('camera')" class="px-5 py-2.5 rounded-full font-semibold border-2 border-dashed border-purple-300 bg-purple-50 hover:bg-purple-100 transition-all text-xs">📷 Mode Kamera</button>
                    <button id="btnModeFile" onclick="switchMode('file')" class="px-5 py-2.5 rounded-full font-semibold border-2 border-dashed border-blue-300 bg-blue-50 hover:bg-blue-100 transition-all text-xs">📁 Mode Unggah File</button>
                </div>

                <div id="cameraArea" class="hidden flex flex-col items-center p-4 rounded-2xl pastel-yellow border border-orange-200">
                    <video id="webcam" autoplay playsinline class="w-full max-w-xs rounded-xl shadow-inner bg-black"></video>
                    <button onclick="takeSnapshot()" class="mt-4 px-6 py-2 bg-amber-200 hover:bg-amber-300 rounded-full text-xs font-semibold shadow transition-all">📸 Ambil Foto & Scan</button>
                </div>

                <div id="fileArea" class="hidden flex flex-col items-center p-8 rounded-2xl pastel-blue border border-cyan-200 text-center">
                    <label class="cursor-pointer">
                        <span class="px-6 py-3 bg-white hover:bg-slate-50 border border-cyan-300 text-cyan-600 rounded-full text-xs font-semibold shadow-sm inline-block transition-all">📂 Jelajahi Berkas Internal</span>
                        <input type="file" id="fileInput" onchange="processFile()" class="hidden" accept="image/*">
                    </label>
                    <p id="fileName" class="text-xs text-slate-400 mt-3 italic"></p>
                </div>

                <div id="loading" class="hidden text-center text-xs font-semibold text-purple-400 animate-pulse bg-purple-50 p-3 rounded-xl border border-purple-200">
                    🔮 Menghubungkan ke Google Gemini Vision API, memindai gaya tulisan riil...
                </div>

                <div class="mt-2 bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col gap-3">
                    <h4 class="text-xs font-bold text-slate-500 flex items-center gap-1.5">🕒 Riwayat Manajemen Cetak OCR:</h4>
                    <div id="historyLogList" class="flex flex-col gap-2 max-h-52 overflow-y-auto pr-1">
                        @forelse($histories as $history)
                            @php
                                $safeText = str_replace(["\r", "\n"], '\n', addslashes($history->extracted_text));
                            @endphp
                            <div id="history-row-{{ $history->id }}" class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl text-xs shadow-sm">
                                <div class="flex items-center gap-3 truncate max-w-xs">
                                    <img src="{{ $history->image_base64 }}" class="w-10 h-10 object-cover rounded-lg border border-slate-200">
                                    <p class="truncate text-slate-600 font-medium">{{ Str::limit($history->extracted_text, 40) ?: '[Gambar Tanpa Teks]' }}...</p>
                                </div>
                                <div class="flex gap-1">
                                    <button onclick="loadHistoryToText(`{{ $safeText }}`,'{{ $history->image_base64 }}')" class="p-1 px-2.5 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg font-semibold transition-all">Muat</button>
                                    <button onclick="deleteHistory({{ $history->id }})" class="p-1 px-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg font-semibold transition-all">Hapus</button>
                                </div>
                            </div>
                        @empty
                            <p id="emptyHistoryPlaceholder" class="text-slate-400 text-[11px] text-center italic py-4">Belum ada riwayat dokumen yang disimpan.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex flex-col bg-slate-50 rounded-2xl p-6 border border-slate-100 gap-6">
                <div id="previewContainer" class="hidden flex flex-col gap-3">
                    <h4 class="text-xs font-semibold text-slate-500">🖼️ Gambar Hasil Jepretan / File:</h4>
                    <img id="imagePreview" class="w-full max-h-40 object-contain rounded-xl border border-slate-200 shadow-sm bg-white" src="" alt="Pratinjau">
                </div>

                <div id="textContainer" class="hidden flex flex-col flex-grow gap-3">
                    <div>
                        <h3 class="font-semibold text-sm text-slate-500 mb-1.5">📄 Teks Hasil Ekstraksi Otomatis:</h3>
                        <textarea id="resultText" readonly class="w-full h-32 p-4 bg-white rounded-xl border border-slate-200 text-xs focus:outline-none resize-none font-semibold" placeholder="Hasil tulisan asli dari kamera akan muncul di sini..."></textarea>
                    </div>
                    
                    <div>
                        <p class="text-[11px] font-medium text-slate-400 mb-1.5">Pilihan Teks Convert Ke:</p>
                        <form action="{{ route('download.dokumen') }}" method="POST" class="grid grid-cols-3 gap-2">
                            @csrf
                            <input type="hidden" name="text" class="formTextClass">
                            <button type="submit" name="type" value="txt" class="py-1.5 text-center text-xs font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 transition-all">TXT</button>
                            <button type="submit" name="type" value="pdf" target="_blank" class="py-1.5 text-center text-xs font-semibold rounded-xl pastel-green text-green-700 hover:opacity-80 transition-all">PDF Cetak</button>
                            <button type="submit" name="type" value="word" class="py-1.5 text-center text-xs font-semibold rounded-xl pastel-yellow text-amber-700 hover:opacity-80 transition-all">MS Word</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let streamActive = null;

        function switchMode(mode) {
            document.getElementById('cameraArea').classList.add('hidden');
            document.getElementById('fileArea').classList.add('hidden');
            stopWebcam();
            if (mode === 'camera') {
                document.getElementById('cameraArea').classList.remove('hidden');
                startWebcam();
            } else if (mode === 'file') {
                document.getElementById('fileArea').classList.remove('hidden');
            }
        }

        function startWebcam() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => { streamActive = stream; document.getElementById('webcam').srcObject = stream; })
                .catch(err => alert("Gagal mengakses kamera."));
        }

        function stopWebcam() {
            if (streamActive) { streamActive.getTracks().forEach(track => track.stop()); }
        }

        function takeSnapshot() {
            const video = document.getElementById('webcam');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const base64Image = canvas.toDataURL('image/png');
            runOcrEngineLive(base64Image);
        }

        function processFile() {
            const fileInput = document.getElementById('fileInput');
            if (fileInput.files.length === 0) return;
            const file = fileInput.files[0];
            document.getElementById('fileName').innerText = "Terpilih: " + file.name;

            const reader = new FileReader();
            reader.onload = function (e) { runOcrEngineLive(e.target.result); };
            reader.readAsDataURL(file);
        }

        function runOcrEngineLive(imageSource) {
            document.getElementById('imagePreview').src = imageSource;
            document.getElementById('previewContainer').classList.remove('hidden');
            
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('textContainer').classList.add('hidden');
            document.getElementById('resultText').value = "";

            fetch('/proses-ocr', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ image_base64: imageSource })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('textContainer').classList.remove('hidden');
                if (data.success) {
                    document.getElementById('resultText').value = data.text;
                    const textInputs = document.getElementsByClassName('formTextClass');
                    for(let i=0; i<textInputs.length; i++) { textInputs[i].value = data.text; }
                    const placeholder = document.getElementById('emptyHistoryPlaceholder');
                    if(placeholder) placeholder.remove();
                    document.getElementById('historyLogList').insertAdjacentHTML('afterbegin', data.html_row);
                } else {
                    document.getElementById('resultText').value = "[Error Sistem: " + data.error + "]";
                }
            })
            .catch(err => {
                document.getElementById('loading').classList.add('hidden');
                alert("Gagal berkomunikasi dengan server backend.");
            });
        }

        function loadHistoryToText(extractedText, imgBase64) {
            const cleanText = extractedText.replace(/\\n/g, '\n');
            document.getElementById('resultText').value = cleanText;
            document.getElementById('imagePreview').src = imgBase64;
            document.getElementById('previewContainer').classList.remove('hidden');
            document.getElementById('textContainer').classList.remove('hidden');
            const textInputs = document.getElementsByClassName('formTextClass');
            for(let i=0; i<textInputs.length; i++) { textInputs[i].value = cleanText; }
        }

        function deleteHistory(id) {
            if (!confirm("Hapus data riwayat ini?")) return;
            fetch(`/history/delete/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(res => res.json()).then(data => { if(data.success) document.getElementById(`history-row-${id}`).remove(); });
        }
    </script>
</body>
</html>