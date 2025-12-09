<?php
require_once '../includes/db.php';
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$id = $_GET['id'] ?? 0;
if (!$id || !is_numeric($id)) {
    die('Invalid receipt ID');
}

$stmt = $pdo->prepare("
    SELECT s.*, c.name as client_name, c.phone as client_phone, u.name as cashier 
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ? AND s.payment_status = 'paid'
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('Receipt not found or payment not confirmed');
}

$stmt = $pdo->prepare("
    SELECT si.*, p.name as product_name 
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

function maskPhone($phone) {
    if (empty($phone)) return 'N/A';
    $digits = preg_replace('/\D/', '', $phone);
    return strlen($digits) >= 10 
        ? substr($digits, 0, 4) . '****' . substr($digits, -3)
        : '***********';
}

$vat_rate = 0.16;
$exclusive_amount = round($sale['total_amount'] / (1 + $vat_rate), 2);
$vat_amount = $sale['total_amount'] - $exclusive_amount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12.5px;
            line-height: 1.45;
            color: #000;
            background: #fff;
            padding: 15px 0;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .receipt {
            width: 100%;
            max-width: 340px;
            background: white;
            padding: 10px 12px;
            margin: 0 auto;
            border: 1px dashed #ccc;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .small { font-size: 11px; color: #444; }
        .large { font-size: 17px; }
        .xlarge { font-size: 19px; }

        .dashed { border-top: 1px dashed #000; margin: 13px 0; }
        .double { border-top: 3px double #000; margin: 15px 0; }

        /* PROFESSIONAL LOGO STYLING - NO OVERFLOW */
        .logo-container {
            text-align: center;
            margin: 8px 0 12px 0;
            padding: 8px 0;
        }

        .logo-container img {
            max-width: 210px;
            max-height: 80px;
            height: auto;
            width: auto;
            object-fit: contain;
            display: inline-block;
            image-rendering: crisp-edges;
        }

        /* Fallback if logo fails */
        .logo-fallback {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #000;
            margin: 10px 0;
        }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 0; font-size: 12.5px; }
        .item { text-align: left; }
        .qty { text-align: center; width: 18%; }
        .price, .amt { text-align: right; }

        .total-row {
            font-size: 19px !important;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 4px double #000;
            padding: 12px 0 !important;
        }

        .footer-note {
            margin-top: 22px;
            line-height: 1.6;
            font-size: 11.5px;
        }

        @media print {
            body { padding: 0; background: white; }
            .receipt {
                border: none;
                margin: 0;
                padding: 12px 8px;
                max-width: none;
                width: 80mm;
            }
            @page { size: 80mm auto; margin: 4mm; }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => window.close(), 1500);">

<div class="receipt">

    <!-- PROFESSIONAL LOGO AREA -->
    <div class="logo-container">
        <img src="/zaina-beauty/assets/img/logo.png" 
             alt="Zaina's Beauty Shop" 
             onerror="this.style.display='none'; document.querySelector('.logo-fallback').style.display='block';">
        <div class="logo-fallback" style="display:none;">ZAINA'S</div>
    </div>

    <!-- Shop Name & Details -->
    <div class="center bold xlarge" style="margin:5px 0 10px;">
        ZAINA'S BEAUTY SHOP
    </div>
    <div class="center small">
        Westlands • Nairobi, Kenya<br>
        Tel: +254 700 123 456<br>
        PIN: P051234567K • Reg: PVT-0012345
    </div>

    <div class="dashed"></div>

    <div class="center bold large">
        <?= $sale['payment_method'] === 'Cash' ? 'CASH RECEIPT' : 'M-PESA RECEIPT' ?>
    </div>
    <div class="center">
        Receipt No: <strong><?= str_pad($id, 6, '0', STR_PAD_LEFT) ?></strong><br>
        <?= date('d M Y • H:i', strtotime($sale['created_at'])) ?>
    </div>

    <div class="dashed"></div>

    <div style="font-size:12.5px; line-height:1.5;">
        Customer: <strong><?= htmlspecialchars($sale['client_name']) ?></strong><br>
        Phone: <span style="font-family:monospace; letter-spacing:1.5px;">
            <?= maskPhone($sale['client_phone']) ?></span><br>
        Cashier: <?= htmlspecialchars($sale['cashier']) ?>
    </div>

    <div class="double"></div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th class="item">Description</th>
                <th class="qty">Qty</th>
                <th class="price">Price</th>
                <th class="amt">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="item"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="qty"><?= $item['qty'] ?></td>
                <td class="price"><?= number_format($item['price'], 0) ?></td>
                <td class="amt bold"><?= number_format($item['price'] * $item['qty'], 0) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="double"></div>

    <!-- Payment Summary -->
    <table>
        <?php if ($sale['payment_method'] === 'Cash'): ?>
            <tr><td>Subtotal (excl. VAT)</td><td align="right">KES <?= number_format($exclusive_amount, 2) ?></td></tr>
            <tr><td>VAT 16%</td><td align="right">KES <?= number_format($vat_amount, 2) ?></td></tr>
            <tr class="total-row bold xlarge">
                <td>TOTAL PAID</td>
                <td align="right">KES <?= number_format($sale['total_amount'], 2) ?></td>
            </tr>
            <tr><td>Cash Tendered</td><td align="right">KES <?= number_format($sale['cash_received'] ?? $sale['total_amount'], 2) ?></td></tr>
            <tr style="color:#27ae60;"><td class="bold">Change Due</td><td align="right" class="bold">KES <?= number_format(($sale['cash_received'] ?? 0) - $sale['total_amount'], 2) ?></td></tr>
        <?php else: ?>
            <tr class="total-row bold xlarge">
                <td>TOTAL PAID (M-PESA)</td>
                <td align="right">KES <?= number_format($sale['total_amount'], 2) ?></td>
            </tr>
            <tr><td>Transaction ID</td><td align="right"><?= htmlspecialchars($sale['mpesa_receipt'] ?? 'N/A') ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="dashed"></div>

    <!-- Footer -->
    <div class="center footer-note">
        <p class="bold large">Thank you for your visit!</p>
        <p>Goods sold not returnable • Services non-refundable</p>
        <p class="small" style="margin-top:18px; color:#555;">
            <?= date('D, d M Y • H:i') ?>
        </p>
    </div>

</div>

</body>
</html>