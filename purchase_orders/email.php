<?php
require_once '../includes/auth.php';
requireRole(['admin']);
require_once '../includes/db.php';
require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';
// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$po_id = (int)($_POST['po_id'] ?? 0);
if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
    exit();
}

// Fetch PO and supplier email
$stmt = $pdo->prepare("
    SELECT 
        po.po_number, 
        po.supplier_name, 
        s.email AS supplier_email,
        u.email AS admin_email
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.id
    JOIN users u ON po.created_by = u.id
    WHERE po.id = ?
");
$stmt->execute([$po_id]);
$po = $stmt->fetch();

if (!$po) {
    echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
    exit();
}

if (empty($po['supplier_email']) || !filter_var($po['supplier_email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid supplier email not found. Please update supplier details.']);
    exit();
}

// === GENERATE PDF ATTACHMENT ===
require_once '../vendor/tcpdf/tcpdf.php';

// Fetch PO items
$stmt = $pdo->prepare("SELECT * FROM po_items WHERE po_id = ? ORDER BY id");
$stmt->execute([$po_id]);
$items = $stmt->fetchAll();

// Create PDF in memory
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Zaina Beauty Shop');
$pdf->SetAuthor('Zaina Beauty');
$pdf->SetTitle('Purchase Order ' . $po['po_number']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Build HTML content (same as pdf.php)
$logo_path = $_SERVER['DOCUMENT_ROOT'] . '/zaina-beauty/assets/img/logo.png';
$logo_html = file_exists($logo_path) ? '<img src="' . $logo_path . '" height="30"><br>' : '';

$html = '
<style>
    body { font-family: helvetica; font-size: 10pt; }
    .header { text-align: center; margin-bottom: 20px; }
    .po-header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; }
    th, td { border: 1px solid #000; padding: 5px; }
    th { background-color: #f0f0f0; }
    .text-right { text-align: right; }
</style>
<div class="header">
    ' . $logo_html . '
    <h2>ZAINA\'S BEAUTY SHOP</h2>
    <p>Nairobi, Kenya</p>
</div>
<div class="po-header">
    <h3>PURCHASE ORDER</h3>
    <p><strong>PO Number:</strong> ' . htmlspecialchars($po['po_number']) . '</p>
    <p><strong>Date:</strong> ' . date('F j, Y') . '</p>
    <p><strong>Supplier:</strong> ' . htmlspecialchars($po['supplier_name']) . '</p>
</div>
<table>
    <thead><tr>
        <th>Item</th><th>Qty</th><th>Unit Cost (KES)</th><th>Total (KES)</th>
    </tr></thead>
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

$html .= '</tbody><tfoot>
    <tr>
        <th colspan="3" class="text-right">GRAND TOTAL:</th>
        <th class="text-right">KES ' . number_format($grand_total, 2) . '</th>
    </tr>
</tfoot></table>
<p><em>This is a purchase order from Zaina\'s Beauty Shop.</em></p>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf_content = $pdf->Output('', 'S'); // Get PDF as string

// === SEND EMAIL WITH PHPMAILER ===
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-gmail@gmail.com';        // 👈 REPLACE WITH YOUR GMAIL
    $mail->Password   = 'your-16-digit-app-password';  // 👈 REPLACE WITH APP PASSWORD
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('your-gmail@gmail.com', "Zaina's Beauty Shop");
    $mail->addAddress($po['supplier_email'], $po['supplier_name']);
    $mail->addReplyTo($po['admin_email'] ?? 'your-gmail@gmail.com');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Purchase Order ' . $po['po_number'] . ' from Zaina\'s Beauty Shop';
    $mail->Body    = "
        <h3>Purchase Order from Zaina's Beauty Shop</h3>
        <p>Dear <strong>" . htmlspecialchars($po['supplier_name']) . "</strong>,</p>
        <p>Please find attached Purchase Order <strong>" . htmlspecialchars($po['po_number']) . "</strong>.</p>
        <p>We appreciate your prompt attention to this order.</p>
        <hr>
        <p><small>This email was sent automatically from Zaina's Beauty Shop Management System.</small></p>
    ";
    $mail->AltBody = "Purchase Order " . $po['po_number'] . " from Zaina's Beauty Shop. Please see attached PDF.";

    // Attach PDF
    $mail->addStringAttachment($pdf_content, 'PO_' . $po['po_number'] . '.pdf', 'base64', 'application/pdf');

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'PO emailed successfully to ' . $po['supplier_email']]);

} catch (Exception $e) {
    error_log("PHPMailer Error: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo]);
}
?>