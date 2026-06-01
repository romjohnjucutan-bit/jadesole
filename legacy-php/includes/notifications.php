<?php
/**
 * Jade Sole - Notification System
 * Fetches notifications based on user role:
 * - Admin: new orders (Received status) + low stock (< 10)
 * - Staff: new orders (Received status) only
 */

function getNotifications($conn) {
    if (!isLoggedIn()) return ['items' => [], 'count' => 0];

    $notifications = [];

    // New orders — both admin and staff see these
    $newOrders = $conn->query("
        SELECT order_id, customer_name, total_amount, created_at
        FROM orders
        WHERE status = 'Received'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    while ($o = $newOrders->fetch_assoc()) {
        $notifications[] = [
            'type'    => 'order',
            'icon'    => '📦',
            'title'   => 'New Order: ' . htmlspecialchars($o['order_id']),
            'message' => htmlspecialchars($o['customer_name']) . ' — ₱' . number_format($o['total_amount'], 2),
            'time'    => $o['created_at'],
            'link'    => isAdmin() ? '../admin/orders.php' : 'index.php',
        ];
    }

    // Low stock — admin only
    if (isAdmin()) {
        $lowStock = $conn->query("
            SELECT id, name, stock
            FROM products
            WHERE stock < 10 AND is_available = 1
            ORDER BY stock ASC
            LIMIT 10
        ");
        while ($p = $lowStock->fetch_assoc()) {
            $notifications[] = [
                'type'    => 'stock',
                'icon'    => '⚠️',
                'title'   => 'Low Stock: ' . htmlspecialchars($p['name']),
                'message' => 'Only ' . $p['stock'] . ' unit' . ($p['stock'] == 1 ? '' : 's') . ' remaining',
                'time'    => null,
                'link'    => '../admin/products.php',
            ];
        }
    }

    // Sort: orders first (by time desc), then stock alerts
    usort($notifications, function($a, $b) {
        if ($a['type'] === $b['type']) {
            if ($a['time'] && $b['time']) return strtotime($b['time']) - strtotime($a['time']);
            return 0;
        }
        return $a['type'] === 'order' ? -1 : 1;
    });

    return ['items' => $notifications, 'count' => count($notifications)];
}

function timeAgo($datetime) {
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}
