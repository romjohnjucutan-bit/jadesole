<?php
$current = basename($_SERVER['PHP_SELF']);
$dir = basename(dirname($_SERVER['PHP_SELF']));

// Load notifications for logged-in users
$notifData = ['items' => [], 'count' => 0];
if (isLoggedIn()) {
    require_once dirname(__DIR__) . '/includes/notifications.php';
    $notifData = getNotifications($conn);
}
$notifCount = $notifData['count'];
$notifItems = $notifData['items'];
?>
<nav class="navbar">
  <a href="<?= ($dir === 'admin' || $dir === 'staff') ? '../' : '' ?>index.php" class="nav-brand">
    <div class="nav-logo-icon">👟</div>
    <div class="nav-brand-text">Erl Jade <span>Sole</span></div>
  </a>
  <ul class="nav-links">
    <?php if (!in_array($dir, ['admin', 'staff'])): ?>
      <li><a href="<?= ($dir === 'admin' || $dir === 'staff') ? '../' : '' ?>index.php" class="<?= $current==='index.php' && !in_array($dir,['admin','staff'])?'active':'' ?>">Home</a></li>
      <li><a href="<?= ($dir === 'admin' || $dir === 'staff') ? '../' : '' ?>menu.php" class="<?= $current==='menu.php'?'active':'' ?>">Collection</a></li>
      <li><a href="<?= ($dir === 'admin' || $dir === 'staff') ? '../' : '' ?>track.php" class="<?= $current==='track.php'?'active':'' ?>">Track Order</a></li>
    <?php endif; ?>
    <?php if (isLoggedIn()): ?>
      <li><a href="<?= isAdmin() ? (($dir==='admin'?'':'admin/')).'index.php' : (($dir==='staff'?'':'staff/')).'index.php' ?>">Dashboard</a></li>
      <?php if (!in_array($dir, ['admin', 'staff'])): ?>
      <!-- Notification Bell -->
      <li class="notif-wrapper">
        <button class="notif-bell" id="notifToggle" aria-label="Notifications">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <?php if ($notifCount > 0): ?>
          <span class="notif-count"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
          <?php endif; ?>
        </button>
        <!-- Dropdown -->
        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-header">
            <span class="notif-header-title">Notifications</span>
            <?php if ($notifCount > 0): ?>
            <span class="notif-header-badge"><?= $notifCount ?> new</span>
            <?php endif; ?>
          </div>
          <div class="notif-list">
            <?php if (empty($notifItems)): ?>
            <div class="notif-empty">
              <span style="font-size:1.8rem;">🔔</span>
              <p>You're all caught up!</p>
            </div>
            <?php else: ?>
              <?php foreach ($notifItems as $n): ?>
              <a href="<?= $n['link'] ?>" class="notif-item notif-item--<?= $n['type'] ?>">
                <div class="notif-item-icon"><?= $n['icon'] ?></div>
                <div class="notif-item-body">
                  <div class="notif-item-title"><?= $n['title'] ?></div>
                  <div class="notif-item-msg"><?= $n['message'] ?></div>
                  <?php if ($n['time']): ?>
                  <div class="notif-item-time"><?= timeAgo($n['time']) ?></div>
                  <?php endif; ?>
                </div>
              </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php if ($notifCount > 0): ?>
          <div class="notif-footer">
            <a href="<?= isAdmin() ? (($dir==='admin'?'':'admin/')).'orders.php' : (($dir==='staff'?'':'staff/')).'index.php' ?>">
              View all orders →
            </a>
          </div>
          <?php endif; ?>
        </div>
      </li>
      <?php endif; ?>
    <?php else: ?>
      <li><a href="<?= ($dir === 'admin' || $dir === 'staff') ? '../' : '' ?>login.php">Admin / Staff Login</a></li>
    <?php endif; ?>
    <?php if (!in_array($dir, ['admin', 'staff'])): ?>
      <li><a href="<?= ($dir === 'admin' || $dir === 'staff') ? '../' : '' ?>order.php" class="nav-cart-btn <?= $current==='order.php'?'active':'' ?>">Order Now</a></li>
    <?php endif; ?>
  </ul>
</nav>

<script>
(function(){
  const btn = document.getElementById('notifToggle');
  const dd  = document.getElementById('notifDropdown');
  if (!btn || !dd) return;
  btn.addEventListener('click', function(e){
    e.stopPropagation();
    dd.classList.toggle('open');
  });
  document.addEventListener('click', function(e){
    if (!dd.contains(e.target) && e.target !== btn) dd.classList.remove('open');
  });
})();
</script>
