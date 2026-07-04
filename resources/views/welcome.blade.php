<!DOCTYPE html>
<html>
<head>
    <title>OCR Camera</title>
</head>
<body>

<h2>Live Camera OCR</h2>

<video id="video" autoplay style="width:300px;"></video>
<button onclick="capture()">Capture</button>

<canvas id="canvas" style="display:none;"></canvas>

<p>Hasil OCR:</p>
<pre id="result"></pre>

<script>
const video = document.getElementById('video');

// hidupkan kamera
navigator.mediaDevices.getUserMedia({ video: true })
.then(stream => {
    video.srcObject = stream;
});

// capture image
function capture() {
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    context.drawImage(video, 0, 0);

    canvas.toBlob(blob => {
        let formData = new FormData();
        formData.append("image", blob, "capture.png");

        fetch("/ocr", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById("result").innerText = data.text;
        });
    });
}
</script>

</body>
</html>