<?php
require_once '../config.php';
if (!isLoggedIn()) redirect('../login.php');
require_once '../includes/notifications.php';
$notifData = getNotifications($conn);
$notifItems = $notifData['items'];
$notifCount = $notifData['count'];
$statuses = ['Received','Preparing','Ready for Pickup','Out for Delivery','Completed'];
$filter = 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — Staff | Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <?php
  require_once '../includes/notifications.php';
  $staffNotifData = getNotifications($conn);
  $staffOrderNotifCount = count(array_filter($staffNotifData['items'], fn($n) => $n['type'] === 'order'));
  ?>
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-label">Staff Panel</div>
      <ul class="sidebar-nav">
        <li><a href="index.php?filter=active">
          📦 Active Orders
          <?php if ($staffOrderNotifCount > 0): ?>
          <span class="sidebar-badge"><?= $staffOrderNotifCount ?></span>
          <?php endif; ?>
        </a></li>
        <li><a href="index.php?filter=all">📋 All Orders</a></li>
      </ul>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-label">Alerts</div>
      <ul class="sidebar-nav">
        <li><a href="notifications.php" class="active">
          🔔 Notifications
          <?php if ($staffOrderNotifCount > 0): ?>
          <span class="sidebar-badge"><?= $staffOrderNotifCount > 9 ? '9+' : $staffOrderNotifCount ?></span>
          <?php endif; ?>
        </a></li>
      </ul>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-label">Account</div>
      <ul class="sidebar-nav">
        <li><a href="../logout.php">🚪 Logout</a></li>
      </ul>
    </div>
  </div>

  <div class="dashboard-main">
    <div class="dash-header">
      <h1 class="dash-title">🔔 Notifications</h1>
      <span class="text-dim" style="font-size:0.85rem;"><?= $notifCount ?> alert<?= $notifCount !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($notifItems)): ?>
    <div class="data-card" style="text-align:center;padding:4rem 2rem;">
      <div style="font-size:3rem;margin-bottom:1rem;">🔔</div>
      <h3 style="color:var(--white);margin-bottom:0.5rem;">All caught up!</h3>
      <p class="text-dim">No new orders awaiting attention.</p>
    </div>
    <?php else: ?>
    <div class="data-card">
      <div class="data-card-header">
        <h3 class="data-card-title">📦 Pending Orders <span class="badge badge-blue" style="margin-left:8px;"><?= $notifCount ?></span></h3>
        <a href="index.php" class="btn btn-ghost btn-sm">Go to Orders</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Received</th></tr></thead>
          <tbody>
            <?php foreach ($notifItems as $n): ?>
            <tr>
              <td><a href="index.php" style="color:var(--coral);font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars(explode(': ', $n['title'])[1] ?? '') ?></a></td>
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
  </div>
</div>
</div>
</body>
</html>
