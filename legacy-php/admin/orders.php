<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$filter = $_GET['filter'] ?? 'all';
$where = $filter !== 'all' ? "WHERE status='" . $conn->real_escape_string($filter) . "'" : '';
$orders = $conn->query("SELECT * FROM orders $where ORDER BY created_at DESC");

$statuses = ['Received','Preparing','Ready for Pickup','Out for Delivery','Completed','Cancelled'];

function badgeCls($s) {
  return match($s) {
    'Received'=>'badge-blue','Preparing'=>'badge-yellow',
    'Ready for Pickup','Out for Delivery'=>'badge-gold',
    'Completed'=>'badge-green','Cancelled'=>'badge-red',default=>'badge-gray'
  };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders — Admin | Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <div class="dashboard-main">
    <div class="dash-header">
      <h1 class="dash-title">All Orders</h1>
    </div>

    <!-- Filters -->
    <div class="products-filter mb-3">
      <a href="orders.php" class="filter-btn <?= $filter==='all'?'active':'' ?>">All</a>
      <?php foreach ($statuses as $s): ?>
      <a href="orders.php?filter=<?= urlencode($s) ?>" class="filter-btn <?= $filter===$s?'active':'' ?>"><?= $s ?></a>
      <?php endforeach; ?>
    </div>

    <div class="data-card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Order ID</th><th>Customer</th><th>Contact</th><th>Items</th><th>Total</th><th>Option</th><th>Payment</th><th>Status</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php while ($o = $orders->fetch_assoc()):
              $itemCount = $conn->query("SELECT SUM(quantity) as s FROM order_items WHERE order_id='".$o['order_id']."'")->fetch_assoc()['s'] ?? 0;
            ?>
            <tr>
              <td><span class="text-gold" style="font-family:monospace;font-size:0.82rem;"><?= htmlspecialchars($o['order_id']) ?></span></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td style="font-size:0.82rem;"><?= htmlspecialchars($o['contact_number']) ?></td>
              <td><?= $itemCount ?> pcs</td>
              <td class="text-gold">₱<?= number_format($o['total_amount'],2) ?></td>
              <td style="text-transform:capitalize;font-size:0.82rem;"><?= htmlspecialchars($o['delivery_option']) ?></td>
              <td style="text-transform:uppercase;font-size:0.8rem;"><?= htmlspecialchars($o['payment_method']) ?></td>
              <td><span class="badge <?= badgeCls($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
              <td style="font-size:0.8rem;color:var(--text-dim);"><?= date('M d, Y<br>h:i A', strtotime($o['created_at'])) ?></td>
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
