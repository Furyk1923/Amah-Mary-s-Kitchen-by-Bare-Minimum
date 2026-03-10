<?php
require_once 'config.php';
require_once 'auth_check.php';

// Today's sales
$today = date('Y-m-d');
$today_sales = $mysqli->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE order_date = '$today' AND status != 'Cancelled'")->fetch_assoc()['total'];

// Pending orders count
$pending_orders = $mysqli->query("SELECT COUNT(*) AS c FROM orders WHERE status='Pending'")->fetch_assoc()['c'];

// Low stock products (stock_quantity <= 5 and Active or Low Stock)
$low_stock = $mysqli->query("SELECT product_id, product_name, stock_quantity FROM products WHERE stock_quantity <= 5 AND status != 'Discontinued' ORDER BY stock_quantity ASC");

// Upcoming deliveries today & tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$deliveries_upcoming = $mysqli->query("
    SELECT d.delivery_id, d.order_id, d.delivery_service, d.delivery_status,
           d.delivery_date, c.full_name AS customer_name
    FROM delivery d
    JOIN orders o  ON d.order_id = o.order_id
    JOIN customers c ON o.customer_id = c.customer_id
    WHERE DATE(d.delivery_date) IN ('$today','$tomorrow') AND d.delivery_status != 'Completed'
    ORDER BY d.delivery_date ASC
");
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
    <h2 class="page-title">DASHBOARD</h2>

    <!-- Stat pills row -->
    <div class="dash-stats">
        <div class="dash-pill">
            <div class="dash-pill-label">TODAY'S SALES</div>
            <div class="dash-pill-value">₱ <?= number_format($today_sales, 2) ?></div>
        </div>
        <div class="dash-pill">
            <div class="dash-pill-label">PENDING ORDERS</div>
            <div class="dash-pill-value"><?= str_pad($pending_orders, 2, '0', STR_PAD_LEFT) ?></div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="dash-section">
        <div class="dash-section-title">LOW STOCK ALERT!!!</div>
        <?php if ($low_stock && $low_stock->num_rows > 0): ?>
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Remaining</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($p = $low_stock->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                    <td><?= (int)$p['stock_quantity'] ?> Jar</td>
                    <td><a href="products.php?edit=<?= $p['product_id'] ?>" class="dash-action-link">Restock</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="dash-empty">All products are well-stocked.</p>
        <?php endif; ?>
    </div>

    <!-- Upcoming Deliveries -->
    <div class="dash-section">
        <div class="dash-section-title">UPCOMING DELIVERIES (TODAY &amp; TOMORROW)</div>
        <?php if ($deliveries_upcoming && $deliveries_upcoming->num_rows > 0): ?>
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Courier</th>
                    <th>Date / Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php while ($d = $deliveries_upcoming->fetch_assoc()):
                $dt = $d['delivery_date'] ? date('M j, g:i A', strtotime($d['delivery_date'])) : '—';
            ?>
                <tr>
                    <td>#<?= $d['order_id'] ?> – <?= htmlspecialchars($d['customer_name']) ?></td>
                    <td>via <?= htmlspecialchars($d['delivery_service']) ?></td>
                    <td><?= $dt ?></td>
                    <td><a href="delivery.php" class="dash-action-link">[Track]</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="dash-empty">No upcoming deliveries.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
