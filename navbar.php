<?php
// navbar.php - shared navigation bar (include on every protected page)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <a class="brand" href="index.php">🍳 Amah Mary's Kitchen</a>
    <ul class="nav-links">
        <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">Products</a></li>
        <li><a href="customers.php" class="<?= $current_page === 'customers.php' ? 'active' : '' ?>">Customers</a></li>
        <li><a href="orders.php" class="<?= $current_page === 'orders.php' ? 'active' : '' ?>">Orders</a></li>
        <li><a href="order_details.php" class="<?= $current_page === 'order_details.php' ? 'active' : '' ?>">Order Details</a></li>
        <li><a href="delivery.php" class="<?= $current_page === 'delivery.php' ? 'active' : '' ?>">Delivery</a></li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <li><a href="users.php" class="<?= $current_page === 'users.php' ? 'active' : '' ?>">Users</a></li>
        <?php endif; ?>
        <li><a href="products_xml.php" class="<?= $current_page === 'products_xml.php' ? 'active' : '' ?>">XML Report</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
    <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</span>
</nav>
