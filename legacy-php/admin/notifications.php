<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');
require_once '../includes/notifications.php';
$notifData = getNotifications($conn);
$notifItems = $notifData['items'];
$notifCount = $notifData['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — Admin | Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <div class="dashboard-main">
    <div class="dash-header">
      <h1 class="dash-title">🔔 Notifications</h1>
      <span class="text-dim" style="font-size:0.85rem;"><?= $notifCount ?> alert<?= $notifCount !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($notifItems)): ?>
    <div class="data-card" style="text-align:center;padding:4rem 2rem;">
      <div style="font-size:3rem;margin-bottom:1rem;">🔔</div>
      <h3 style="color:var(--white);margin-bottom:0.5rem;">All caught up!</h3>
      <p class="text-dim">No new orders or low stock alerts at the moment.</p>
    </div>
    <?php else: ?>

    <?php
    $orders = array_filter($notifItems, fn($n) => $n['type'] === 'order');
    $stocks = array_filter($notifItems, fn($n) => $n['type'] === 'stock');
    ?>

    <?php if (!empty($orders)): ?>
    <div class="data-card" style="margin-bottom:1.5rem;">
      <div class="data-card-header">
        <h3 class="data-card-title">📦 New Orders <span class="badge badge-blue" style="margin-left:8px;"><?= count($orders) ?></span></h3>
        <a href="orders.php" class="btn btn-ghost btn-sm">Manage Orders</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Time</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $n): ?>
            <tr>
              <td><a href="orders.php" style="color:var(--coral);font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars(explode(': ', $n['title'])[1] ?? '') ?></a></td>
              <td><?= htmlspecialchars(explode(' — ₱', $n['message'])[0] ?? $n['message']) ?></td>
              <td class="text-gold">₱<?= htmlspecialchars(explode(' — ₱', $n['message'])[1] ?? '') ?></td>
              <td style="font-size:0.8rem;color:var(--text-dim);"><?= timeAgo($n['time']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($stocks)): ?>
    <div class="data-card">
      <div class="data-card-header">
        <h3 class="data-card-title">⚠️ Low Stock Alerts <span class="badge badge-red" style="margin-left:8px;"><?= count($stocks) ?></span></h3>
        <a href="products.php" class="btn btn-ghost btn-sm">Manage Products</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Product</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($stocks as $n): ?>
            <tr>
              <td><?= htmlspecialchars(str_replace('Low Stock: ', '', $n['title'])) ?></td>
              <td><span class="badge badge-red"><?= htmlspecialchars($n['message']) ?></span></td>
              <td><a href="products.php" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">Restock →</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</div>
</div>
</body>
</html>
