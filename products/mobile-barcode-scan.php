<?php
require_once '../includes/auth.php';
requireRole(['admin']);
$page_title = "Scan Barcode";
include '../includes/layout.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Barcode — Zaina's Beauty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        #video-preview {
            width: 100%;
            max-width: 500px;
            aspect-ratio: 4/3;
            background: #000;
            margin: 0 auto;
            display: block;
        }
        .scan-line {
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 100%;
            background: rgba(0,255,0,0.7);
            transform: translateX(-50%);
            animation: scan 2s infinite;
        }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="text-center mb-4">
        <h2><i class="fas fa-camera me-2"></i> Scan Product Barcode</h2>
        <p class="text-muted">Point your camera at the barcode</p>
    </div>

    <div class="text-center mb-3">
        <video id="video-preview" autoplay playsinline></video>
        <div class="position-relative" style="height: 0;">
            <div class="scan-line"></div>
        </div>
    </div>

    <div id="scan-result" class="text-center mb-3"></div>

    <div class="d-grid gap-2">
        <button id="toggleCamera" class="btn btn-outline-secondary">
            <i class="fas fa-video me-2"></i> Toggle Camera
        </button>
        <button class="btn btn-secondary" onclick="window.close()">Close</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
let stream = null;

// Start camera
async function startCamera() {
    try {
        const constraints = { video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } };
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        document.getElementById('video-preview').srcObject = stream;
        
        Quagga.init({
            inputStream: { name: "Live", type: "LiveStream", target: document.querySelector('#video-preview') },
            decoder: { readers: ["code_128_reader", "ean_reader", "upc_reader", "i2of5_reader", "codabar_reader"] }
        }, function(err) {
            if (err) return;
            Quagga.start();
        });

        Quagga.onDetected(function(data) {
            const code = data.codeResult.code;
            if (code) {
                Quagga.stop();
                handleScanResult(code);
                setTimeout(() => { if (stream) Quagga.start(); }, 1000);
            }
        });
    } catch (err) {
        showError("Camera access denied. Please allow camera permission.");
    }
}

// Send scanned code to parent window (product form)
function handleScanResult(barcode) {
    document.getElementById('scan-result').innerHTML = `
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> Scanned: <strong>${barcode}</strong>
        </div>
    `;
    
    // Send to parent window (add.php)
    if (window.opener && window.opener.fillProductFromBarcode) {
        window.opener.fillProductFromBarcode(barcode);
        setTimeout(() => window.close(), 1000);
    } else {
        // Fallback: show error
        showError("Please open this scanner from the product form.");
    }
}

function showError(msg) {
    document.getElementById('scan-result').innerHTML = `<div class="alert alert-danger">${msg}</div>`;
}

document.getElementById('toggleCamera').addEventListener('click', function() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
        Quagga.stop();
        document.getElementById('video-preview').srcObject = null;
        this.innerHTML = '<i class="fas fa-video me-2"></i> Start Camera';
    } else {
        startCamera();
        this.innerHTML = '<i class="fas fa-video-slash me-2"></i> Stop Camera';
    }
});

window.addEventListener('load', startCamera);
</script>
</body>
</html>