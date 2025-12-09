<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Zaina Beauty POS – Mobile Scan</title>

    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">

    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
        }

        .scanner-box {
            width: 100%;
            max-width: 520px;
            margin: 25px auto;
            background: #000;
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        }

        #interactive.viewport {
            width: 100%;
            height: 420px;
        }

        .torch-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.25);
            color: white;
            border: none;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            backdrop-filter: blur(4px);
            font-size: 20px;
            display: none;
            z-index: 10;
        }

        .scan-feedback {
            display: none;
            margin-top: 15px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 17px;
            text-align: center;
        }

        .btn-scan {
            padding: 14px 35px;
            font-size: 18px;
            border-radius: 50px;
            background: #28a745;
            color: white;
            border: none;
        }

        a.cart-link {
            display: block;
            margin-top: 20px;
            font-size: 18px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container py-4 text-center">
    <h2 class="fw-bold mb-3">Scan Product</h2>

    <div class="scanner-box">
        <div id="interactive" class="viewport"></div>
        <button id="torchBtn" class="torch-btn">🔦</button>
    </div>

    <button id="startBtn" class="btn-scan">Start Scanning</button>

    <div id="scanFeedback" class="scan-feedback"></div>

    <a href="../cart.php" class="cart-link">
        Go to Cart (<span id="cartCount"><?= count($_SESSION['cart']) ?></span>)
    </a>
</div>

<!-- Quagga -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

<!-- Beep -->
<audio id="beepSound" src="../../assets/sounds/beep.mp3" preload="auto"></audio>

<script>
/* ELEMENTS */
const beepSound   = document.getElementById('beepSound');
const feedback    = document.getElementById('scanFeedback');
const startBtn    = document.getElementById('startBtn');
const cartCount   = document.getElementById('cartCount');
const torchBtn    = document.getElementById('torchBtn');

let lastScan = { code: null, time: 0 };
let streamRef = null;
let torchOn = false;

/* UPDATE CART */
function updateCart() {
    fetch('get_cart_count.php')
        .then(r => r.json())
        .then(d => cartCount.textContent = d.count)
        .catch(() => {});
}

/* INIT SCANNER */
startBtn.addEventListener('click', () => {
    startBtn.disabled = true;
    startBtn.textContent = "Initializing...";

    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector('#interactive'),
            constraints: {
                facingMode: "environment",
                width: { min: 640 },
                height: { min: 480 }
            },
            area: {
                top: "20%",
                bottom: "20%",
                left: "10%",
                right: "10%"
            }
        },
        locator: {
            patchSize: "medium",
            halfSample: true
        },
        decoder: {
            readers: [
                "code_128_reader",
                "ean_reader",
                "ean_8_reader",
                "upc_reader",
                "code_39_reader"
            ]
        },
        numOfWorkers: navigator.hardwareConcurrency || 4,
        locate: true
    }, err => {
        if (err) {
            showError("Camera Error: " + err.message + "<br>Allow camera access and reload.");
            return;
        }

        Quagga.start();
        startBtn.textContent = "Scanning...";
        torchBtn.style.display = "block";
    });
});

/* PROCESS SCAN */
Quagga.onDetected(data => {
    const code = data.codeResult.code;
    const now = Date.now();

    if (code === lastScan.code && now - lastScan.time < 1500) return;

    lastScan = { code, time: now };

    beepSound.play().catch(() => {});

    feedback.style.display = "block";
    feedback.style.background = "#e5ffe5";
    feedback.style.color = "#155724";
    feedback.innerHTML = `Scanned: <strong>${code}</strong><br>Adding...`;

    fetch("add_to_cart_scan.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "barcode=" + encodeURIComponent(code)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            feedback.innerHTML = `${res.product_name} added! (KES ${res.price})`;
            feedback.style.background = "#d4edda";
            updateCart();

            const box = document.querySelector(".viewport");
            box.style.border = "4px solid #28a745";
            setTimeout(() => box.style.border = "none", 700);

        } else {
            showError("Not Found: " + code);
        }
    })
    .catch(() => showError("Server error"));
});

/* TORCH */
torchBtn.addEventListener("click", () => {
    if (!streamRef) {
        streamRef = document.querySelector('#interactive video')?.srcObject;
    }
    if (!streamRef) return;

    const track = streamRef.getVideoTracks()[0];
    const caps = track.getCapabilities();

    if (caps.torch) {
        torchOn = !torchOn;
        track.applyConstraints({ advanced: [{ torch: torchOn }] });
        torchBtn.textContent = torchOn ? "💡" : "🔦";
    }
});

/* ERROR MESSAGE */
function showError(msg) {
    feedback.style.display = "block";
    feedback.style.background = "#f8d7da";
    feedback.style.color = "#721c24";
    feedback.innerHTML = msg;
}

/* STOP CAMERA ON EXIT */
window.addEventListener("beforeunload", () => Quagga.stop());

document.addEventListener("visibilitychange", () => {
    document.hidden ? Quagga.stop() : Quagga.start();
});
</script>

</body>
</html>
