<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jade Sole — Step Into Style</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="page-wrap">

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-lines"></div>
    <div class="container hero-content">
      <div class="hero-eyebrow">
        <span class="hero-eyebrow-line"></span>
        Premium Footwear · Loon, Bohol
      </div>
      <h1 class="hero-title">
        Every Step<br>
        <em>Tells a Story.</em>
      </h1>
      <p class="hero-subtitle">
        Discover handpicked shoes crafted for comfort, built for style.
        From the streets of Bohol to wherever life takes you.
      </p>

      <div class="hero-info-strip">
        <div class="hero-info-item">
          <span class="icon">📍</span>
          <span><?= SITE_LOCATION ?></span>
        </div>
        <div class="hero-info-item">
          <span class="icon">📞</span>
          <span><?= SITE_CONTACT ?></span>
        </div>
        <div class="hero-info-item">
          <span class="icon">🕐</span>
          <span><?= SITE_HOURS ?></span>
        </div>
      </div>
    </div>
  </section>

  <!-- PROMOTIONS -->
  <section class="section" style="background: var(--off-black);">
    <div class="container">
      <span class="section-tag">Exclusive Offers</span>
      <h2 class="section-title">Why Shop With Us</h2>
      <p class="section-subtitle mb-4">Perks that make every purchase sweeter.</p>
      <div class="promos-grid">
        <div class="promo-card">
          <span class="promo-icon">📶</span>
          <div class="promo-title">Free WiFi In-Store</div>
          <p class="promo-desc">Stay connected while you browse our collection. Comfortable seating and fast internet — shop at your pace.</p>
        </div>
        <div class="promo-card">
          <span class="promo-icon">🏷️</span>
          <div class="promo-title">10% Off Orders Over ₱500</div>
          <p class="promo-desc">Mix and match your favourites. Any order totalling ₱500 and above automatically gets a 10% discount at checkout.</p>
        </div>
        <div class="promo-card">
          <span class="promo-icon">🚚</span>
          <div class="promo-title">Free Delivery Over ₱300</div>
          <p class="promo-desc">Orders above ₱300 qualify for free delivery to nearby areas in Loon, Bohol. Get your shoes right at your door.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURED PRODUCTS -->
  <section class="section">
    <div class="container">
      <span class="section-tag">Featured</span>
      <h2 class="section-title">Best Sellers</h2>
      <p class="section-subtitle mb-4">Our top picks — loved by our customers.</p>
      <div class="products-grid">
        <?php
        $sql = "SELECT p.*, c.name as cat_name FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_available = 1
                ORDER BY p.id ASC LIMIT 8";
        $res = $conn->query($sql);
        while ($p = $res->fetch_assoc()):
          $emoji = match((int)$p['category_id']) {
            1 => '👟', 2 => '👟', 3 => '👞', 4 => '🩴', 5 => '🥾', default => '👟'
          };
        ?>
        <div class="product-card">
          <div class="product-img-wrap">
            <?php if ($p['image'] && $p['image'] !== 'default.jpg' && file_exists('assets/images/'.$p['image'])): ?>
              <img src="assets/images/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <?php else: ?>
              <div class="product-placeholder"><?= $emoji ?></div>
            <?php endif; ?>
            <span class="product-badge"><?= htmlspecialchars($p['cat_name']) ?></span>
          </div>
          <div class="product-info">
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <p class="product-desc"><?= htmlspecialchars($p['description']) ?></p>
            <div class="product-footer">
              <div class="product-price">₱<?= number_format($p['price'], 2) ?></div>
              <a href="order.php" class="product-add-btn">Order →</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <div class="text-center mt-4">
        <a href="menu.php" class="btn btn-outline">View Full Collection →</a>
      </div>
    </div>
  </section>

  <!-- ABOUT STRIP -->
  <section class="section-sm" style="background: var(--off-black); border-top: 1px solid var(--border);">
    <div class="container">
      <div class="flex-between flex-wrap gap-2">
        <div>
          <span class="section-tag">About Jade Sole</span>
          <h2 style="font-size:1.6rem;">Quality You Can Feel With Every Step</h2>
          <p class="text-dim mt-2" style="max-width:520px;">
            Based in the heart of Loon, Bohol, Jade Sole brings premium footwear to your doorstep.
            We curate shoes that blend comfort, durability, and undeniable style — for every occasion.
          </p>
        </div>
        <a href="order.php" class="btn btn-gold" style="flex-shrink:0;">Order Now →</a>
      </div>
    </div>
  </section>

</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/react/main.js"></script>
</body>
</html>
