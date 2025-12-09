<?php
function format_money($amount) {
    return 'KES ' . number_format($amount, 2);
}

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Log security-sensitive actions
 */
function logAudit($pdo, $action, $table, $record_id, $old_values, $ip, $user_agent) {
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs 
        (user_id, username, action, table_affected, record_id, old_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $action,
        $table,
        $record_id,
        is_array($old_values) ? json_encode($old_values) : $old_values,
        $ip,
        $user_agent
    ]);
}

/**
 * Send real-time security alerts to admins
 */
function sendSecurityAlert($action, $username, $ip, $record_id = null) {
    global $pdo;
    
    // Get all admin/staff contacts
    $stmt = $pdo->query("SELECT email, phone FROM users WHERE role IN ('admin', 'staff') AND email IS NOT NULL");
    $admins = $stmt->fetchAll();
    
    $subject = "🚨 SECURITY ALERT: Suspicious Activity in Zaina's";
    $message = "
        A potentially unauthorized action was detected:
        
        • Action: $action
        • User: $username (ID: " . ($_SESSION['user_id'] ?? 'N/A') . ")
        • IP Address: $ip
        • Record ID: " . ($record_id ? $record_id : 'N/A') . "
        • Time: " . date('Y-m-d H:i:s E') . "
        
        ⚠️ Review this immediately in Audit Logs: 
        http://localhost/zaina-beauty/reports/audit.php
        
        — Zaina's Beauty Shop Security System
    ";
    
    foreach ($admins as $admin) {
        if (!empty($admin['email'])) {
            // Basic email (use PHPMailer for production)
            mail($admin['email'], $subject, $message, "From: security@zainas.com\r\n");
        }
        
        // Optional: SMS via Twilio (requires API setup)
        // sendSMS($admin['phone'], substr(strip_tags($message), 0, 160));
    }
}
?>