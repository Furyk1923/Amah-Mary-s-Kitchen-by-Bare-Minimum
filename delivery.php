<?php
require_once 'config.php';
require_once 'auth_check.php';

$action  = $_GET['action'] ?? 'list';
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM delivery WHERE delivery_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: delivery.php?msg=deleted');
        exit;
    }
    $stmt->close();
}

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id         = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $delivery_service = in_array($_POST['delivery_service'], ['Lalamove','Grab','Pickup']) ? $_POST['delivery_service'] : 'Pickup';
    $delivery_status  = in_array($_POST['delivery_status'], ['In-progress','Completed','Delayed']) ? $_POST['delivery_status'] : 'In-progress';
    $delivery_date    = trim($_POST['delivery_date']);

    if (!$order_id) {
        $errors[] = 'An order must be selected.';
    }

    if (empty($errors)) {
        $date_val = !empty($delivery_date) ? $delivery_date : null;
        if ($action === 'edit' && isset($_POST['delivery_id'])) {
            $id = (int)$_POST['delivery_id'];
            $stmt = $mysqli->prepare("UPDATE delivery SET order_id=?, delivery_service=?, delivery_status=?, delivery_date=? WHERE delivery_id=?");
            $stmt->bind_param('isssi', $order_id, $delivery_service, $delivery_status, $date_val, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: delivery.php?msg=updated');
            exit;
        } else {
            $stmt = $mysqli->prepare("INSERT INTO delivery (order_id, delivery_service, delivery_status, delivery_date) VALUES (?,?,?,?)");
            $stmt->bind_param('isss', $order_id, $delivery_service, $delivery_status, $date_val);
            $stmt->execute();
            $stmt->close();
            header('Location: delivery.php?msg=added');
            exit;
        }
    }
}

// ---------- FETCH FOR EDIT ----------
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM delivery WHERE delivery_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$orders_list = $mysqli->query("SELECT order_id FROM orders ORDER BY order_id DESC");

if (isset($_GET['msg'])) {
    $map = ['added'=>'Delivery added.','updated'=>'Delivery updated.','deleted'=>'Delivery deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Delivery Management</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <form method="post" action="delivery.php?action=<?= $action ?>" class="crud-form">
        <?php if ($edit_data): ?>
            <input type="hidden" name="delivery_id" value="<?= $edit_data['delivery_id'] ?>">
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
        <label>Delivery Service
            <select name="delivery_service">
                <?php foreach (['Lalamove','Grab','Pickup'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($edit_data['delivery_service'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Delivery Status
            <select name="delivery_status">
                <?php foreach (['In-progress','Completed','Delayed'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($edit_data['delivery_status'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Delivery Date
            <input type="date" name="delivery_date" value="<?= htmlspecialchars($edit_data['delivery_date'] ?? '') ?>">
        </label>
        <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?> Delivery</button>
        <a href="delivery.php" class="btn btn-secondary">Cancel</a>
    </form>

    <?php else: ?>
    <a href="delivery.php?action=add" class="btn btn-success mb-20">+ Add Delivery</a>
    <table>
        <thead>
        <tr>
            <th>ID</th><th>Order #</th><th>Service</th><th>Status</th><th>Date</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $result = $mysqli->query("SELECT * FROM delivery ORDER BY delivery_id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['delivery_id'] ?></td>
            <td>#<?= $row['order_id'] ?></td>
            <td><?= htmlspecialchars($row['delivery_service']) ?></td>
            <td><?= htmlspecialchars($row['delivery_status']) ?></td>
            <td><?= htmlspecialchars($row['delivery_date'] ?? 'N/A') ?></td>
            <td class="actions">
                <a href="delivery.php?action=edit&id=<?= $row['delivery_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="delivery.php?action=delete&id=<?= $row['delivery_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this delivery?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
