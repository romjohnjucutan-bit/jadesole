<?php
require_once 'config.php';

// Handle cart in session
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Add to cart via size selection (POST) or simple GET (legacy)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_with_size'])) {
  $pid = (int)$_POST['pid'];
  $size = trim($_POST['size']);
  $row = $conn->query("SELECT * FROM products WHERE id=$pid AND is_available=1 AND stock>0")->fetch_assoc();
  if ($row) {
    if (isset($_SESSION['cart'][$pid])) {
      if ($_SESSION['cart'][$pid]['qty'] < $row['stock']) {
        $_SESSION['cart'][$pid]['qty']++;
      }
    } else {
      $_SESSION['cart'][$pid] = [
        'id' => $row['id'], 'name' => $row['name'],
        'price' => $row['price'], 'qty' => 1,
        'stock' => $row['stock'], 'image' => $row['image'], 'size' => $size
      ];
    }
  }
  header("Location: order.php");
  exit();
}

// Backwards-compatible GET add (no size)
if (isset($_GET['add'])) {
  $pid = (int)$_GET['add'];
  $row = $conn->query("SELECT * FROM products WHERE id=$pid AND is_available=1 AND stock>0")->fetch_assoc();
  if ($row) {
    if (isset($_SESSION['cart'][$pid])) {
      if ($_SESSION['cart'][$pid]['qty'] < $row['stock']) {
        $_SESSION['cart'][$pid]['qty']++;
      }
    } else {
      $_SESSION['cart'][$pid] = [
        'id' => $row['id'], 'name' => $row['name'],
        'price' => $row['price'], 'qty' => 1,
        'stock' => $row['stock'], 'image' => $row['image']
      ];
    }
  }
  header("Location: order.php");
  exit();
}

// Update qty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $pid = (int)$_POST['pid'];
  if ($_POST['action'] === 'update' && isset($_SESSION['cart'][$pid])) {
    $qty = (int)$_POST['qty'];
    $stock = $_SESSION['cart'][$pid]['stock'];
    if ($qty <= 0) unset($_SESSION['cart'][$pid]);
    else $_SESSION['cart'][$pid]['qty'] = min($qty, $stock);
  }
  if ($_POST['action'] === 'remove') unset($_SESSION['cart'][$pid]);
  header("Location: order.php");
  exit();
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) $subtotal += $item['price'] * $item['qty'];
$discount = ($subtotal >= DISCOUNT_THRESHOLD) ? round($subtotal * DISCOUNT_PERCENT / 100, 2) : 0;
$total = $subtotal - $discount;
$cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));

// Place Order
$order_success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  if (!empty($_SESSION['cart'])) {
    $customer_name = sanitize($conn, $_POST['customer_name']);
    $contact = sanitize($conn, $_POST['contact_number']);
    $delivery = sanitize($conn, $_POST['delivery_option']);
    $address = ($delivery === 'delivery') ? sanitize($conn, $_POST['address']) : '';
    $payment = sanitize($conn, $_POST['payment_method']);
    $oid = generateOrderId();

    $sql = "INSERT INTO orders (order_id, customer_name, contact_number, delivery_option, address, payment_method, total_amount, discount, status)
            VALUES ('$oid','$customer_name','$contact','$delivery','$address','$payment',$total,$discount,'Received')";

    if ($conn->query($sql)) {
      foreach ($_SESSION['cart'] as $item) {
        $pid = (int)$item['id'];
        $qty = (int)$item['qty'];
        $pname = sanitize($conn, $item['name']);
        $price = (float)$item['price'];
        $sub = $price * $qty;
        $pname_with_size = $pname;
        if (!empty($item['size'])) {
          $pname_with_size .= " (Size " . sanitize($conn, $item['size']) . ")";
        }
        $pname_with_size = sanitize($conn, $pname_with_size);
        $conn->query("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                      VALUES ('$oid',$pid,'$pname_with_size',$price,$qty,$sub)");
        $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $pid AND stock >= $qty");
      }
      $_SESSION['cart'] = [];
      $order_success = $oid;
    }
  }
}

