<?php
require_once 'config.php';
require_once 'auth_check.php';

$action  = $_GET['action'] ?? 'list';
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: orders.php?msg=deleted');
        exit;
    }
    $stmt->close();
}

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_date   = trim($_POST['order_date']);
    $total_amount = (float)$_POST['total_amount'];
    $status       = in_array($_POST['status'], ['Pending','Completed','Cancelled']) ? $_POST['status'] : 'Pending';
    $customer_id  = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;

    if (empty($order_date)) {
        $errors[] = 'Order date is required.';
    }

    if (empty($errors)) {
        if ($action === 'edit' && isset($_POST['order_id'])) {
            $id = (int)$_POST['order_id'];
            $stmt = $mysqli->prepare("UPDATE orders SET order_date=?, total_amount=?, status=?, customer_id=? WHERE order_id=?");
            $stmt->bind_param('sdsii', $order_date, $total_amount, $status, $customer_id, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: orders.php?msg=updated');
            exit;
        } else {
            $stmt = $mysqli->prepare("INSERT INTO orders (order_date, total_amount, status, customer_id) VALUES (?,?,?,?)");
            $stmt->bind_param('sdsi', $order_date, $total_amount, $status, $customer_id);
            $stmt->execute();
            $stmt->close();
            header('Location: orders.php?msg=added');
            exit;
        }
    }
}

// ---------- FETCH FOR EDIT ----------
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch customers for dropdown
$customers = $mysqli->query("SELECT customer_id, full_name FROM customers ORDER BY full_name");

if (isset($_GET['msg'])) {
    $map = ['added'=>'Order added.','updated'=>'Order updated.','deleted'=>'Order deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Orders Management</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <form method="post" action="orders.php?action=<?= $action ?>" class="crud-form">
        <?php if ($edit_data): ?>
            <input type="hidden" name="order_id" value="<?= $edit_data['order_id'] ?>">
        <?php endif; ?>
        <label>Order Date
            <input type="date" name="order_date" value="<?= htmlspecialchars($edit_data['order_date'] ?? date('Y-m-d')) ?>" required>
        </label>
        <label>Total Amount (₱)
            <input type="number" name="total_amount" step="0.01" min="0" value="<?= htmlspecialchars($edit_data['total_amount'] ?? '0') ?>" required>
        </label>
        <label>Status
            <select name="status">
                <?php foreach (['Pending','Completed','Cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($edit_data['status'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Customer
            <select name="customer_id">
                <option value="">-- Select Customer --</option>
                <?php
                $customers->data_seek(0);
                while ($c = $customers->fetch_assoc()):
                ?>
                    <option value="<?= $c['customer_id'] ?>" <?= (($edit_data['customer_id'] ?? '') == $c['customer_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?> Order</button>
        <a href="orders.php" class="btn btn-secondary">Cancel</a>
    </form>

    <?php else: ?>
    <a href="orders.php?action=add" class="btn btn-success mb-20">+ Add Order</a>
    <table>
        <thead>
        <tr>
            <th>ID</th><th>Date</th><th>Total</th><th>Status</th><th>Customer</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $result = $mysqli->query("SELECT o.*, c.full_name AS customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id ORDER BY o.order_id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['order_id'] ?></td>
            <td><?= htmlspecialchars($row['order_date']) ?></td>
            <td>₱<?= number_format($row['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
            <td class="actions">
                <a href="orders.php?action=edit&id=<?= $row['order_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="orders.php?action=delete&id=<?= $row['order_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this order?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
