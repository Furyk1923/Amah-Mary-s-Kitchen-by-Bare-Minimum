<?php
require_once 'config.php';
require_once 'auth_check.php';

$action  = $_GET['action'] ?? 'list';
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM customers WHERE customer_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: customers.php?msg=deleted');
        exit;
    }
    $stmt->close();
}

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name']);
    $contact_number = trim($_POST['contact_number']);
    $address        = trim($_POST['address']);
    $email          = trim($_POST['email']);

    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($errors)) {
        if ($action === 'edit' && isset($_POST['customer_id'])) {
            $id = (int)$_POST['customer_id'];
            $stmt = $mysqli->prepare("UPDATE customers SET full_name=?, contact_number=?, address=?, email=? WHERE customer_id=?");
            $stmt->bind_param('ssssi', $full_name, $contact_number, $address, $email, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: customers.php?msg=updated');
            exit;
        } else {
            $stmt = $mysqli->prepare("INSERT INTO customers (full_name, contact_number, address, email) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $full_name, $contact_number, $address, $email);
            $stmt->execute();
            $stmt->close();
            header('Location: customers.php?msg=added');
            exit;
        }
    }
}

// ---------- FETCH FOR EDIT ----------
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (isset($_GET['msg'])) {
    $map = ['added'=>'Customer added.','updated'=>'Customer updated.','deleted'=>'Customer deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers - Amah Mary's Kitchen</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'navbar.php'; ?>

<div class="container">
<h2>Customers Management</h2>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>

<form method="post" action="customers.php?action=<?= $action ?>" class="crud-form">

<?php if ($edit_data): ?>
<input type="hidden" name="customer_id" value="<?= $edit_data['customer_id'] ?>">
<?php endif; ?>

<label>Full Name
<input type="text" name="full_name" value="<?= htmlspecialchars($edit_data['full_name'] ?? '') ?>" required>
</label>

<label>Contact Number
<input type="text" name="contact_number" value="<?= htmlspecialchars($edit_data['contact_number'] ?? '') ?>">
</label>

<label>Address
<textarea name="address"><?= htmlspecialchars($edit_data['address'] ?? '') ?></textarea>
</label>

<label>Email
<input type="email" name="email" value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>">
</label>

<button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?> Customer</button>
<a href="customers.php" class="btn btn-secondary">Cancel</a>

</form>

<?php else: ?>

<a href="customers.php?action=add" class="btn btn-add-new">+ Add Customer</a>

<br><br>

<input type="text" id="searchCustomer" placeholder="Search customers..."
style="padding:8px;width:250px;">

<table id="customerTable">

<thead>
<tr>
<th>ID</th>
<th>Full Name</th>
<th>Contact</th>
<th>Address</th>
<th>Email</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php
$result = $mysqli->query("SELECT * FROM customers ORDER BY customer_id DESC");
while ($row = $result->fetch_assoc()):
?>

<tr>
<td><?= $row['customer_id'] ?></td>
<td><?= htmlspecialchars($row['full_name']) ?></td>
<td><?= htmlspecialchars($row['contact_number']) ?></td>
<td><?= htmlspecialchars($row['address']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>

<td class="actions">
<a href="customers.php?action=edit&id=<?= $row['customer_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
<a href="customers.php?action=delete&id=<?= $row['customer_id'] ?>" class="btn btn-danger btn-sm"
onclick="return confirm('Delete this customer?')">Delete</a>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php endif; ?>

</div>

<script>
document.getElementById("searchCustomer").addEventListener("keyup", function() {
let filter = this.value.toLowerCase();
let rows = document.querySelectorAll("#customerTable tbody tr");

rows.forEach(row => {
let text = row.innerText.toLowerCase();
row.style.display = text.includes(filter) ? "" : "none";
});
});
</script>

</body>
</html>