// Get products
$products_sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.category_id, p.name";
$products_res = $conn->query($products_sql);
$products_by_cat = [];
while ($p = $products_res->fetch_assoc()) {
  $products_by_cat[$p['cat_name']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order — Jade Sole</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-wrap">
<div class="container section">

  <?php if ($order_success): ?>
  <!-- SUCCESS -->
  <div class="modal-overlay active" id="successModal">
    <div class="modal">
      <div class="modal-body">
        <div class="order-confirm">
          <div class="order-confirm-icon">✅</div>
          <h2 style="font-size:1.5rem;">Order Placed!</h2>
          <p class="text-dim mt-2">Your order has been received. Use the ID below to track your order.</p>
          <div class="order-confirm-id"><?= htmlspecialchars($order_success) ?></div>
          <p class="text-dim" style="font-size:0.85rem;">Save this Order ID — you'll need it to track your order.</p>
          <div class="flex-center gap-2 mt-4" style="flex-wrap:wrap;">
            <a href="track.php?id=<?= urlencode($order_success) ?>" class="btn btn-gold">Track Order →</a>
            <a href="index.php" class="btn btn-ghost">Back to Home</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="flex-between mb-4 flex-wrap gap-2">
    <div>
      <span class="section-tag">Place an Order</span>
      <h1 class="section-title">Build Your Cart</h1>
    </div>
    <a href="menu.php" class="btn btn-ghost">← Browse Collection</a>
  </div>

  <div class="order-layout">

    <!-- LEFT: Products -->
    <div>
      <?php foreach ($products_by_cat as $catName => $items): ?>
      <div class="data-card mb-3">
        <div class="data-card-header">
          <h3 class="data-card-title"><?= htmlspecialchars($catName) ?></h3>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $p):
                $avail = $p['is_available'] && $p['stock'] > 0;
                $emoji = match((int)$p['category_id']) { 1=>'👟',2=>'👟',3=>'👞',4=>'🩴',5=>'🥾',default=>'👟'};
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:44px;height:44px;background:var(--dark);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
                      <?php if ($p['image'] && $p['image'] !== 'default.jpg' && file_exists('assets/images/'.$p['image'])): ?>
                        <img src="assets/images/<?= htmlspecialchars($p['image']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                      <?php else: echo $emoji; endif; ?>
                    </div>
                    <div>
                      <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($p['name']) ?></div>
                      <div style="font-size:0.76rem;color:var(--text-dim);"><?= htmlspecialchars(substr($p['description'],0,50)) ?>...</div>
                    </div>
                  </div>
                </td>
                <td class="text-gold" style="font-family:'Playfair Display',serif;font-weight:700;">₱<?= number_format($p['price'],2) ?></td>
                <td><?= $p['stock'] ?></td>
                <td>
                  <?php if ($avail): ?>
                    <span class="badge badge-green">Available</span>
                  <?php else: ?>
                    <span class="badge badge-red">Unavailable</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($avail): ?>
                    <button type="button" class="btn btn-gold btn-sm add-with-size" data-pid="<?= $p['id'] ?>">+ Add</button>
                  <?php else: ?>
                    <button class="btn btn-ghost btn-sm" disabled>N/A</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- RIGHT: Cart -->
    <div class="cart-panel">
      <div class="cart-header">
        <h3>My Cart</h3>
        <span class="cart-count"><?= $cart_count ?> items</span>
      </div>

      <?php if (empty($_SESSION['cart'])): ?>
      <div class="cart-empty">
        <div class="cart-empty-icon">🛒</div>
        <p>Your cart is empty.</p>
        <p class="text-dim" style="font-size:0.8rem;margin-top:0.3rem;">Add products from the list.</p>
      </div>
      <?php else: ?>
      <div class="cart-items">
        <?php foreach ($_SESSION['cart'] as $pid => $item):
          $emoji = '👟';
        ?>
        <div class="cart-item">
          <div class="cart-item-img">
            <?php if ($item['image'] && $item['image'] !== 'default.jpg' && file_exists('assets/images/'.$item['image'])): ?>
              <img src="assets/images/<?= htmlspecialchars($item['image']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
            <?php else: echo $emoji; endif; ?>
          </div>
          <div class="cart-item-info">
            <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
            <?php if (!empty($item['size'])): ?>
              <div class="cart-item-size">Size <?= htmlspecialchars($item['size']) ?></div>
            <?php endif; ?>
            <div class="cart-item-price">₱<?= number_format($item['price'],2) ?> × <?= $item['qty'] ?> = ₱<?= number_format($item['price']*$item['qty'],2) ?></div>
          </div>
          <div class="cart-qty-ctrl">
            <form method="post" action="order.php" style="display:contents;">
              <input type="hidden" name="pid" value="<?= $pid ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="qty" value="<?= $item['qty']-1 ?>">
              <button type="submit" class="qty-btn">−</button>
            </form>
            <span class="qty-num"><?= $item['qty'] ?></span>
            <form method="post" action="order.php" style="display:contents;">
              <input type="hidden" name="pid" value="<?= $pid ?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="qty" value="<?= $item['qty']+1 ?>">
              <button type="submit" class="qty-btn" <?= $item['qty']>=$item['stock']?'disabled':'' ?>>+</button>
            </form>
          </div>
          <form method="post" action="order.php">
            <input type="hidden" name="pid" value="<?= $pid ?>">
            <input type="hidden" name="action" value="remove">
            <button type="submit" class="cart-remove" title="Remove">✕</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Summary -->
      <div class="cart-summary">
        <div class="cart-row"><span>Subtotal</span><span>₱<?= number_format($subtotal,2) ?></span></div>
        <?php if ($discount > 0): ?>
        <div class="cart-row discount"><span>Discount (<?= DISCOUNT_PERCENT ?>%)</span><span>−₱<?= number_format($discount,2) ?></span></div>
        <?php endif; ?>
        <div class="cart-row total"><span>Total</span><span>₱<?= number_format($total,2) ?></span></div>

        <?php if ($subtotal >= FREE_DELIVERY_THRESHOLD): ?>
        <div class="alert alert-success" style="margin-top:0.8rem;font-size:0.78rem;">🚚 Free delivery eligible!</div>
        <?php endif; ?>

        <!-- Payment Method -->
        <div class="form-group mt-3">
          <label class="form-label">Payment Method</label>
          <div class="payment-methods" id="paymentMethodDisplay">
            <label class="payment-option selected" id="lbl_cod">
              <input type="radio" name="payment_display" value="cod" checked> 💵 COD
            </label>
            <label class="payment-option" id="lbl_cash">
              <input type="radio" name="payment_display" value="cash"> 💳 Cash
            </label>
          </div>
        </div>

        <button class="btn btn-gold w-full mt-2" id="placeOrderBtn" style="justify-content:center;">
          Place Order →
        </button>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- end order-layout -->
</div>
</div>

<!-- SIZE SELECTION MODAL -->
<div class="modal-overlay" id="sizeModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Select Shoe Size</h3>
      <button class="modal-close" id="closeSizeModal">✕</button>
    </div>
    <form method="post" action="order.php" id="sizeForm">
      <input type="hidden" name="add_with_size" value="1">
      <input type="hidden" name="pid" id="sizePid" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Size</label>
          <select name="size" id="sizeSelect" class="form-control" required>
            <option value="">-- Choose size --</option>
            <?php for ($s = 5; $s <= 13; $s += 0.5): ?>
              <option value="<?= $s ?>"><?= $s ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" id="cancelSize">Cancel</button>
        <button type="submit" class="btn btn-gold">Add to Cart</button>
      </div>
    </form>
  </div>
</div>

<!-- PLACE ORDER MODAL -->
<div class="modal-overlay" id="orderModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Complete Your Order</h3>
      <button class="modal-close" id="closeModal">✕</button>
    </div>
    <form method="post" action="order.php">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="customer_name" class="form-control" placeholder="e.g. Juan dela Cruz" required>
        </div>
        <div class="form-group">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact_number" class="form-control" placeholder="e.g. 09XXXXXXXXX" required>
        </div>
        <div class="form-group">
          <label class="form-label">Fulfillment Option</label>
          <select name="delivery_option" class="form-control" id="deliverySelect">
            <option value="pickup">🏪 Store Pickup</option>
            <option value="delivery">🚚 Delivery</option>
          </select>
        </div>
        <div class="form-group" id="addressGroup" style="display:none;">
          <label class="form-label">Delivery Address</label>
          <input type="text" name="address" class="form-control" placeholder="Enter your delivery address">
        </div>
        <input type="hidden" name="payment_method" id="paymentHidden" value="cod">
        <div class="alert alert-info" style="font-size:0.82rem;">
          💰 <strong>Total: ₱<?= number_format($total,2) ?></strong>
          <?php if ($discount > 0): ?> (includes ₱<?= number_format($discount,2) ?> discount)<?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" id="cancelModal">Cancel</button>
        <button type="submit" name="place_order" class="btn btn-gold">Confirm Order →</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Payment method sync
document.querySelectorAll('#paymentMethodDisplay .payment-option').forEach(opt => {
  opt.addEventListener('click', function() {
    document.querySelectorAll('#paymentMethodDisplay .payment-option').forEach(o => o.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('paymentHidden').value = this.querySelector('input').value;
  });
});

// Modal
const modal = document.getElementById('orderModal');
const placeBtn = document.getElementById('placeOrderBtn');
const closeBtn = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelModal');

if (placeBtn) {
  placeBtn.addEventListener('click', () => modal.classList.add('active'));
}
if (closeBtn) closeBtn.addEventListener('click', () => modal.classList.remove('active'));
if (cancelBtn) cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('active'); });

// Delivery address toggle
const deliverySelect = document.getElementById('deliverySelect');
const addressGroup = document.getElementById('addressGroup');
if (deliverySelect) {
  deliverySelect.addEventListener('change', function() {
    addressGroup.style.display = this.value === 'delivery' ? '' : 'none';
    addressGroup.querySelector('input').required = this.value === 'delivery';
  });
}

// Size selection modal for adding shoes
const sizeModal = document.getElementById('sizeModal');
const sizeForm = document.getElementById('sizeForm');
const sizePid = document.getElementById('sizePid');
const closeSize = document.getElementById('closeSizeModal');
const cancelSize = document.getElementById('cancelSize');
document.querySelectorAll('.add-with-size').forEach(btn => {
  btn.addEventListener('click', () => {
    const pid = btn.getAttribute('data-pid');
    sizePid.value = pid;
    sizeModal.classList.add('active');
  });
});
if (closeSize) closeSize.addEventListener('click', () => sizeModal.classList.remove('active'));
if (cancelSize) cancelSize.addEventListener('click', () => sizeModal.classList.remove('active'));
sizeModal.addEventListener('click', e => { if (e.target === sizeModal) sizeModal.classList.remove('active'); });
</script>
</body>
</html>
