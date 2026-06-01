<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$msg = '';

// Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
  $name = sanitize($conn, $_POST['name']);
  $contact = sanitize($conn, $_POST['contact']);
  $email = sanitize($conn, $_POST['email']);
  $username = sanitize($conn, $_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $role = sanitize($conn, $_POST['role']);

  $check = $conn->query("SELECT id FROM staff WHERE username='$username'")->num_rows;
  if ($check > 0) {
    $msg = 'error:Username already exists.';
  } else {
    $conn->query("INSERT INTO staff (name,contact,email,username,password,role) VALUES ('$name','$contact','$email','$username','$password','$role')");
    $msg = 'success:Staff member added!';
  }
}

// Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
  $id = (int)$_POST['id'];
  $name = sanitize($conn, $_POST['name']);
  $contact = sanitize($conn, $_POST['contact']);
  $email = sanitize($conn, $_POST['email']);
  $role = sanitize($conn, $_POST['role']);
  $pwdSql = '';
  if (!empty($_POST['password'])) {
    $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $pwdSql = ", password='$hash'";
  }
  $conn->query("UPDATE staff SET name='$name',contact='$contact',email='$email',role='$role' $pwdSql WHERE id=$id");
  $msg = 'success:Staff updated!';
}

// Delete Staff
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id != $_SESSION['staff_id']) {
    $conn->query("DELETE FROM staff WHERE id=$id");
    $msg = 'success:Staff removed.';
  } else {
    $msg = 'error:Cannot delete your own account.';
  }
}

$staffList = $conn->query("SELECT * FROM staff ORDER BY role DESC, name");
$editStaff = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $editStaff = $conn->query("SELECT * FROM staff WHERE id=$eid")->fetch_assoc();
}

list($msgType, $msgText) = $msg ? explode(':', $msg, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff — Admin | Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <div class="dashboard-main">
    <div class="dash-header">
      <h1 class="dash-title">Staff Management</h1>
      <button class="btn btn-gold" id="addBtn">+ Add Staff</button>
    </div>

    <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <div class="data-card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Name</th><th>Username</th><th>Email</th><th>Contact</th><th>Role</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while ($s = $staffList->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
              <td style="font-family:monospace;color:var(--gold);"><?= htmlspecialchars($s['username']) ?></td>
              <td style="font-size:0.82rem;"><?= htmlspecialchars($s['email']) ?></td>
              <td style="font-size:0.82rem;"><?= htmlspecialchars($s['contact']) ?></td>
              <td><span class="badge <?= $s['role']==='admin'?'badge-gold':'badge-blue' ?>"><?= $s['role'] ?></span></td>
              <td>
                <div style="display:flex;gap:0.4rem;">
                  <a href="staff.php?edit=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <?php if ($s['id'] != $_SESSION['staff_id']): ?>
                  <a href="staff.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                     onclick="return confirm('Remove this staff member?')">Delete</a>
                  <?php endif; ?>
                </div>
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

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Add Staff Member</h3>
      <button class="modal-close" id="closeAdd">✕</button>
    </div>
    <form method="post" action="staff.php">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Contact</label>
            <input type="text" name="contact" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" id="cancelAdd">Cancel</button>
        <button type="submit" name="add_staff" class="btn btn-gold">Add Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editStaff): ?>
<div class="modal-overlay active">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Edit Staff</h3>
      <a href="staff.php" class="modal-close">✕</a>
    </div>
    <form method="post" action="staff.php">
      <input type="hidden" name="id" value="<?= $editStaff['id'] ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editStaff['name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
              <option value="staff" <?= $editStaff['role']==='staff'?'selected':'' ?>>Staff</option>
              <option value="admin" <?= $editStaff['role']==='admin'?'selected':'' ?>>Admin</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Contact</label>
            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($editStaff['contact']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editStaff['email']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control" placeholder="Enter new password">
        </div>
      </div>
      <div class="modal-footer">
        <a href="staff.php" class="btn btn-ghost">Cancel</a>
        <button type="submit" name="edit_staff" class="btn btn-gold">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
const addModal = document.getElementById('addModal');
document.getElementById('addBtn')?.addEventListener('click', () => addModal.classList.add('active'));
document.getElementById('closeAdd')?.addEventListener('click', () => addModal.classList.remove('active'));
document.getElementById('cancelAdd')?.addEventListener('click', () => addModal.classList.remove('active'));
</script>
</body>
</html>
