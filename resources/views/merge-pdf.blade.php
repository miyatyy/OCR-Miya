<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gabungkan PDF - Miya PDF Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: 700;
            color: #dc3545 !important;
        }
        .upload-area {
            border: 2px dashed #dc3545;
            background-color: #fff;
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            background-color: #fff5f5;
            transform: scale(1.01);
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-danger:hover {
            background-color: #bd2130;
        }
        .file-list-item {
            background: #fff;
            border-left: 5px solid #dc3545;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-5">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-file-pdf me-2"></i>Miya PDF</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-secondary" href="/"><i class="fa-solid fa-camera me-1"></i> Kembali ke Kamera OCR</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center mb-4">
                <h1 class="fw-bold text-dark">Gabungkan PDF</h1>
                <p class="text-muted fs-5">Gabungkan beberapa file PDF terpisah menjadi satu dokumen PDF utuh dengan mudah dan cepat.</p>
            </div>
            
            <div class="col-md-8">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('pdf.merge.proses') }}" method="POST" enctype="multipart/form-data" id="mergeForm">
                    @csrf
                    
                    <div class="upload-area shadow-sm" onclick="document.getElementById('pdf_files').click()">
                        <i class="fa-solid fa-cloud-arrow-up fa-4x text-danger mb-3"></i>
                        <h3>Pilih beberapa file PDF sekaligus</h3>
                        <p class="text-muted">atau seret dan lepas berkas PDF di sini (Minimal 2 File)</p>
                        <input type="file" name="pdf_files[]" id="pdf_files" class="d-none" multiple accept="application/pdf" onchange="previewFiles()">
                    </div>

                    <div id="filePreviewArea" class="mt-4 d-none">
                        <h5 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-list me-2"></i>Daftar Berkas Terpilih:</h5>
                        <div id="fileList"></div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-danger btn-lg w-100 shadow">
                                <i class="fa-solid fa-layer-group me-2"></i> Gabungkan Berkas PDF!
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewFiles() {
            const input = document.getElementById('pdf_files');
            const previewArea = document.getElementById('filePreviewArea');
            const fileList = document.getElementById('fileList');
            
            fileList.innerHTML = ''; // Reset daftar lama
            
            if (input.files.length > 0) {
                previewArea.classList.remove('d-none');
                
                // Urutkan dan tampilkan nama berkas satu per satu
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const fileSizeMb = (file.size / (1024 * 1024)).toFixed(2);
                    
                    const item = document.createElement('div');
                    item.className = 'file-list-item d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <div>
                            <i class="fa-regular fa-file-pdf text-danger me-2 fs-5"></i>
                            <span class="fw-semibold text-dark">${file.name}</span>
                        </div>
                        <span class="badge bg-secondary">${fileSizeMb} MB</span>
                    `;
                    fileList.appendChild(item);
                }
            } else {
                previewArea.classList.add('d-none');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>