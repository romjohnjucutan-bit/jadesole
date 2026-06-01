<?php
$page = basename($_SERVER['PHP_SELF']);

// Load notification count for sidebar badge
require_once dirname(__DIR__) . '/includes/notifications.php';
$notifData = getNotifications($conn);
$notifCount = $notifData['count'];

// Separate counts
$orderCount = 0;
$stockCount  = 0;
foreach ($notifData['items'] as $n) {
    if ($n['type'] === 'order') $orderCount++;
    if ($n['type'] === 'stock') $stockCount++;
}
?>
<div class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Admin Panel</div>
    <ul class="sidebar-nav">
      <li><a href="index.php" class="<?= $page==='index.php'?'active':'' ?>">📊 Overview</a></li>
    </ul>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Management</div>
    <ul class="sidebar-nav">
      <li><a href="products.php" class="<?= $page==='products.php'?'active':'' ?>">
        👟 Products
        <?php if ($stockCount > 0): ?>
        <span class="sidebar-badge sidebar-badge--warn"><?= $stockCount ?></span>
        <?php endif; ?>
      </a></li>
      <li><a href="orders.php" class="<?= $page==='orders.php'?'active':'' ?>">
        📦 Orders
        <?php if ($orderCount > 0): ?>
        <span class="sidebar-badge"><?= $orderCount ?></span>
        <?php endif; ?>
      </a></li>
      <li><a href="staff.php" class="<?= $page==='staff.php'?'active':'' ?>">👤 Staff</a></li>
    </ul>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Alerts</div>
    <ul class="sidebar-nav">
      <li>
        <a href="notifications.php" class="<?= $page==='notifications.php'?'active':'' ?>">
          🔔 Notifications
          <?php if ($notifCount > 0): ?>
          <span class="sidebar-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
          <?php endif; ?>
        </a>
      </li>
    </ul>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Store</div>
    <ul class="sidebar-nav">
      <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
  </div>
</div>
