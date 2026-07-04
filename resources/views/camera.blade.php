<!DOCTYPE html>
<html>
<head>
    <title>Camera OCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial;
            text-align: center;
            background: #f5f5f5;
        }

        video {
            width: 320px;
            border-radius: 10px;
            margin-top: 10px;
        }

        button {
            padding: 10px 20px;
            margin-top: 10px;
            cursor: pointer;
        }

        #result {
            margin-top: 20px;
            background: #fff;
            padding: 15px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 10px;
            min-height: 100px;
            white-space: pre-wrap;
        }

        .loading {
            color: blue;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

<h2>📸 Live Camera OCR</h2>

<video id="video" autoplay playsinline></video>

<br>

<button id="btn" onclick="capture()">📸 Capture & OCR</button>

<canvas id="canvas" style="display:none;"></canvas>

<div id="result">Menunggu hasil OCR...</div>

<script>

// =====================
// CAMERA START
// =====================
const video = document.getElementById('video');
const resultBox = document.getElementById('result');
const btn = document.getElementById('btn');

navigator.mediaDevices.getUserMedia({ video: true })
.then(stream => {
    video.srcObject = stream;
})
.catch(err => {
    resultBox.innerHTML = "<span class='error'>Kamera error: " + err + "</span>";
});


// =====================
// CAPTURE + OCR
// =====================
function capture() {

    btn.disabled = true;
    resultBox.innerHTML = "<span class='loading'>Memproses OCR...</span>";

    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video, 0, 0);

    canvas.toBlob(function(blob) {

        let formData = new FormData();
        formData.append("image", blob, "capture.png");

        fetch("http://127.0.0.1:8000/api/ocr", {
    method: "POST",
    body: formData
})
.then(async (res) => {

    if (!res.ok) {
        throw new Error("Server error: " + res.status);
    }

    return res.json();
})
.then(data => {
    console.log(data);
    document.getElementById("result").innerText = data.text;
})
.catch(err => {
    document.getElementById("result").innerText = "ERROR: " + err;
});

    }, "image/png");
}

</script>

</body>
</html>