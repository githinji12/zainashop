<?php
require_once '../includes/auth.php';
requireRole(['admin']);
require_once '../includes/db.php';
require_once '../vendor/tcpdf/tcpdf.php';

$po_id = (int)($_GET['id'] ?? 0);
if ($po_id <= 0) die('Invalid PO');

$stmt = $pdo->prepare("
    SELECT po.*, u.name AS created_by 
    FROM purchase_orders po
    LEFT JOIN users u ON po.created_by = u.id
    WHERE po.id = ?
");
$stmt->execute([$po_id]);
$po = $stmt->fetch();

if (!$po) die('PO not found.');

$stmt = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ? ORDER BY id");
$stmt->execute([$po_id]);
$items = $stmt->fetchAll();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document info
$pdf->SetCreator('Zaina Beauty Shop');
$pdf->SetAuthor('Zaina Beauty');
$pdf->SetTitle('Purchase Order ' . $po['po_number']);
$pdf->SetSubject('Purchase Order');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Logo (adjust path if needed)
$logo = '../assets/img/logo.png';
$logo_exists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/zaina-beauty/assets/img/logo.png');

// HTML Content
$html = '
<style>
    body { font-family: helvetica; }
    .header { text-align: center; margin-bottom: 20px; }
    .logo { height: 30px; }
    .po-header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; }
    th, td { border: 1px solid #000; padding: 6px; text-align: left; }
    th { background-color: #f0f0f0; }
    .text-right { text-align: right; }
    .footer { margin-top: 30px; font-size: 10px; color: #666; }
</style>

<div class="header">
    ' . ($logo_exists ? '<img src="' . $logo . '" class="logo"><br>' : '') . '
    <h2>ZAINA\'S BEAUTY SHOP</h2>
    <p>Nairobi, Kenya | Phone: +254 XXX XXX XXX</p>
</div>

<div class="po-header">
    <h3>PURCHASE ORDER</h3>
    <p><strong>PO Number:</strong> ' . htmlspecialchars($po['po_number']) . '</p>
    <p><strong>Date:</strong> ' . date('F j, Y', strtotime($po['created_at'])) . '</p>
    <p><strong>Supplier:</strong> ' . htmlspecialchars($po['supplier_name']) . '</p>
</div>

<table>
    <thead>
        <tr>
            <th>Item Description</th>
            <th>Quantity</th>
            <th>Unit Cost (KES)</th>
            <th>Total (KES)</th>
        </tr>
    </thead>
    <tbody>';

$grand_total = 0;
foreach ($items as $item) {
    $line_total = (float)$item['cost_price'] * (int)$item['quantity'];
    $grand_total += $line_total;
    $html .= '<tr>
        <td>' . htmlspecialchars($item['product_name']) . '</td>
        <td>' . (int)$item['quantity'] . '</td>
        <td>' . number_format((float)$item['cost_price'], 2) . '</td>
        <td class="text-right">' . number_format($line_total, 2) . '</td>
    </tr>';
}

$html .= '
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-right">GRAND TOTAL:</th>
            <th class="text-right">KES ' . number_format($grand_total, 2) . '</th>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <p><strong>Notes:</strong> Please quote PO number on all correspondence.</p>
    <p>Authorized Signature: _________________________</p>
    <p><em>This is a purchase order, not a tax invoice.</em></p>
</div>';

$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF (download)
$pdf->Output('PO_' . $po['po_number'] . '.pdf', 'D');
exit();
?>