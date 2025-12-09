// pos.js – Handle POS cart operations & payments
$(document).ready(function() {
  let cart = [];

  // Render products grid based on search
  $('#product-search').on('input', function() {
    const q = $(this).val().trim();
    $.getJSON(`${BASE_URL}controllers/POSController.php?action=search`, { q }, products => {
      $('#products-grid').empty();
      products.forEach(p => {
        const card = $(`
          <div class="col-md-4 mb-3">
            <div class="product-card" data-product='${JSON.stringify(p)}'>
              <img src="${BASE_URL}assets/uploads/${p.image||'products/default.jpg'}" class="product-image">
              <div class="product-info">
                <h6>${p.name}</h6>
                <p class="text-primary">${formatCurrency(p.selling_price)}</p>
              </div>
            </div>
          </div>`);
        $('#products-grid').append(card);
      });
    });
  });

  // Utility: format currency
  function formatCurrency(val) {
    return Number(val).toFixed(2) + ' ' + CURRENCY;
  }

  // Add product to cart
  $('#products-grid').on('click', '.product-card', function() {
    const p = $(this).data('product');
    const existing = cart.find(i=>i.product_id===p.id);
    if (existing) existing.quantity++;
    else cart.push({
      product_id: p.id,
      name: p.name,
      price: p.selling_price,
      quantity: 1,
      subtotal: p.selling_price
    });
    renderCart();
  });

  function renderCart() {
    let html = '';
    let subtotal = 0;
    cart.forEach((item, idx) => {
      item.subtotal = item.price * item.quantity;
      subtotal += item.subtotal;
      html += `
        <div class="cart-item d-flex justify-content-between align-items-center mb-2">
          <div>${item.name} x 
            <input type="number" class="cart-qty" data-idx="${idx}" value="${item.quantity}" min="1" style="width:50px;">
          </div>
          <div>${formatCurrency(item.subtotal)}</div>
        </div>`;
    });
    if (!cart.length) {
      html = `<div class="text-center text-muted py-5"><i class="fas fa-shopping-cart fa-3x"></i>
              <p>Cart is empty</p></div>`;
    }
    $('#cart-items').html(html);
    $('#cart-subtotal').text(formatCurrency(subtotal));
    updateTotals();
    $('#btn-payment').prop('disabled', cart.length===0);
  }

  // Change quantity
  $('#cart-items').on('change', '.cart-qty', function() {
    const idx = $(this).data('idx');
    cart[idx].quantity = Number($(this).val());
    renderCart();
  });

  // Discount change
  $('#discount-input').on('input', updateTotals);

  function updateTotals() {
    const subtotal = cart.reduce((sum,i)=>sum+i.subtotal, 0);
    const discount = Number($('#discount-input').val())||0;
    const taxRate = Number(TAX_RATE)||0;
    const tax = ((subtotal - discount) * taxRate / 100);
    const total = subtotal - discount + tax;
    $('#cart-tax').text(formatCurrency(tax));
    $('#cart-total').text(formatCurrency(total));
  }

  // Clear cart
  $('#btn-clear-cart').click(()=>{
    cart = [];
    $('#discount-input').val(0);
    renderCart();
  });

  // Open payment modal
  $('#btn-payment').click(()=>{
    $('#payment-total').text($('#cart-total').text());
    $('#bank-transfer-section').hide();
    $('#paymentModal').modal('show');
  });

  // Select payment method
  $('.payment-method').click(function() {
    const method = $(this).data('method');
    if (method === 'bank_transfer') {
      generateBankQR();
    } else {
      finalizeSale(method);
    }
  });

  // Generate Bank QR & show section
  function generateBankQR() {
    finalizeSale('bank_transfer', true);
  }

  // Confirm payment received
  $('#btn-confirm-payment').click(()=>{
    const sale_id = $('#btn-confirm-payment').data('sale');
    $.post(`${BASE_URL}controllers/POSController.php?action=confirm-payment`,
      { sale_id },
      resp=>{
        if (resp.success) location.href = `${BASE_URL}controllers/POSController.php?action=receipt&sale_id=${sale_id}`;
      }
    );
  });

  // Finalize sale (ajax)
  function finalizeSale(payment_method, preview=false) {
    const items = cart.map(i=>({
      product_id: i.product_id,
      quantity: i.quantity,
      price: i.price,
      subtotal: i.subtotal
    }));
    const payload = {
      items,
      subtotal: cart.reduce((s,i)=>s+i.subtotal,0),
      discount: Number($('#discount-input').val())||0,
      tax: Number($('#cart-tax').text()),
      total: parseFloat($('#cart-total').text()),
      payment_method,
      customer_id: $('#customer-select').val()
    };
    $.ajax({
      url: `${BASE_URL}controllers/POSController.php?action=process`,
      method: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json',
      success: function(resp) {
        if (resp.success) {
          if (payment_method==='bank_transfer') {
            // show QR
            $('#qr-code-container').html(`<img src="${resp.payment_data.qr_path}" style="width:200px;">`);
            $('#payment-instructions').html(`<pre>${resp.payment_data.payment_text}</pre>`);
            $('#btn-confirm-payment').data('sale', resp.sale_id);
            $('#bank-transfer-section').show();
          } else {
            // direct to receipt
            location.href = `${BASE_URL}controllers/POSController.php?action=receipt&sale_id=${resp.sale_id}`;
          }
        } else {
          alert(resp.message);
        }
      }
    });
  }
});