<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$msg = '';

// Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
  $name = sanitize($conn, $_POST['name']);
  $desc = sanitize($conn, $_POST['description']);
  $price = (float)$_POST['price'];
  $cat = (int)$_POST['category_id'];
  $stock = (int)$_POST['stock'];
  $image = 'default.jpg';

  if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fname = 'prod_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $fname);
    $image = $fname;
  }

  $conn->query("INSERT INTO products (name, description, price, category_id, image, stock)
                VALUES ('$name','$desc',$price,$cat,'$image',$stock)");
  $msg = 'success:Product added successfully!';
}

// Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
  $id = (int)$_POST['id'];
  $name = sanitize($conn, $_POST['name']);
  $desc = sanitize($conn, $_POST['description']);
  $price = (float)$_POST['price'];
  $cat = (int)$_POST['category_id'];
  $stock = (int)$_POST['stock'];
  $avail = isset($_POST['is_available']) ? 1 : 0;

  $imgSql = '';
  if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fname = 'prod_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $fname);
    $imgSql = ", image='$fname'";
  }

  $conn->query("UPDATE products SET name='$name', description='$desc', price=$price,
                category_id=$cat, stock=$stock, is_available=$avail $imgSql WHERE id=$id");
  $msg = 'success:Product updated!';
}

// Delete Product
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $conn->query("DELETE FROM products WHERE id=$id");
  $msg = 'success:Product deleted.';
}

// Toggle Availability
if (isset($_GET['toggle'])) {
  $id = (int)$_GET['toggle'];
  $conn->query("UPDATE products SET is_available = 1 - is_available WHERE id=$id");
  redirect('products.php');
}

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.category_id, p.name");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  $editProduct = $conn->query("SELECT * FROM products WHERE id=$eid")->fetch_assoc();
}

list($msgType, $msgText) = $msg ? explode(':', $msg, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — Admin | Jade Sole</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="page-wrap">
<div class="dashboard-layout">
  <?php include 'sidebar.php'; ?>
  <div class="dashboard-main">
    <div class="dash-header">
      <h1 class="dash-title">Products</h1>
      <button class="btn btn-gold" id="addBtn">+ Add Product</button>
    </div>

    <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="data-card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php while ($p = $products->fetch_assoc()):
              $emoji = match((int)$p['category_id']) { 1=>'👟',2=>'👟',3=>'👞',4=>'🩴',5=>'🥾',default=>'👟' };
            ?>
            <tr>
              <td>
                <div style="width:44px;height:44px;background:var(--dark);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                  <?php if ($p['image'] !== 'default.jpg' && file_exists('../assets/images/'.$p['image'])): ?>
                    <img src="../assets/images/<?= htmlspecialchars($p['image']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                  <?php else: echo $emoji; endif; ?>
                </div>
              </td>
              <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
              <td><?= htmlspecialchars($p['cat_name']) ?></td>
              <td class="text-gold">₱<?= number_format($p['price'],2) ?></td>
              <td><?= $p['stock'] ?></td>
              <td>
                <a href="products.php?toggle=<?= $p['id'] ?>">
                  <span class="badge <?= $p['is_available'] ? 'badge-green' : 'badge-red' ?>">
                    <?= $p['is_available'] ? 'Available' : 'Unavailable' ?>
                  </span>
                </a>
              </td>
              <td>
                <div style="display:flex;gap:0.4rem;">
                  <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                     onclick="return confirm('Delete this product?')">Delete</a>
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
      <h3 class="modal-title">Add Product</h3>
      <button class="modal-close" id="closeAdd">✕</button>
    </div>
    <form method="post" action="products.php" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-control" required>
              <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Price (₱)</label>
            <input type="number" name="price" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Initial Stock</label>
            <input type="number" name="stock" class="form-control" value="10" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Product Image</label>
          <input type="file" name="image" class="form-control" accept="image/*">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" id="cancelAdd">Cancel</button>
        <button type="submit" name="add_product" class="btn btn-gold">Add Product</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<?php if ($editProduct): ?>
<div class="modal-overlay active" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Edit Product</h3>
      <a href="products.php" class="modal-close">✕</a>
    </div>
    <form method="post" action="products.php" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editProduct['name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-control">
              <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= $c['id']==$editProduct['category_id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editProduct['description']) ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Price (₱)</label>
            <input type="number" name="price" class="form-control" step="0.01" value="<?= $editProduct['price'] ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" value="<?= $editProduct['stock'] ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">New Image (optional)</label>
          <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:10px;">
          <input type="checkbox" name="is_available" id="isAvail" <?= $editProduct['is_available']?'checked':'' ?> style="width:auto;">
          <label for="isAvail" class="form-label" style="margin:0;">Available for sale</label>
        </div>
      </div>
      <div class="modal-footer">
        <a href="products.php" class="btn btn-ghost">Cancel</a>
        <button type="submit" name="edit_product" class="btn btn-gold">Save Changes</button>
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
addModal?.addEventListener('click', e => { if(e.target === addModal) addModal.classList.remove('active'); });
</script>
</body>
</html>
