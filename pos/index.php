<?php
require_once '../includes/auth.php';
requireRole(['admin', 'staff', 'employee']);
include '../includes/functions.php';
include '../includes/layout.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Audio Elements (Hidden) -->
<audio id="scanSuccessSound" src="/zaina-beauty/assets/sounds/success.mp3" preload="auto"></audio>
<audio id="scanErrorSound" src="/zaina-beauty/assets/sounds/error.mp3" preload="auto"></audio>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Scanner + Client & Product Search -->
        <div class="col-md-5">
            <!-- 🔍 SCANNER MODAL BUTTON -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <button class="btn btn-success w-100" onclick="openMobileScanner()">
                        <i class="fas fa-barcode me-2"></i> Scan Product
                    </button>
                </div>
            </div>

            <!-- 👤 CLIENT SECTION -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">👤 Client</h5>
                    <button class="btn btn-sm btn-outline-info" onclick="openMobileClientScanner()">
                        <i class="fas fa-qrcode"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="clientPhone" class="form-control" placeholder="Enter phone">
                        <button class="btn btn-outline-primary" id="searchClientBtn">🔍</button>
                    </div>
                    <div id="clientResult"></div>
                </div>
            </div>

            <!-- 🛍️ Product Search -->
            <div class="card">
                <div class="card-header">
                    <h5>🛍️ Add Product/Service</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
                        <div id="productResults" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; display: none;"></div>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text">Qty</span>
                        <input type="number" id="productQty" class="form-control" value="1" min="1">
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>🛒 Cart</h5>
                    <div>
                        <button class="btn btn-sm btn-danger" id="clearCartBtn">Clear</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="cartItems">Your cart is empty</div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>Total: <span id="cartTotal">KES 0.00</span></h5>
                        <div>
                            <button class="btn btn-success" onclick="showCashPayment()">💵 Cash Payment</button>
                            <button class="btn btn-primary" id="checkoutBtn">💳 MPESA</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let products = <?= json_encode($pdo->query("SELECT id, name, selling_price, type, stock_qty, barcode FROM products ORDER BY name")->fetchAll()) ?>;

// Audio
const successSound = document.getElementById('scanSuccessSound');
const errorSound = document.getElementById('scanErrorSound');
function playSound(type){ type==='success'?successSound.play():errorSound.play(); }

// CART FUNCTIONS
function renderCart(){
    if(cart.length===0){
        document.getElementById('cartItems').innerHTML='Your cart is empty';
        document.getElementById('cartTotal').textContent='KES 0.00';
        return;
    }
    let html=`<table class='table'><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead><tbody>`;
    let total=0;
    cart.forEach((item,index)=>{
        const itemTotal=item.price*item.qty;
        total+=itemTotal;
        html+=`<tr>
            <td>${item.name}</td>
            <td><input type="number" class="form-control form-control-sm cart-qty" value="${item.qty}" data-index="${index}" min="1" style="width:70px;"></td>
            <td>${item.price.toFixed(2)}</td>
            <td>${itemTotal.toFixed(2)}</td>
            <td><button class="btn btn-sm btn-danger remove-btn" data-index="${index}">🗑️</button></td>
        </tr>`;
    });
    html+=`</tbody></table>`;
    document.getElementById('cartItems').innerHTML=html;
    document.getElementById('cartTotal').textContent='KES '+total.toFixed(2);

    document.querySelectorAll('.cart-qty').forEach(input=>{
        input.addEventListener('change',function(){
            const idx=parseInt(this.dataset.index);
            const val=parseInt(this.value)||1;
            if(cart[idx].type==='product' && val>cart[idx].stock){ Swal.fire('Low Stock!',`Only ${cart[idx].stock} available`,'warning'); this.value=cart[idx].qty; return;}
            cart[idx].qty=val;
            renderCart();
        });
    });

    document.querySelectorAll('.remove-btn').forEach(btn=>{
        btn.addEventListener('click',function(){
            const idx=parseInt(this.dataset.index);
            cart.splice(idx,1);
            renderCart();
        });
    });
}

function clearCart(){ cart=[]; localStorage.removeItem('pos_cart'); renderCart(); document.getElementById('clientPhone').value=''; document.getElementById('clientResult').innerHTML=''; }

// PROCESS BARCODE
async function processBarcode(barcode){
    const product=products.find(p=>p.barcode===barcode);
    if(product){
        const existingIndex=cart.findIndex(i=>i.id==product.id);
        if(existingIndex!==-1){ cart[existingIndex].qty+=1; }
        else{ cart.push({id:product.id,name:product.name,price:parseFloat(product.selling_price),qty:1,type:product.type,stock:product.stock_qty}); }
        renderCart(); playSound('success');
    }else{ Swal.fire('Not Found','Product not found: '+barcode,'warning'); playSound('error'); }
}

// MOBILE SCANNER
function openMobileScanner(){ window.open('/zaina-beauty/pos/mobile-scan.php?mode=product','scannerWindow','width=400,height=600'); }
function openMobileClientScanner(){ window.open('/zaina-beauty/pos/mobile-scan.php?mode=client','scannerWindow','width=400,height=600'); }

// HANDLE CLIENT PHONE
function processClientPhone(phone){
    if(phone){ document.getElementById('clientPhone').value=phone; searchClient(); }
}

