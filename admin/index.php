<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$totalProducts = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$totalOrders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$totalStaff = $conn->query("SELECT COUNT(*) as c FROM staff")->fetch_assoc()['c'];
$revenue = $conn->query("SELECT SUM(total_amount) as s FROM orders WHERE status='Completed'")->fetch_assoc()['s'] ?? 0;

$recentOrders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <div class="dashboard-main">
    <div class="dash-header">
      <h1 class="dash-title">Dashboard</h1>
      <span class="text-dim" style="font-size:0.85rem;">Welcome back, <?= htmlspecialchars($_SESSION['staff_name']) ?> 👋</span>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?= $totalProducts ?></div>
        <div class="stat-label">Products</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $totalOrders ?></div>
        <div class="stat-label">Total Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $totalStaff ?></div>
        <div class="stat-label">Staff Members</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">₱<?= number_format($revenue, 0) ?></div>
        <div class="stat-label">Revenue</div>
      </div>
    </div>

    <div class="data-card">
      <div class="data-card-header">
        <h3 class="data-card-title">Recent Orders</h3>
        <a href="orders.php" class="btn btn-ghost btn-sm">View All</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php while ($o = $recentOrders->fetch_assoc()):
              $badgeCls = match($o['status']) {
                'Received' => 'badge-blue', 'Preparing' => 'badge-yellow',
                'Ready for Pickup','Out for Delivery' => 'badge-gold',
                'Completed' => 'badge-green', 'Cancelled' => 'badge-red', default => 'badge-gray'
              };
            ?>
            <tr>
              <td><span class="text-gold" style="font-family:monospace;"><?= htmlspecialchars($o['order_id']) ?></span></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td>₱<?= number_format($o['total_amount'],2) ?></td>
              <td style="text-transform:uppercase;font-size:0.8rem;"><?= htmlspecialchars($o['payment_method']) ?></td>
              <td><span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($o['status']) ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-dim);"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>
</body>
</html>
