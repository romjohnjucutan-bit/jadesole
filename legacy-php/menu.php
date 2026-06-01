<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection — Jade Sole</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-wrap">
  <section class="section">
    <div class="container">
      <div class="flex-between mb-4 flex-wrap gap-2">
        <div>
          <span class="section-tag">Our Shoes</span>
          <h1 class="section-title">Full Collection</h1>
        </div>
        <a href="order.php" class="btn btn-gold">Order Online →</a>
      </div>


      <!-- Filters -->
      <div class="products-filter">
        <button class="filter-btn active" data-cat="all">All</button>
        <?php
        $cats = $conn->query("SELECT * FROM categories ORDER BY id");
        while ($c = $cats->fetch_assoc()):
        ?>
        <button class="filter-btn" data-cat="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></button>
        <?php endwhile; ?>
      </div>

      <!-- Products Grid -->
      <div class="products-grid" id="menuGrid">
        <?php
        $sql = "SELECT p.*, c.name as cat_name FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.category_id, p.name";
        $res = $conn->query($sql);
        while ($p = $res->fetch_assoc()):
          $available = $p['is_available'] && $p['stock'] > 0;
          $emoji = match((int)$p['category_id']) {
            1 => '👟', 2 => '👟', 3 => '👞', 4 => '🩴', 5 => '🥾', default => '👟'
          };
        ?>
        <div class="product-card" data-cat="<?= $p['category_id'] ?>">
          <div class="product-img-wrap">
            <?php if ($p['image'] && $p['image'] !== 'default.jpg' && file_exists('assets/images/'.$p['image'])): ?>
              <img src="assets/images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <?php else: ?>
              <div class="product-placeholder"><?= $emoji ?></div>
            <?php endif; ?>
            <?php if ($available): ?>
              <span class="product-badge">In Stock</span>
            <?php else: ?>
              <span class="product-badge unavailable">Unavailable</span>
            <?php endif; ?>
          </div>
          <div class="product-info">
            <div class="product-category"><?= htmlspecialchars($p['cat_name']) ?></div>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <p class="product-desc"><?= htmlspecialchars($p['description']) ?></p>
            <div class="product-footer">
              <div class="product-price">₱<?= number_format($p['price'], 2) ?></div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                <span style="font-size:0.7rem;color:var(--text-dim);">Stock: <?= $p['stock'] ?></span>
                <?php if ($available): ?>
                  <button type="button" class="product-add-btn add-with-size" data-pid="<?= $p['id'] ?>">Add to Order</button>
                <?php else: ?>
                  <button class="product-add-btn" disabled>Unavailable</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>
</div>

<?php include 'includes/footer.php'; ?>

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

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    const cat = this.dataset.cat;
    document.querySelectorAll('#menuGrid .product-card').forEach(card => {
      if (cat === 'all' || card.dataset.cat === cat) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  });
});

const sizeModal = document.getElementById('sizeModal');
const sizePid = document.getElementById('sizePid');
const closeSize = document.getElementById('closeSizeModal');
const cancelSize = document.getElementById('cancelSize');

document.querySelectorAll('.add-with-size').forEach(btn => {
  btn.addEventListener('click', () => {
    sizePid.value = btn.dataset.pid;
    sizeModal.classList.add('active');
  });
});
if (closeSize) closeSize.addEventListener('click', () => sizeModal.classList.remove('active'));
if (cancelSize) cancelSize.addEventListener('click', () => sizeModal.classList.remove('active'));
sizeModal.addEventListener('click', e => { if (e.target === sizeModal) sizeModal.classList.remove('active'); });
</script>
<script src="assets/js/react/main.js"></script>
</body>
</html>
