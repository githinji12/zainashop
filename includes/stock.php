<?php
/**
 * Professional Stock Management Functions
 */

/**
 * Deduct stock for a sale
 * @param int $product_id
 * @param int $quantity
 * @param int $user_id
 * @param string $sale_id (reference)
 * @return bool
 */
function deductStockForSale($pdo, $product_id, $quantity, $user_id, $sale_id) {
    return logStockMovement($pdo, $product_id, 'out', $quantity, 'Sale', $sale_id, $user_id);
}

/**
 * Add stock (e.g., new purchase)
 * @param int $product_id
 * @param int $quantity
 * @param int $user_id
 * @param string $reason
 * @return bool
 */
function addStock($pdo, $product_id, $quantity, $user_id, $reason = 'Adjustment') {
    return logStockMovement($pdo, $product_id, 'in', $quantity, $reason, null, $user_id);
}

/**
 * Core: Log a stock movement & update product stock_qty
 */
function logStockMovement($pdo, $product_id, $movement_type, $quantity, $reason, $reference_id, $user_id) {
    // Only for products (not services)
    $stmt = $pdo->prepare("SELECT type FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $type = $stmt->fetch()['type'];
    
    if ($type !== 'product') return true; // Skip services

    try {
        $pdo->beginTransaction();

        // Update product stock
        $operator = ($movement_type === 'in') ? '+' : '-';
        $stmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty $operator ? WHERE id = ?");
        $stmt->execute([$quantity, $product_id]);

        // Log movement
        $stmt = $pdo->prepare("
            INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_id, user_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$product_id, $movement_type, $quantity, $reason, $reference_id, $user_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Stock movement failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current stock level
 */
function getCurrentStock($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT stock_qty FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch()['stock_qty'] ?? 0;
}

/**
 * Get stock movements for a product
 */
function getStockMovements($pdo, $product_id, $limit = 50) {
    // Validate and sanitize inputs
    $product_id = (int)$product_id;
    $limit = (int)$limit;
    
    // Safety checks
    if ($product_id <= 0) return [];
    if ($limit <= 0) $limit = 50;
    if ($limit > 1000) $limit = 1000; // Prevent excessive queries

    // Use direct integer for LIMIT (MariaDB requirement)
    $sql = "
        SELECT sm.*, u.name as user_name 
        FROM stock_movements sm
        JOIN users u ON sm.user_id = u.id
        WHERE sm.product_id = ?
        ORDER BY sm.created_at DESC
        LIMIT $limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

/**
 * Get low stock products (threshold = 5)
 */
function getLowStockProducts($pdo, $threshold = 5) {
    $threshold = (int)$threshold;
    if ($threshold < 0) $threshold = 0;
    
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE type = 'product' AND stock_qty <= ?
        ORDER BY stock_qty ASC
    ");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll();
}
?>