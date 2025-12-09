<?php
require_once '../includes/auth.php';
requireRole(['admin']);
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv_file'])) {
    header('Location: index.php?error=invalid_file');
    exit();
}

$file = $_FILES['csv_file']['tmp_name'];
if (!($handle = fopen($file, 'r'))) {
    header('Location: index.php?error=unreadable');
    exit();
}

$errors = [];
$success_count = 0;

// Skip header
$header = fgetcsv($handle);

while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) < 6) continue;
    
    $name = trim($data[0]);
    $category = trim($data[1]) ?: 'General';
    $contact_person = trim($data[2]);
    $phone = trim($data[3]);
    $email = trim($data[4]);
    $address = trim($data[5]);

    // Validate
    if (empty($name)) {
        $errors[] = "Missing name in row";
        continue;
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email: " . $email;
        continue;
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO suppliers (name, category, contact_person, phone, email, address)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        category = VALUES(category),
        contact_person = VALUES(contact_person),
        phone = VALUES(phone),
        email = VALUES(email),
        address = VALUES(address)
    ");
    $stmt->execute([$name, $category, $contact_person, $phone, $email, $address]);
    $success_count++;
}

fclose($handle);
unlink($file);

$message = "Imported {$success_count} suppliers.";
if (!empty($errors)) {
    $message .= " Errors: " . implode(', ', array_unique($errors));
}

header('Location: index.php?imported=' . urlencode($message));
exit();
?>