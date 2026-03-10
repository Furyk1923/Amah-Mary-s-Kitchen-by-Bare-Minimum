<?php
require_once 'config.php';
require_once 'auth_check.php';

$action  = $_GET['action'] ?? 'list';
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: products.php?msg=deleted');
        exit;
    }
    $stmt->close();
}

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name   = trim($_POST['product_name']);
    $description    = trim($_POST['description']);
    $price          = (float)$_POST['price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $status         = in_array($_POST['status'], ['Active','Low Stock','Discontinued']) ? $_POST['status'] : 'Active';

    if (empty($product_name) || $price <= 0) {
        $errors[] = 'Product name and a valid price are required.';
    }

    if (empty($errors)) {
        if ($action === 'edit' && isset($_POST['product_id'])) {
            $id = (int)$_POST['product_id'];
            $stmt = $mysqli->prepare("UPDATE products SET product_name=?, description=?, price=?, stock_quantity=?, status=? WHERE product_id=?");
            $stmt->bind_param('ssdisi', $product_name, $description, $price, $stock_quantity, $status, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: products.php?msg=updated');
            exit;
        } else {
            $stmt = $mysqli->prepare("INSERT INTO products (product_name, description, price, stock_quantity, status) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssdis', $product_name, $description, $price, $stock_quantity, $status);
            $stmt->execute();
            $stmt->close();
            header('Location: products.php?msg=added');
            exit;
        }
    }
}

// ---------- FETCH FOR EDIT ----------
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Flash message
if (isset($_GET['msg'])) {
    $map = ['added'=>'Product added.','updated'=>'Product updated.','deleted'=>'Product deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Products Management</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

    <!-- ADD / EDIT FORM -->
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <form method="post" action="products.php?action=<?= $action ?>" class="crud-form">
        <?php if ($edit_data): ?>
            <input type="hidden" name="product_id" value="<?= $edit_data['product_id'] ?>">
        <?php endif; ?>
        <label>Product Name
            <input type="text" name="product_name" value="<?= htmlspecialchars($edit_data['product_name'] ?? '') ?>" required>
        </label>
        <label>Description
            <textarea name="description"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
        </label>
        <label>Price (₱)
            <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($edit_data['price'] ?? '') ?>" required>
        </label>
        <label>Stock Quantity
            <input type="number" name="stock_quantity" min="0" value="<?= htmlspecialchars($edit_data['stock_quantity'] ?? '0') ?>">
        </label>
        <label>Status
            <select name="status">
                <?php foreach (['Active','Low Stock','Discontinued'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($edit_data['status'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?> Product</button>
        <a href="products.php" class="btn btn-secondary">Cancel</a>
    </form>

    <?php else: ?>
    <!-- LIST -->
    <a href="products.php?action=add" class="btn btn-success mb-20">+ Add Product</a>
    <table>
        <thead>
        <tr>
            <th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $result = $mysqli->query("SELECT * FROM products ORDER BY product_id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['product_id'] ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td>₱<?= number_format($row['price'], 2) ?></td>
            <td><?= $row['stock_quantity'] ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td class="actions">
                <a href="products.php?action=edit&id=<?= $row['product_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="products.php?action=delete&id=<?= $row['product_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
