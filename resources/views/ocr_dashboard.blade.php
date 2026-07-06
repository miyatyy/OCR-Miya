<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miya OCR - Pastel Dashboard (AI Engine)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
            <p class="text-sm text-slate-400 mt-1">Mendukung pembacaan tulisan cetak, kaligrafi, dan gaya tulisan tangan apa pun secara akurat</p>
        </div>

        <div class="flex gap-2 justify-center mb-8 border-b border-slate-100 pb-4">
            <button onclick="switchTab('ocr')" id="tabOcr" class="px-5 py-2 rounded-full font-semibold text-xs transition-all bg-amber-100 text-amber-700 shadow-sm">
                🧠 Miya AI Tools
            </button>
            <button onclick="switchTab('merge')" id="tabMerge" class="px-5 py-2 rounded-full font-semibold text-xs transition-all text-slate-400 hover:bg-slate-50">
                📄 Gabungkan PDF
            </button>
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

                <div id="loading" class="hidden text-center text-xs font-semibold text-purple-400 animate-pulse">
                    🔮 Menghubungkan ke Mesin Cloud AI, memindai gaya tulisan...
                </div>
            </div>

            <div class="flex flex-col bg-slate-50 rounded-2xl p-6 border border-slate-100 gap-6">
                <div id="previewContainer" class="hidden flex flex-col gap-3">
                    <h4 class="text-xs font-semibold text-slate-500">🖼️ Gambar Hasil Jepretan / File:</h4>
                    <img id="imagePreview" class="w-full max-h-40 object-contain rounded-xl border border-slate-200 shadow-sm bg-white" src="" alt="Pratinjau">
                    
                    <div>
                        <p class="text-[11px] font-medium text-slate-400 mb-1.5">Pilihan Gambar Convert Ke:</p>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" onclick="convertImageToPdf()" class="py-1.5 text-center text-xs font-semibold rounded-xl pastel-pink text-slate-600 hover:opacity-80 transition-all">PDF</button>
                            
                            <form action="{{ route('download.dokumen') }}" method="POST" class="contents">
                                @csrf
                                <input type="hidden" name="text" class="formTextClass">
                                <button type="submit" name="type" value="txt" class="py-1.5 text-center text-xs font-semibold rounded-xl border border-slate-300 bg-white hover:bg-slate-100 transition-all">TXT</button>
                            </form>

                            <button type="button" onclick="convertImageToWord()" class="py-1.5 text-center text-xs font-semibold rounded-xl pastel-blue text-slate-600 hover:opacity-80 transition-all">Word</button>
                        </div>
                    </div>
                </div>

                <div id="textContainer" class="hidden flex flex-col flex-grow gap-3">
                    <div>
                        <h3 class="font-semibold text-sm text-slate-500 mb-1.5">📄 Teks Hasil Ekstraksi:</h3>
                        <textarea id="resultText" readonly class="w-full h-32 p-4 bg-white rounded-xl border border-slate-200 text-xs focus:outline-none resize-none" placeholder="Hasil tulisan akan muncul di sini..."></textarea>
                    </div>
                    
                    <div>
                        <p class="text-[11px] font-medium text-slate-400 mb-1.5">Pilihan Teks Convert Ke:</p>
                        <form action="{{ route('download.dokumen') }}" method="POST" class="grid grid-cols-2 gap-2">
                            @csrf
                            <input type="hidden" name="text" class="formTextClass">
                            <button type="submit" name="type" value="pdf" class="py-1.5 text-center text-xs font-semibold rounded-xl pastel-green text-slate-600 hover:opacity-80 transition-all">PDF</button>
                            <button type="submit" name="type" value="word" class="py-1.5 text-center text-xs font-semibold rounded-xl pastel-yellow text-slate-600 hover:opacity-80 transition-all">Word</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="sectionMerge" class="hidden flex flex-col items-center justify-center p-4">
            <div class="w-full max-w-2xl bg-slate-50 border border-slate-200 rounded-2xl p-6">
                <h3 class="font-bold text-lg text-slate-600 text-center mb-2">📂 Gabungkan Dokumen PDF</h3>
                <p class="text-xs text-slate-400 text-center mb-6">Pilih 2 file atau lebih berkas PDF lokal untuk disatukan menjadi satu file utuh.</p>
                
                <form id="dashboardMergeForm" onsubmit="submitMergeForm(event)" enctype="multipart/form-data">
                    @csrf
                    <div onclick="document.getElementById('dashboardPdfFiles').click()" class="border-2 border-dashed border-red-300 hover:bg-red-50/50 bg-white rounded-xl p-8 text-center cursor-pointer transition-all">
                        <span class="text-3xl block mb-2">📄✨</span>
                        <span class="px-5 py-2 bg-red-100 text-red-600 rounded-full text-xs font-semibold shadow-sm inline-block">Pilih Berkas PDF</span>
                        <p class="text-[11px] text-slate-400 mt-2">Mendukung unggahan multi-file sekaligus</p>
                        <input type="file" name="pdf_files[]" id="dashboardPdfFiles" class="hidden" multiple accept="application/pdf" onchange="previewDashboardPdf()">
                    </div>

                    <div id="dashPdfPreviewArea" class="hidden mt-5">
                        <h4 class="text-xs font-semibold text-slate-500 mb-2">📋 Daftar Dokumen Terpilih:</h4>
                        <div id="dashPdfList" class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-1"></div>
                        
                        <button type="submit" id="btnStartMerge" class="w-full mt-4 py-2.5 text-center text-xs font-semibold rounded-xl pastel-pink text-red-700 shadow-sm hover:opacity-90 transition-all">
                            🔗 Mulai Penggabungan Dokumen
                        </button>
                    </div>
                </form>

                <div id="mergeLoading" class="hidden text-center text-xs font-semibold text-red-400 animate-pulse my-4">
                    ⏳ Sedang menyatukan halaman berkas PDF kamu, mohon tunggu...
                </div>

                <div id="visualPreviewArea" class="hidden flex flex-col gap-4 mt-2">
                    <h4 class="text-xs font-semibold text-slate-500 text-center">👁️ Pratinjau Visual Hasil Gabungan:</h4>
                    
                    <iframe id="pdfIframePreview" class="w-full h-96 rounded-xl border border-slate-200 shadow-inner bg-white"></iframe>
                    
                    <p class="text-[11px] text-slate-400 text-center">Apakah hasil penggabungan di atas sudah sesuai?</p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" onclick="resetMergeSelection()" class="py-2.5 text-center text-xs font-semibold rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-600 transition-all">
                            ❌ Atur Ulang Pilihan
                        </button>
                        <a id="btnDownloadAcc" href="#" download class="py-2.5 text-center text-xs font-semibold rounded-xl pastel-green text-green-700 shadow-sm hover:opacity-90 transition-all block">
                            📥 ACC & Download File
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        let streamActive = null;
        let currentBase64Image = "";

        function switchTab(tab) {
            document.getElementById('sectionOcr').classList.add('hidden');
            document.getElementById('sectionMerge').classList.add('hidden');
            
            document.getElementById('tabOcr').className = "px-5 py-2 rounded-full font-semibold text-xs transition-all text-slate-400 hover:bg-slate-50";
            document.getElementById('tabMerge').className = "px-5 py-2 rounded-full font-semibold text-xs transition-all text-slate-400 hover:bg-slate-50";

            if (tab === 'ocr') {
                document.getElementById('sectionOcr').classList.remove('hidden');
                document.getElementById('tabOcr').className = "px-5 py-2 rounded-full font-semibold text-xs transition-all bg-amber-100 text-amber-700 shadow-sm";
            } else if (tab === 'merge') {
                document.getElementById('sectionMerge').classList.remove('hidden');
                document.getElementById('tabMerge').className = "px-5 py-2 rounded-full font-semibold text-xs transition-all bg-red-100 text-red-700 shadow-sm";
                stopWebcam();
            }
        }

        function previewDashboardPdf() {
            const input = document.getElementById('dashboardPdfFiles');
            const previewArea = document.getElementById('dashPdfPreviewArea');
            const fileList = document.getElementById('dashPdfList');
            fileList.innerHTML = '';

            if (input.files.length > 0) {
                previewArea.classList.remove('hidden');
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                    const div = document.createElement('div');
                    div.className = "flex justify-content border justify-between p-2.5 bg-white border-slate-200 rounded-xl text-xs";
                    div.innerHTML = `<span class="truncate max-w-xs text-slate-600 font-medium">📄 ${file.name}</span><span class="text-slate-400 text-[10px]">${sizeMb} MB</span>`;
                    fileList.appendChild(div);
                }
            } else {
                previewArea.classList.add('hidden');
            }
        }

        // DISISIPKAN HEADER KEAMANAN CSRF UNTUK MENGHINDARI ERROR 419 PAGE EXPIRED
        function submitMergeForm(e) {
            e.preventDefault();
            
            const form = document.getElementById('dashboardMergeForm');
            const formData = new FormData(form);
            
            document.getElementById('mergeLoading').classList.remove('hidden');
            document.getElementById('btnStartMerge').disabled = true;

            fetch('/proses-merge-pdf', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('mergeLoading').classList.add('hidden');
                document.getElementById('btnStartMerge').disabled = false;
                
                if (data.success) {
                    form.classList.add('hidden');
                    document.getElementById('visualPreviewArea').classList.remove('hidden');
                    document.getElementById('pdfIframePreview').src = data.file_url;
                    
                    const downloadBtn = document.getElementById('btnDownloadAcc');
                    downloadBtn.href = data.file_url;
                    downloadBtn.setAttribute('download', data.file_name);
                } else {
                    alert(data.message || "Gagal menggabungkan dokumen PDF.");
                }
            })
            .catch(err => {
                document.getElementById('mergeLoading').classList.add('hidden');
                document.getElementById('btnStartMerge').disabled = false;
                alert("Terjadi kesalahan transmisi jaringan sistem.");
            });
        }

        function resetMergeSelection() {
            document.getElementById('dashboardPdfFiles').value = "";
            document.getElementById('dashPdfList').innerHTML = "";
            document.getElementById('dashPdfPreviewArea').classList.add('hidden');
            document.getElementById('pdfIframePreview').src = "";
            
            document.getElementById('visualPreviewArea').classList.add('hidden');
            document.getElementById('dashboardMergeForm').classList.remove('hidden');
        }

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
                .then(stream => {
                    streamActive = stream;
                    document.getElementById('webcam').srcObject = stream;
                })
                .catch(err => alert("Gagal mengakses kamera: " + err));
        }

        function stopWebcam() {
            if (streamActive) {
                streamActive.getTracks().forEach(track => track.stop());
            }
        }

        function takeSnapshot() {
            const video = document.getElementById('webcam');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const base64Image = canvas.toDataURL('image/png');
            
            showImagePreview(base64Image);
            runOcrEngine(base64Image);
        }

        function processFile() {
            const fileInput = document.getElementById('fileInput');
            if (fileInput.files.length === 0) return;

            const file = fileInput.files[0];
            document.getElementById('fileName').innerText = "Terpilih: " + file.name;

            const reader = new FileReader();
            reader.onload = function (e) {
                showImagePreview(e.target.result);
                runOcrEngine(e.target.result);
            };
            reader.readAsDataURL(file);
        }

        function showImagePreview(src) {
            currentBase64Image = src;
            document.getElementById('imagePreview').src = src;
            document.getElementById('previewContainer').classList.remove('hidden');
            
            const imgInputs = document.getElementsByClassName('formImageClass');
            for(let i=0; i<imgInputs.length; i++) {
                imgInputs[i].value = src;
            }
        }

        function convertImageToPdf() {
            if (!currentBase64Image) return alert("Belum ada gambar yang diproses.");
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Hasil_Gambar_Convert_PDF</title>
                    <style>
                        body { margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: white; }
                        img { max-width: 100%; max-height: 100%; object-contain: scale-down; }
                        @media print { @page { margin: 0; } body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <img src="${currentBase64Image}" onload="window.print(); window.close();"/>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        function convertImageToWord() {
            if (!currentBase64Image) return alert("Belum ada gambar yang diproses.");
            const contentBase64 = currentBase64Image.replace(/^data:image\/(png|jpeg|jpg);base64,/, "");
            const mhtmlContent = `MIME-Version: 1.0\nContent-Type: multipart/related; boundary=\"NEXT_PART_BOUNDARY\"\n\n--NEXT_PART_BOUNDARY\nContent-Type: text/html; charset=\"utf-8\"\nContent-Location: main.html\n\n<!DOCTYPE html><html><head><meta charset=\"utf-8\"></head><body><h2>Berkas Hasil Kloning Gambar Miya OCR</h2><hr/><br/><p><img src=\"image.png\" width=\"450\" /></p></body></html>\n\n--NEXT_PART_BOUNDARY\nContent-Type: image/png\nContent-Transfer-Encoding: base64\nContent-Location: image.png\n\n${contentBase64}\n--NEXT_PART_BOUNDARY--`;
            const blob = new Blob([mhtmlContent], { type: 'application/msword' });
            const downloadLink = document.createElement('a');
            downloadLink.href = URL.createObjectURL(blob);
            downloadLink.download = `Hasil_Gambar_Convert_${Date.now()}.doc`;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function runOcrEngine(imageSource) {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('textContainer').classList.add('hidden');
            document.getElementById('resultText').value = "";

            fetch('/proses-ocr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ image_base64: imageSource })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('textContainer').classList.remove('hidden');
                
                if (data.success) {
                    document.getElementById('resultText').value = data.text;
                    
                    const textInputs = document.getElementsByClassName('formTextClass');
                    for(let i=0; i<textInputs.length; i++) {
                        textInputs[i].value = data.text;
                    }
                } else {
                    document.getElementById('resultText').value = "[Eror: " + data.error + "]";
                }
            })
            .catch(err => {
                document.getElementById('loading').classList.add('hidden');
                alert("Gagal berkomunikasi dengan server backend.");
            });
        }
    </script>
</body>
</html>