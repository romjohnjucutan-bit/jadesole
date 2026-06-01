<?php
require_once 'config.php';

if (isLoggedIn()) redirect(isAdmin() ? 'admin/index.php' : 'staff/index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($conn, $_POST['username']);
  $password = $_POST['password'];
  $res = $conn->query("SELECT * FROM staff WHERE username='$username' LIMIT 1");
  if ($res && $res->num_rows > 0) {
    $user = $res->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['staff_id'] = $user['id'];
      $_SESSION['staff_name'] = $user['name'];
      $_SESSION['role'] = $user['role'];
      redirect($user['role'] === 'admin' ? 'admin/index.php' : 'staff/index.php');
    } else {
      $error = "Incorrect username or password.";
    }
  } else {
    $error = "Incorrect username or password.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Login — Jade Sole</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">

  <!-- Left: Brand Panel -->
  <div class="login-bg">
    <div class="login-bg-tag">
      <span class="login-bg-tag-line"></span>
      Staff & Admin Portal
    </div>
    <div class="login-bg-brand">
      Step Into<br>Your Shift.
    </div>
    <p class="login-bg-sub">
      Manage orders, products, and your team — all from one place. Welcome to the Jade Sole dashboard.
    </p>
  </div>

  <!-- Right: Login Form -->
  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-icon">👟</div>
      <h2>Welcome back</h2>
      <p>Sign in to your staff account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" style="width:100%;max-width:380px;">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="e.g. admin" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
      </div>
      <button type="submit" class="btn btn-gold w-full" style="justify-content:center;margin-top:0.8rem;">
        Sign In →
      </button>
    </form>

    <div class="mt-3" style="width:100%;max-width:380px;">
      <a href="index.php" style="font-size:0.82rem;color:var(--text-dim);">← Back to Store</a>
    </div>
  </div>

</div>
</body>
</html>
