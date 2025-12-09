<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
$page_title = "Batch Scan Stock Intake";
include '../includes/layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-qrcode me-2"></i> Batch Scan Stock Intake</h4>
            <a href="index.php" class="btn btn-secondary btn-sm">← Back to Stock</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Scan barcodes one by one. Each scan adds 1 unit to stock.
                Press <strong>ESC</strong> to stop scanning.
            </div>
            
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                <input type="text" 
                       id="batchScanner" 
                       class="form-control form-control-lg" 
                       placeholder="Scan barcode..."
                       autocomplete="off"
                       autofocus>
            </div>
            
            <div id="scanLog" class="mt-3" style="max-height: 300px; overflow-y: auto;">
                <p class="text-muted">Scan log will appear here...</p>
            </div>
        </div>
    </div>
</div>

<script>
let scanLog = [];
const logEl = document.getElementById('scanLog');

// Handle scans
document.getElementById('batchScanner').addEventListener('keydown', async function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = this.value.trim();
        this.value = '';
        
        if (barcode) {
            try {
                const response = await fetch('/zaina-beauty/stock/update-stock-by-barcode.php?barcode=' + encodeURIComponent(barcode));
                const result = await response.json();
                
                if (result.success) {
                    addToLog(`✅ ${result.product} → Stock +1 (New: ${result.new_stock})`, 'success');
                } else {
                    addToLog(`❌ ${barcode}: ${result.error}`, 'danger');
                }
            } catch (error) {
                addToLog(`⚠️ Network error: ${barcode}`, 'warning');
            }
        }
    } else if (e.key === 'Escape') {
        // Stop scanning
        this.blur();
    }
});

function addToLog(message, type = 'info') {
    const now = new Date().toLocaleTimeString();
    const logItem = document.createElement('div');
    logItem.className = `alert alert-${type} alert-dismissible fade show mb-2 p-2`;
    logItem.innerHTML = `<small>${now}</small> ${message} <button type="button" class="btn-close p-1" data-bs-dismiss="alert"></button>`;
    logEl.prepend(logItem);
}

// Auto-focus
document.getElementById('batchScanner').focus();
</script>

<?php include '../includes/layout_end.php'; ?>