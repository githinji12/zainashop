<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff']);
$page_title = "Inventory Cycle Count";
include '../includes/layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-clipboard-list me-2"></i> Cycle Count</h4>
            <a href="index.php" class="btn btn-secondary btn-sm">← Back to Stock</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Scan barcodes to verify stock. Enter actual count when prompted.
            </div>
            
            <!-- Scan Input -->
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                <input type="text" 
                       id="cycleScanner" 
                       class="form-control form-control-lg" 
                       placeholder="Scan barcode to count..."
                       autocomplete="off"
                       autofocus>
            </div>
            
            <!-- Count Form (Hidden by default) -->
            <div id="countForm" class="card p-3 mb-3" style="display:none;">
                <h5 id="countProductName">Product Name</h5>
                <p>Expected: <span id="expectedCount">0</span></p>
                <div class="input-group mb-2">
                    <span class="input-group-text">Actual Count</span>
                    <input type="number" id="actualCount" class="form-control" min="0" value="0">
                </div>
                <button id="saveCount" class="btn btn-success w-100">✅ Save Count</button>
            </div>
            
            <!-- Results -->
            <div id="countResults" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
let currentProduct = null;

document.getElementById('cycleScanner').addEventListener('keydown', async function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = this.value.trim();
        this.value = '';
        
        if (barcode) {
            try {
                const response = await fetch('/zaina-beauty/pos/get-product-by-barcode.php?barcode=' + encodeURIComponent(barcode));
                const product = await response.json();
                
                if (product.id) {
                    currentProduct = product;
                    document.getElementById('countProductName').textContent = product.name;
                    document.getElementById('expectedCount').textContent = 
                        product.stock_qty !== undefined ? product.stock_qty : 'N/A';
                    document.getElementById('actualCount').value = 
                        product.stock_qty !== undefined ? product.stock_qty : 0;
                    document.getElementById('countForm').style.display = 'block';
                } else {
                    alert('Product not found: ' + barcode);
                }
            } catch (error) {
                console.error(error);
                alert('Scan error');
            }
        }
    }
});

document.getElementById('saveCount').addEventListener('click', async function() {
    const actual = parseInt(document.getElementById('actualCount').value) || 0;
    const expected = currentProduct.stock_qty || 0;
    
    try {
        const response = await fetch('/zaina-beauty/stock/save-cycle-count.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: currentProduct.id,
                actual_count: actual,
                expected_count: expected,
                user_id: <?= $_SESSION['user_id'] ?>
            })
        });
        
        const result = await response.json();
        if (result.success) {
            // Add to results
            const div = document.createElement('div');
            div.className = 'alert alert-success alert-dismissible fade show';
            div.innerHTML = `
                <strong>${currentProduct.name}</strong>: 
                Expected ${expected} → Actual ${actual} 
                ${actual !== expected ? '<span class="badge bg-warning">MISMATCH</span>' : '<span class="badge bg-success">MATCH</span>'}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.getElementById('countResults').prepend(div);
            
            // Hide form
            document.getElementById('countForm').style.display = 'none';
            currentProduct = null;
            document.getElementById('cycleScanner').focus();
        }
    } catch (error) {
        alert('Save failed');
    }
});

// Auto-focus
document.getElementById('cycleScanner').focus();
</script>

<?php include '../includes/layout_end.php'; ?>