// CLIENT SEARCH
function searchClient(){
    const phone=document.getElementById('clientPhone').value.trim();
    if(!phone) return;
    fetch('../ajax/get_clients.php?phone='+encodeURIComponent(phone))
        .then(res=>res.json())
        .then(clients=>{
            if(clients.length>0){
                const c=clients[0];
                document.getElementById('clientResult').innerHTML=`<div class="alert alert-info">✅ ${c.name} (${c.phone})<br>Loyalty: ${c.loyalty_points} pts</div>`;
            }else{ document.getElementById('clientResult').innerHTML=`<div class="alert alert-warning">Client not found. They'll be added on first purchase.</div>`;}
        });
}

// PAYMENT
function showCashPayment(){
    const phone=document.getElementById('clientPhone').value||'WALK-IN';
    let name='Cash Customer';
    const nameEl=document.querySelector('#clientResult .alert-info');
    if(nameEl){ const match=nameEl.textContent.match(/✅\s+(.*?)\s+\(/); if(match) name=match[1]; }
    localStorage.setItem('pos_cart',JSON.stringify(cart));
    localStorage.setItem('cash_customer_name',name);
    localStorage.setItem('cash_customer_phone',phone);
    window.location.href='/zaina-beauty/pos/cash_receipt.php';
}

// MPESA CHECKOUT
document.getElementById('checkoutBtn').addEventListener('click',checkout);
function checkout(){
    const phone=document.getElementById('clientPhone').value.trim();
    if(!phone){ Swal.fire('Missing Phone','Please enter client phone number','warning'); return;}
    const total=parseFloat(document.getElementById('cartTotal').textContent.replace(/[^0-9.]/g,''));
    if(isNaN(total)||total<=0){ Swal.fire('Empty Cart','Add items to cart first','info'); return;}
    Swal.fire({title:'Processing...',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
    fetch('/zaina-beauty/ajax/mpesa_payment.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({phone,amount:total,cart})})
    .then(res=>res.json()).then(data=>{
        Swal.close();
        if(data.success){ Swal.fire('📱 M-Pesa Prompt Sent!','Enter your PIN on your phone','info'); startPaymentPolling(data.sale_id); }
        else{ Swal.fire('Payment Failed',data.error||'Could not initiate payment','error'); }
    }).catch(err=>{ Swal.close(); Swal.fire('Network Error','Failed to connect to payment server','error'); });
}

function startPaymentPolling(saleId){
    const poll=setInterval(()=>{
        fetch(`/zaina-beauty/ajax/check_payment.php?sale_id=${saleId}`).then(r=>r.json()).then(data=>{
            if(data.paid){
                clearInterval(poll);
                Swal.fire('✅ Payment Confirmed!','Opening receipt...','success');
                window.open(`/zaina-beauty/pos/receipt.php?id=${saleId}`,'_blank');
                cart=[]; localStorage.removeItem("pos_cart"); renderCart(); document.getElementById('clientPhone').value=''; document.getElementById('clientResult').innerHTML='';
            }
        });
    },2000);
}

// PRODUCT SEARCH
document.getElementById('productSearch').addEventListener('input',function(){
    const term=this.value.toLowerCase().trim();
    const resultsDiv=document.getElementById('productResults');
    if(!term){ resultsDiv.style.display='none'; return;}
    const matches=products.filter(p=>p.name.toLowerCase().includes(term));
    resultsDiv.innerHTML=matches.length===0?'<div class="list-group-item">No products found</div>':
        matches.map(p=>`<button class="list-group-item list-group-item-action product-item" 
            data-id="${p.id}" data-name="${p.name}" data-price="${p.selling_price}" data-type="${p.type}" data-stock="${p.stock_qty}">${p.name} - KES ${p.selling_price}</button>`).join('');
    resultsDiv.style.display='block';
    document.querySelectorAll('.product-item').forEach(button=>{
        button.addEventListener('click',function(){
            const id=this.dataset.id, name=this.dataset.name, price=parseFloat(this.dataset.price), type=this.dataset.type, stock=parseInt(this.dataset.stock);
            const qty=parseInt(document.getElementById('productQty').value)||1;
            if(type==='product' && qty>stock){ Swal.fire('Low Stock!',`Only ${stock} available`,'warning'); return;}
            const existingIndex=cart.findIndex(i=>i.id==id);
            if(existingIndex!==-1) cart[existingIndex].qty+=qty;
            else cart.push({id,name,price,qty,type,stock});
            renderCart(); document.getElementById('productSearch').value=''; resultsDiv.style.display='none';
        });
    });
});

// SCANNER MESSAGE LISTENER
window.addEventListener("message",function(event){
    if(!event.data) return;
    if(event.data.scannedBarcode) processBarcode(event.data.scannedBarcode);
    if(event.data.scannedPhone) processClientPhone(event.data.scannedPhone);
});

// INITIALIZE
document.getElementById('searchClientBtn').addEventListener('click',searchClient);
document.getElementById('clearCartBtn').addEventListener('click',clearCart);
const savedCart=localStorage.getItem('pos_cart'); if(savedCart) cart=JSON.parse(savedCart); renderCart();

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/layout_end.php'; ?>
