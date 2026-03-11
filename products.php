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

    // Auto-determine status based on stock
    if ($stock_quantity <= 0) {
        $status = 'Discontinued';
    } elseif ($stock_quantity <= 5) {
        $status = 'Low Stock';
    } else {
        $status = 'Active';
    }

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
    <title>Inventory - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2 class="page-title">PRODUCT INVENTORY</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

    <!-- Inventory section -->
    <div class="inv-box">
        <a href="products.php?action=add" class="btn btn-add-new">+ Add New Product</a>

        <table>
            <thead>
            <tr>
                <th>Product name</th><th>Price</th><th>Stock</th><th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $result = $mysqli->query("SELECT * FROM products ORDER BY product_id ASC");
            while ($row = $result->fetch_assoc()):
            ?>
            <tr class="inv-row" onclick="window.location='products.php?action=edit&id=<?= $row['product_id'] ?>'" style="cursor:pointer;">
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td>₱ <?= number_format($row['price'], 0) ?></td>
                <td><?= $row['stock_quantity'] ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($action === 'edit' && $edit_data): ?>
        <!-- Edit Product Modal (inline card) -->
        <p class="inv-modal-hint">*Edit Product Modal Pop-up on click*</p>
        <div class="inv-modal-card">
            <form method="post" action="products.php?action=edit">
                <input type="hidden" name="product_id" value="<?= $edit_data['product_id'] ?>">
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Name:</span>
                    <input type="text" name="product_name" value="<?= htmlspecialchars($edit_data['product_name']) ?>" required>
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Price:</span>
                    <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($edit_data['price']) ?>" required>
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Stock:</span>
                    <input type="number" name="stock_quantity" min="0" value="<?= htmlspecialchars($edit_data['stock_quantity']) ?>">
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Status:</span>
                    <select name="status">
                        <?php foreach (['Active','Low Stock','Discontinued'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit_data['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="description" value="<?= htmlspecialchars($edit_data['description'] ?? '') ?>">
                <div class="inv-modal-actions">
                    <button type="submit" class="btn-dark btn-save">SAVE CHANGES</button>
                    <a href="products.php?action=delete&id=<?= $edit_data['product_id'] ?>" class="btn-dark btn-cancel" onclick="return confirm('Delete this product?')">DELETE</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($action === 'add'): ?>
        <!-- Add Product Form -->
        <p class="inv-modal-hint">Add New Product</p>
        <div class="inv-modal-card">
            <form method="post" action="products.php?action=add">
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Name:</span>
                    <input type="text" name="product_name" required>
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Description:</span>
                    <input type="text" name="description">
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Price:</span>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Stock:</span>
                    <input type="number" name="stock_quantity" min="0" value="0">
                </div>
                <div class="inv-modal-field">
                    <span class="inv-modal-label">Status:</span>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Discontinued">Discontinued</option>
                    </select>
                </div>
                <div class="inv-modal-actions">
                    <button type="submit" class="btn-dark btn-save">ADD PRODUCT</button>
                    <a href="products.php" class="btn-dark btn-cancel">CANCEL</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
