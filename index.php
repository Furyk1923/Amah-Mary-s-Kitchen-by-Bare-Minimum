<?php
require_once 'config.php';
require_once 'auth_check.php';

// Fetch counts for dashboard cards
$product_count  = $mysqli->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$customer_count = $mysqli->query("SELECT COUNT(*) AS c FROM customers")->fetch_assoc()['c'];
$order_count    = $mysqli->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$delivery_count = $mysqli->query("SELECT COUNT(*) AS c FROM delivery")->fetch_assoc()['c'];
$pending_orders = $mysqli->query("SELECT COUNT(*) AS c FROM orders WHERE status='Pending'")->fetch_assoc()['c'];
$user_count     = $mysqli->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Dashboard</h2>
    <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! Here's an overview of the system.</p>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Products</h3>
            <div class="count"><?= $product_count ?></div>
            <a href="products.php">Manage Products →</a>
        </div>
        <div class="card">
            <h3>Customers</h3>
            <div class="count"><?= $customer_count ?></div>
            <a href="customers.php">Manage Customers →</a>
        </div>
        <div class="card">
            <h3>Orders</h3>
            <div class="count"><?= $order_count ?></div>
            <a href="orders.php">Manage Orders →</a>
        </div>
        <div class="card">
            <h3>Pending Orders</h3>
            <div class="count"><?= $pending_orders ?></div>
            <a href="orders.php">View Orders →</a>
        </div>
        <div class="card">
            <h3>Deliveries</h3>
            <div class="count"><?= $delivery_count ?></div>
            <a href="delivery.php">Manage Delivery →</a>
        </div>
        <?php if ($_SESSION['role'] === 'Admin'): ?>
        <div class="card">
            <h3>Users</h3>
            <div class="count"><?= $user_count ?></div>
            <a href="users.php">Manage Users →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
