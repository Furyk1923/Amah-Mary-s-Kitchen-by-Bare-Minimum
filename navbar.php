<?php
// navbar.php - shared navigation bar matching wireframe design
$current_page = basename($_SERVER['PHP_SELF']);

// Determine which nav group is active
$is_home      = in_array($current_page, ['index.php','customers.php','users.php']);
$is_orders    = in_array($current_page, ['orders.php','order_details.php','delivery.php']);
$is_inventory = ($current_page === 'products.php');
$is_reports   = in_array($current_page, ['reports.php','products_xml.php']);
?>
<!-- Top dark navbar -->
<nav class="top-navbar">
    <div class="top-navbar-brand">
        <span class="brand-icon"></span>
        <span>AMAH MARY'S KITCHEN</span>
    </div>
    <span class="top-navbar-user">User: <?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
</nav>
<!-- Secondary navigation -->
<nav class="sec-navbar">
    <div class="sec-nav-item <?= $is_home ? 'active' : '' ?>">
        <a href="index.php">Home</a>
        <span class="nav-arrow">&#9662;</span>
        <div class="sec-dropdown">
            <a href="index.php">Dashboard</a>
            <a href="customers.php">Customers</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <a href="users.php">Users</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="sec-nav-item <?= $is_orders ? 'active' : '' ?>">
        <a href="orders.php">Orders</a>
        <span class="nav-arrow">&#9662;</span>
        <div class="sec-dropdown">
            <a href="orders.php">Order Management</a>
            <a href="order_details.php">Order Details</a>
            <a href="delivery.php">Delivery</a>
        </div>
    </div>
    <div class="sec-nav-item <?= $is_inventory ? 'active' : '' ?>">
        <a href="products.php"><strong>Inventory</strong></a>
    </div>
    <div class="sec-nav-item <?= $is_reports ? 'active' : '' ?>">
        <a href="reports.php">Reports</a>
        <span class="nav-arrow">&#9662;</span>
        <div class="sec-dropdown">
            <a href="reports.php">Sales Reports</a>
            <a href="products_xml.php">XML Report</a>
        </div>
    </div>
</nav>

