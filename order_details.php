<?php
require_once 'config.php';
require_once 'auth_check.php';

$action  = $_GET['action'] ?? 'list';
$order_view = $_GET['order_id'] ?? null;
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM order_details WHERE order_detail_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: order_details.php?msg=deleted');
        exit;
    }
    $stmt->close();
}

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id   = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $quantity   = (int)$_POST['quantity'];
    $subtotal   = (float)$_POST['subtotal'];

    if (!$order_id || !$product_id || $quantity <= 0) {
        $errors[] = 'Order, product, and a valid quantity are required.';
    }

    if (empty($errors)) {
        if ($action === 'edit' && isset($_POST['order_detail_id'])) {
            $id = (int)$_POST['order_detail_id'];
            $stmt = $mysqli->prepare("UPDATE order_details SET order_id=?, product_id=?, quantity=?, subtotal=? WHERE order_detail_id=?");
            $stmt->bind_param('iiidi', $order_id, $product_id, $quantity, $subtotal, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: order_details.php?msg=updated');
            exit;
        } else {
            $stmt = $mysqli->prepare("INSERT INTO order_details (order_id, product_id, quantity, subtotal) VALUES (?,?,?,?)");
            $stmt->bind_param('iiid', $order_id, $product_id, $quantity, $subtotal);
            $stmt->execute();
            $stmt->close();
            header('Location: order_details.php?msg=added');
            exit;
        }
    }
}

// ---------- FETCH FOR EDIT ----------
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM order_details WHERE order_detail_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch orders & products for dropdowns
$orders_list   = $mysqli->query("SELECT order_id FROM orders ORDER BY order_id DESC");
$products_list = $mysqli->query("SELECT product_id, product_name, price FROM products ORDER BY product_name");

if (isset($_GET['msg'])) {
    $map = ['added'=>'Order detail added.','updated'=>'Order detail updated.','deleted'=>'Order detail deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Order Details Management</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <form method="post" action="order_details.php?action=<?= $action ?>" class="crud-form">
        <?php if ($edit_data): ?>
            <input type="hidden" name="order_detail_id" value="<?= $edit_data['order_detail_id'] ?>">
        <?php endif; ?>
        <label>Order
            <select name="order_id" required>
                <option value="">-- Select Order --</option>
                <?php while ($o = $orders_list->fetch_assoc()): ?>
                    <option value="<?= $o['order_id'] ?>" <?= (($edit_data['order_id'] ?? '') == $o['order_id']) ? 'selected' : '' ?>>
                        Order #<?= $o['order_id'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <label>Product
            <select name="product_id" required>
                <option value="">-- Select Product --</option>
                <?php while ($p = $products_list->fetch_assoc()): ?>
                    <option value="<?= $p['product_id'] ?>" <?= (($edit_data['product_id'] ?? '') == $p['product_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['product_name']) ?> (₱<?= number_format($p['price'], 2) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <label>Quantity
            <input type="number" name="quantity" min="1" value="<?= htmlspecialchars($edit_data['quantity'] ?? '1') ?>" required>
        </label>
        <label>Subtotal (₱)
            <input type="number" name="subtotal" step="0.01" min="0" value="<?= htmlspecialchars($edit_data['subtotal'] ?? '0') ?>" required>
        </label>
        <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?> Order Detail</button>
        <a href="order_details.php" class="btn btn-secondary">Cancel</a>
    </form>

    <?php else: ?>
    <a href="order_details.php?action=add" class="btn btn-add-new">+ Add Order Detail</a>
    <table>
        <thead>
        <tr>
            <th>ID</th><th>Order #</th><th>Product</th><th>Quantity</th><th>Subtotal</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
if ($order_view) {
    $stmt = $mysqli->prepare("
        SELECT od.*, p.product_name 
        FROM order_details od 
        LEFT JOIN products p ON od.product_id = p.product_id
        WHERE od.order_id = ?
    ");
    $stmt->bind_param("i", $order_view);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query("
        SELECT od.*, p.product_name 
        FROM order_details od 
        LEFT JOIN products p ON od.product_id = p.product_id 
        ORDER BY od.order_detail_id DESC
    ");
}        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['order_detail_id'] ?></td>
            <td>#<?= $row['order_id'] ?></td>
            <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
            <td><?= $row['quantity'] ?></td>
            <td>₱<?= number_format($row['subtotal'], 2) ?></td>
            <td class="actions">
                <a href="order_details.php?action=edit&id=<?= $row['order_detail_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="order_details.php?action=delete&id=<?= $row['order_detail_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this detail?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
