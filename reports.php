<?php
require_once 'config.php';
require_once 'auth_check.php';

$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$generated  = !empty($start_date) && !empty($end_date);

$total_revenue  = 0;
$total_orders   = 0;
$top_item       = 'N/A';
$daily_data     = [];

if ($generated) {
    // Summary: Total Revenue
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(total_amount),0) AS rev, COUNT(*) AS cnt FROM orders WHERE order_date BETWEEN ? AND ? AND status != 'Cancelled'");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total_revenue = $row['rev'];
    $total_orders  = $row['cnt'];
    $stmt->close();

    // Top Selling Item
    $stmt = $mysqli->prepare("SELECT p.product_name, SUM(od.quantity) AS qty
        FROM order_details od
        JOIN orders o ON od.order_id = o.order_id
        JOIN products p ON od.product_id = p.product_id
        WHERE o.order_date BETWEEN ? AND ? AND o.status != 'Cancelled'
        GROUP BY od.product_id
        ORDER BY qty DESC LIMIT 1");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $top_item = $res['product_name'];
    $stmt->close();

    // Daily Breakdown
    $stmt = $mysqli->prepare("SELECT order_date, COUNT(*) AS orders_count, SUM(total_amount) AS revenue
        FROM orders
        WHERE order_date BETWEEN ? AND ? AND status != 'Cancelled'
        GROUP BY order_date
        ORDER BY order_date ASC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $daily_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2 class="page-title">SALES REPORTS</h2>

    <!-- Date Range Filter -->
    <form method="get" action="reports.php" class="report-filter">
        <span>Date Range:</span>
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
        <span>to</span>
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
        <button type="submit" class="btn-dark">GENERATE REPORT</button>
    </form>

    <?php if ($generated): ?>
    <!-- Summary -->
    <h3 class="section-heading">SUMMARY</h3>
    <div class="summary-box">
        <div class="summary-row">
            <span class="summary-label">Total Revenue:</span>
            <span class="summary-value">₱ <?= number_format($total_revenue, 2) ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Orders:</span>
            <span class="summary-value"><?= $total_orders ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Top Selling Item:</span>
            <span class="summary-value"><?= htmlspecialchars($top_item) ?></span>
        </div>
    </div>

    <!-- Daily Breakdown -->
    <h3 class="section-heading">DAILY BREAKDOWNS</h3>
    <table>
        <thead>
        <tr>
            <th>Date</th><th>Orders</th><th>Revenue</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($daily_data)): ?>
            <tr><td colspan="3" style="text-align:center;">No data for this date range.</td></tr>
        <?php else: ?>
            <?php foreach ($daily_data as $day): ?>
            <tr>
                <td><?= date('M d', strtotime($day['order_date'])) ?></td>
                <td><?= $day['orders_count'] ?></td>
                <td>₱ <?= number_format($day['revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#888; margin-top:20px;">Select a date range and click GENERATE REPORT to view sales data.</p>
    <?php endif; ?>
</div>

</body>
</html>
