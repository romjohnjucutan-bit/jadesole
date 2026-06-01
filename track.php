<?php
require_once 'config.php';

$order = null;
$items = [];
$error = null;
$success = null;
$statuses = ['Received','Preparing','Ready for Pickup','Out for Delivery','Completed','Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
  $oid = sanitize($conn, $_POST['order_id'] ?? $_GET['id'] ?? '');
  if ($oid) {
    $res = $conn->query("SELECT * FROM orders WHERE order_id='$oid'");
    if ($res && $res->num_rows > 0) {
      $order = $res->fetch_assoc();
      $iRes = $conn->query("SELECT * FROM order_items WHERE order_id='$oid'");
      while ($i = $iRes->fetch_assoc()) $items[] = $i;
    } else {
      $error = "Order not found. Please check your Order ID and try again.";
    }
  }

  // Handle order cancellation
  if (isset($_POST['cancel_order']) && $order) {
    if ($order['status'] === 'Received') {
      $conn->query("UPDATE orders SET status='Cancelled' WHERE order_id='$oid'");
      $order['status'] = 'Cancelled'; // Update local copy
      $success = "Order has been cancelled successfully.";
    } else {
      $error = "Order cannot be cancelled at this stage.";
    }
  }
}

function statusBadge($status) {
  return match($status) {
    'Received' => 'badge-blue',
    'Preparing' => 'badge-yellow',
    'Ready for Pickup' => 'badge-gold',
    'Out for Delivery' => 'badge-gold',
    'Completed' => 'badge-green',
    'Cancelled' => 'badge-red',
    default => 'badge-gray'
  };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Order — Jade Sole</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-wrap">
<section class="section">
<div class="container">
  <span class="section-tag">Order Tracking</span>
  <h1 class="section-title mb-4">Where's My Order?</h1>

  <!-- Search Form -->
  <div class="track-card mb-4" style="max-width:600px;">
    <form method="post" action="track.php">
      <div class="form-group">
        <label class="form-label">Enter Your Order ID</label>
        <div style="display:flex;gap:0.5rem;">
          <input type="text" name="order_id" class="form-control"
                 placeholder="e.g. JS-XXXXXXXX"
                 value="<?= htmlspecialchars($_POST['order_id'] ?? $_GET['id'] ?? '') ?>"
                 style="border-radius:4px 0 0 4px;" required>
          <button type="submit" class="btn btn-gold" style="border-radius:0 4px 4px 0;white-space:nowrap;">Track →</button>
        </div>
      </div>
    </form>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($order): ?>
  <div class="track-card">
    <!-- Order ID & Status -->
    <div class="track-id-display">
      <div>
        <div class="track-id-label">Order ID</div>
        <div class="track-id-value"><?= htmlspecialchars($order['order_id']) ?></div>
      </div>
      <span class="badge <?= statusBadge($order['status']) ?>" style="font-size:0.8rem;padding:6px 14px;">
        <?= htmlspecialchars($order['status']) ?>
      </span>
    </div>

    <!-- Customer Info -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:2rem;">
      <div>
        <div class="form-label">Customer</div>
        <div><?= htmlspecialchars($order['customer_name']) ?></div>
      </div>
      <div>
        <div class="form-label">Contact</div>
        <div><?= htmlspecialchars($order['contact_number']) ?></div>
      </div>
      <div>
        <div class="form-label">Option</div>
        <div style="text-transform:capitalize;"><?= htmlspecialchars($order['delivery_option']) ?></div>
      </div>
      <div>
        <div class="form-label">Payment</div>
        <div style="text-transform:uppercase;"><?= htmlspecialchars($order['payment_method']) ?></div>
      </div>
      <?php if ($order['address']): ?>
      <div style="grid-column:1/-1;">
        <div class="form-label">Delivery Address</div>
        <div><?= htmlspecialchars($order['address']) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Cancellation Option -->
    <?php if ($order['status'] === 'Received'): ?>
    <div class="mb-3">
      <form method="post" action="track.php" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
        <button type="submit" name="cancel_order" class="btn btn-danger" style="padding:8px 16px;border-radius:4px;">
          Cancel Order
        </button>
        <span style="font-size:0.8rem;color:var(--text-dim);margin-left:1rem;">You can cancel this order since it hasn't been prepared yet.</span>
      </form>
    </div>
    <?php elseif ($order['status'] === 'Preparing'): ?>
    <div class="mb-3">
      <div class="alert alert-info" style="background:var(--blue-light);color:var(--blue);border:1px solid var(--blue);padding:12px;border-radius:4px;">
        This order is currently being prepared and cannot be cancelled.
      </div>
    </div>
    <?php endif; ?>

    <!-- Items -->
    <div class="data-card mb-3">
      <div class="data-card-header"><h3 class="data-card-title">Items Ordered</h3></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['product_name']) ?></td>
              <td><?= $item['quantity'] ?></td>
              <td>₱<?= number_format($item['price'],2) ?></td>
              <td>₱<?= number_format($item['subtotal'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($order['discount'] > 0): ?>
            <tr><td colspan="3" style="text-align:right;color:var(--green);">Discount</td><td style="color:var(--green);">−₱<?= number_format($order['discount'],2) ?></td></tr>
            <?php endif; ?>
            <tr>
              <td colspan="3" style="text-align:right;font-weight:700;color:var(--white);">Total</td>
              <td style="font-weight:700;color:var(--gold);font-family:'Playfair Display',serif;">₱<?= number_format($order['total_amount'],2) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Timeline -->
    <h3 style="font-size:1rem;margin-bottom:1.5rem;">Order Progress</h3>
    <?php
    $isCancelled = $order['status'] === 'Cancelled';
    $timeline = $order['delivery_option'] === 'delivery'
      ? ['Received','Preparing','Out for Delivery','Completed']
      : ['Received','Preparing','Ready for Pickup','Completed'];
    if ($isCancelled) $timeline[] = 'Cancelled';
    $currentIdx = array_search($order['status'], $timeline);
    ?>
    <div class="order-status-timeline">
      <?php foreach ($timeline as $idx => $step):
        $done = !$isCancelled && $idx < $currentIdx;
        $current = $idx === $currentIdx;
        $cls = $done ? 'done' : ($current ? 'current' : '');
      ?>
      <div class="status-step <?= $cls ?>">
        <div class="status-dot"></div>
        <div>
          <div class="status-step-label"><?= htmlspecialchars($step) ?></div>
          <?php if ($current && !$isCancelled): ?>
          <div style="font-size:0.78rem;color:var(--text-dim);margin-top:2px;">Current status</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-4 text-dim" style="font-size:0.8rem;">
      Ordered: <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</section>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
