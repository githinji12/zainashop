// Add same logging pattern before deletion:
logAudit($pdo, 'delete_sale', 'sales', $id, $sale, ...);
sendSecurityAlert('Sale Deleted', $_SESSION['user_name'], $_SERVER['REMOTE_ADDR'], $id);