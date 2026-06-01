<?php
require_once '../config.php';
if (!isLoggedIn()) redirect('../login.php');

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $oid = sanitize($conn, $_POST['order_id']);
  $status = sanitize($conn, $_POST['status']);
  
  // Get current order status and delivery option
  $currentOrder = $conn->query("SELECT status, delivery_option FROM orders WHERE order_id='$oid'")->fetch_assoc();
  if ($currentOrder) {
    $allowedStatuses = getAllowedStatuses($currentOrder['status'], $currentOrder['delivery_option']);
    if (in_array($status, $allowedStatuses) || $status === $currentOrder['status']) {
      $conn->query("UPDATE orders SET status='$status' WHERE order_id='$oid'");
    }
  }
  redirect('index.php');
}

$filter = $_GET['filter'] ?? 'active';
if ($filter === 'active') {
  $orders = $conn->query("SELECT * FROM orders WHERE status NOT IN ('Completed','Cancelled') ORDER BY created_at DESC");
} elseif ($filter === 'all') {
  $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
} else {
  $sf = $conn->real_escape_string($filter);
  $orders = $conn->query("SELECT * FROM orders WHERE status='$sf' ORDER BY created_at DESC");
}

$statuses = ['Received','Preparing','Ready for Pickup','Out for Delivery','Completed'];

function badgeCls($s) {
  return match($s) {
    'Received'=>'badge-blue','Preparing'=>'badge-yellow',
    'Ready for Pickup','Out for Delivery'=>'badge-gold',
    'Completed'=>'badge-green','Cancelled'=>'badge-red',default=>'badge-gray'
  };
}

function getAllowedStatuses($currentStatus, $deliveryOption) {
  return match($currentStatus) {
    'Received' => ['Preparing'],
    'Preparing' => $deliveryOption === 'delivery' ? ['Out for Delivery'] : ['Ready for Pickup'],
    'Ready for Pickup' => ['Completed'],
    'Out for Delivery' => ['Completed'],
    'Completed', 'Cancelled' => [],
    default => []
  };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard — Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <!-- Staff Sidebar -->
  <?php
  require_once '../includes/notifications.php';
  $staffNotifData = getNotifications($conn);
  $staffOrderNotifCount = count(array_filter($staffNotifData['items'], fn($n) => $n['type'] === 'order'));
  ?>
  <div class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-label">Staff Panel</div>
      <ul class="sidebar-nav">
        <li><a href="index.php?filter=active" class="<?= $filter==='active'?'active':'' ?>">
          📦 Active Orders
          <?php if ($staffOrderNotifCount > 0): ?>
          <span class="sidebar-badge"><?= $staffOrderNotifCount ?></span>
          <?php endif; ?>
        </a></li>
        <li><a href="index.php?filter=all" class="<?= $filter==='all'?'active':'' ?>">📋 All Orders</a></li>
      </ul>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-label">By Status</div>
      <ul class="sidebar-nav">
        <?php foreach ($statuses as $s): ?>
        <li><a href="index.php?filter=<?= urlencode($s) ?>" class="<?= $filter===$s?'active':'' ?>"><?= $s ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-label">Alerts</div>
      <ul class="sidebar-nav">
        <li><a href="notifications.php">
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
      <h1 class="dash-title">Orders</h1>
      <span class="text-dim" style="font-size:0.85rem;">Staff: <?= htmlspecialchars($_SESSION['staff_name']) ?></span>
    </div>

    <div class="data-card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Order ID</th><th>Customer</th><th>Contact</th><th>Items</th><th>Total</th><th>Option</th><th>Status</th><th>Time</th><th>Update Status</th></tr>
          </thead>
          <tbody>
            <?php while ($o = $orders->fetch_assoc()):
              $items = $conn->query("SELECT * FROM order_items WHERE order_id='".$o['order_id']."'");
              $itemCount = 0;
              $itemList = [];
              while ($i = $items->fetch_assoc()) {
                $itemCount += $i['quantity'];
                $itemList[] = $i['product_name'].' ×'.$i['quantity'];
              }
            ?>
            <tr>
              <td><span class="text-gold" style="font-family:monospace;font-size:0.82rem;"><?= htmlspecialchars($o['order_id']) ?></span></td>
              <td>
                <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
                <?php if ($o['delivery_option'] === 'delivery' && $o['address']): ?>
                <br><span style="font-size:0.75rem;color:var(--text-dim);">📍 <?= htmlspecialchars(substr($o['address'],0,40)) ?></span>
                <?php endif; ?>
              </td>
              <td style="font-size:0.82rem;"><?= htmlspecialchars($o['contact_number']) ?></td>
              <td>
                <div style="font-size:0.78rem;color:var(--text-dim);"><?= implode(', ', $itemList) ?></div>
              </td>
              <td class="text-gold">₱<?= number_format($o['total_amount'],2) ?></td>
              <td>
                <span class="badge <?= $o['delivery_option']==='delivery'?'badge-blue':'badge-gray' ?>" style="font-size:0.7rem;">
                  <?= ucfirst($o['delivery_option']) ?>
                </span>
              </td>
              <td><span class="badge <?= badgeCls($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
              <td style="font-size:0.78rem;color:var(--text-dim);"><?= date('M d<br>h:i A', strtotime($o['created_at'])) ?></td>
              <td>
                <?php if (!in_array($o['status'], ['Completed','Cancelled'])): ?>
                <form method="post" style="display:flex;gap:4px;align-items:center;">
                  <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['order_id']) ?>">
                  <select name="status" class="form-control" style="padding:6px;font-size:0.78rem;min-width:140px;">
                    <option value="<?= htmlspecialchars($o['status']) ?>" selected><?= htmlspecialchars($o['status']) ?></option>
                    <?php foreach (getAllowedStatuses($o['status'], $o['delivery_option']) as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="update_status" class="btn btn-gold btn-sm">✓</button>
                </form>
                <?php else: ?>
                <span class="text-dim" style="font-size:0.82rem;">Finalized</span>
                <?php endif; ?>
              </td>
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
