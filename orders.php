<?php
require_once 'config.php';
require_once 'auth_check.php';

$action  = $_GET['action'] ?? 'list';
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Check if order is NOT completed — restore stock
    $ostmt = $mysqli->prepare("SELECT status FROM orders WHERE order_id = ?");
    $ostmt->bind_param('i', $id);
    $ostmt->execute();
    $ostmt->bind_result($order_status);
    $ostmt->fetch();
    $ostmt->close();

    if ($order_status !== 'Completed') {
        // Restore stock for each item
        $items = $mysqli->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
        $items->bind_param('i', $id);
        $items->execute();
        $res = $items->get_result();
        while ($row = $res->fetch_assoc()) {
            $mysqli->query("UPDATE products SET stock_quantity = stock_quantity + {$row['quantity']} WHERE product_id = {$row['product_id']}");
            $mysqli->query("UPDATE products SET status = CASE WHEN stock_quantity <= 0 THEN 'Discontinued' WHEN stock_quantity <= 5 THEN 'Low Stock' ELSE 'Active' END WHERE product_id = {$row['product_id']}");
        }
        $items->close();
    }

    // Delete related order_details first
    $stmt = $mysqli->prepare("DELETE FROM order_details WHERE order_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    // Delete related delivery
    $stmt = $mysqli->prepare("DELETE FROM delivery WHERE order_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    // Delete order
    $stmt = $mysqli->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        header('Location: orders.php?msg=deleted');
        exit;
    }
    $stmt->close();
}

// ---------- CREATE NEW ORDER (full form) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $customer_name    = trim($_POST['customer_name'] ?? '');
    $customer_id      = null;
    $delivery_service = in_array($_POST['delivery_service'] ?? '', ['Lalamove','Grab','Pickup']) ? $_POST['delivery_service'] : 'Pickup';
    $delivery_date    = trim($_POST['delivery_date'] ?? '');
    if (!empty($delivery_date) && strtotime($delivery_date) < time()) {
        $errors[] = 'Delivery date/time cannot be in the past.';
    }
    $order_date       = date('Y-m-d');
    $status           = 'Pending';

    // Filter out empty item rows
    $item_products   = array_values(array_filter($_POST['item_product'] ?? [], function($v){ return $v !== ''; }));
    $item_quantities = $_POST['item_qty'] ?? [];

    if (empty($customer_name)) {
        $errors[] = 'Please enter or select a customer name.';
    } else {
        // Look up existing customer by name
        $cstmt = $mysqli->prepare("SELECT customer_id, address FROM customers WHERE full_name = ? LIMIT 1");
        $cstmt->bind_param('s', $customer_name);
        $cstmt->execute();
        $cresult = $cstmt->get_result();
        if ($crow = $cresult->fetch_assoc()) {
            $customer_id = (int)$crow['customer_id'];
        } else {
            // Auto-create new customer
            $ins = $mysqli->prepare("INSERT INTO customers (full_name) VALUES (?)");
            $ins->bind_param('s', $customer_name);
            $ins->execute();
            $customer_id = $mysqli->insert_id;
            $ins->close();
        }
        $cstmt->close();
    }
    if (count($item_products) === 0) {
        $errors[] = 'Please add at least one item.';
    }

    if (empty($errors)) {
        // Calculate total & validate stock
        $grand_total = 0;
        $items_data = [];
        foreach ($item_products as $i => $pid) {
            $pid = (int)$pid;
            $qty = max(1, (int)($item_quantities[$i] ?? 1));
            $pstmt = $mysqli->prepare("SELECT price, stock_quantity, product_name FROM products WHERE product_id = ?");
            $pstmt->bind_param('i', $pid);
            $pstmt->execute();
            $pstmt->bind_result($unit_price, $stock_qty, $pname);
            $pstmt->fetch();
            $pstmt->close();
            if ($qty > $stock_qty) {
                $errors[] = htmlspecialchars($pname) . ': only ' . $stock_qty . ' in stock, but you ordered ' . $qty . '.';
                continue;
            }
            $subtotal = $unit_price * $qty;
            $grand_total += $subtotal;
            $items_data[] = ['product_id' => $pid, 'quantity' => $qty, 'subtotal' => $subtotal];
        }
    }

    if (empty($errors)) {

        // Insert order
        $stmt = $mysqli->prepare("INSERT INTO orders (order_date, total_amount, status, customer_id) VALUES (?,?,?,?)");
        $stmt->bind_param('sdsi', $order_date, $grand_total, $status, $customer_id);
        $stmt->execute();
        $order_id = $mysqli->insert_id;
        $stmt->close();

        // Insert order details
        foreach ($items_data as $item) {
            $stmt = $mysqli->prepare("INSERT INTO order_details (order_id, product_id, quantity, subtotal) VALUES (?,?,?,?)");
            $stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['subtotal']);
            $stmt->execute();
            $stmt->close();
        }

        // Deduct stock
        foreach ($items_data as $item) {
            $mysqli->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE product_id = {$item['product_id']}");
            // Auto-update status based on remaining stock
            $mysqli->query("UPDATE products SET status = CASE WHEN stock_quantity <= 0 THEN 'Discontinued' WHEN stock_quantity <= 5 THEN 'Low Stock' ELSE 'Active' END WHERE product_id = {$item['product_id']}");
        }

        // Insert delivery
        $date_val = !empty($delivery_date) ? $delivery_date : null;
        $del_status = 'In-progress';
        $stmt = $mysqli->prepare("INSERT INTO delivery (order_id, delivery_service, delivery_status, delivery_date) VALUES (?,?,?,?)");
        $stmt->bind_param('isss', $order_id, $delivery_service, $del_status, $date_val);
        $stmt->execute();
        $stmt->close();

        header('Location: orders.php?msg=added');
        exit;
    }
}

// ---------- UPDATE ORDER (edit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    $id           = (int)$_POST['order_id'];
    $order_date   = trim($_POST['order_date']);
    $total_amount = (float)$_POST['total_amount'];
    $status       = in_array($_POST['status'], ['Pending','Completed','Cancelled']) ? $_POST['status'] : 'Pending';
    $customer_id  = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;

    $stmt = $mysqli->prepare("UPDATE orders SET order_date=?, total_amount=?, status=?, customer_id=? WHERE order_id=?");
    $stmt->bind_param('sdsii', $order_date, $total_amount, $status, $customer_id, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: orders.php?msg=updated');
    exit;
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

// Customers & Products for dropdowns
$customers    = $mysqli->query("SELECT customer_id, full_name, address FROM customers ORDER BY full_name");
$products_all = $mysqli->query("SELECT product_id, product_name, price, stock_quantity FROM products WHERE status != 'Discontinued' ORDER BY product_name");

// Flash message
if (isset($_GET['msg'])) {
    $map = ['added'=>'Order created successfully.','updated'=>'Order updated.','deleted'=>'Order deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}

// ---------- LIST: Filter & Search & Pagination ----------
$filter = $_GET['filter'] ?? 'All';
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$where = "1=1";
$params = [];
$types  = '';

if ($filter !== 'All' && in_array($filter, ['Pending','Completed','Cancelled'])) {
    $where .= " AND o.status = ?";
    $params[] = $filter;
    $types .= 's';
}
if (!empty($search)) {
    $where .= " AND (c.full_name LIKE ? OR o.order_id LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Count total
$count_sql = "SELECT COUNT(*) AS total FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id LEFT JOIN delivery d ON o.order_id = d.order_id WHERE $where";
$cstmt = $mysqli->prepare($count_sql);
if ($types) $cstmt->bind_param($types, ...$params);
$cstmt->execute();
$total_rows = $cstmt->get_result()->fetch_assoc()['total'];
$cstmt->close();
$total_pages = max(1, ceil($total_rows / $per_page));
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

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

<?php if ($action === 'add'): ?>
    <!-- ==================== CREATE NEW ORDER ==================== -->
    <h2 class="page-title">CREATE NEW ORDER</h2>

    <form method="post" action="orders.php?action=add" id="orderForm">
    <div class="order-create-grid">
        <!-- Top-left: Customer Details -->
        <div class="order-cell">
            <fieldset class="wf-section">
                <legend>1. CUSTOMER DETAILS</legend>
                <div class="wf-row">
                    <label class="wf-label">Customer:</label>
                    <input type="text" name="customer_name" id="customerInput" class="wf-input" list="customerList" placeholder="Select or type a New Name" required autocomplete="off">
                    <datalist id="customerList">
                        <?php while ($c = $customers->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($c['full_name']) ?>" data-address="<?= htmlspecialchars($c['address'] ?? '') ?>">
                        <?php endwhile; ?>
                    </datalist>
                </div>
                <div class="wf-row">
                    <label class="wf-label">Address:</label>
                    <input type="text" id="addressField" class="wf-input" placeholder="Auto-fills if existing user">
                </div>
            </fieldset>
        </div>

        <!-- Top-right: Delivery Details -->
        <div class="order-cell">
            <fieldset class="wf-section wf-delivery">
                <legend>3. DELIVERY DETAILS</legend>
                <div class="wf-row">
                    <label class="wf-label">Courier:</label>
                    <div class="radio-group-inline">
                        <label><input type="radio" name="delivery_service" value="Lalamove" checked> Lalamove</label>
                        <label><input type="radio" name="delivery_service" value="Grab"> Grab</label>
                        <label><input type="radio" name="delivery_service" value="Pickup"> Pickup</label>
                    </div>
                </div>
                <div class="wf-row">
                    <label class="wf-label">Date:</label>
                    <input type="datetime-local" name="delivery_date" class="wf-input" value="<?= date('Y-m-d\TH:i') ?>" min="<?= date('Y-m-d\TH:i') ?>">
                </div>
            </fieldset>
            <div class="grand-total">GRAND TOTAL: ₱ <span id="grandTotal">0.00</span></div>
        </div>

        <!-- Bottom-left: Order Items -->
        <div class="order-cell">
            <fieldset class="wf-section">
                <legend>2. ORDER ITEMS</legend>
                <table class="items-table" id="itemsTable">
                    <thead>
                        <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr>
                    </thead>
                    <tbody id="itemsBody">
                    </tbody>
                </table>
                <button type="button" class="btn-add-item" onclick="addItemRow()">Add Item</button>
            </fieldset>
        </div>

        <!-- Bottom-right: Buttons -->
        <div class="order-cell order-btn-cell">
            <div class="order-buttons">
                <a href="orders.php" class="btn-dark btn-cancel">CANCEL</a>
                <button type="submit" class="btn-dark btn-save">SAVE &amp; PROCESS</button>
            </div>
        </div>
    </div>
    </form>

    <!-- Products data for JS -->
    <?php
        $pdata = [];
        $products_all->data_seek(0);
        while ($p = $products_all->fetch_assoc()) {
            $pdata[$p["product_id"]] = [
                "name"  => $p["product_name"],
                "price" => (float)$p["price"],
                "stock" => (int)$p["stock_quantity"]
            ];
        }
    ?>
    <div id="productsData" data-products="<?= htmlspecialchars(json_encode($pdata), ENT_QUOTES) ?>" hidden></div>
    <script>
    var products = JSON.parse(document.getElementById('productsData').dataset.products);

    // Auto-fill address when picking from datalist
    document.getElementById('customerInput').addEventListener('input', function(){
        var val = this.value;
        var opts = document.querySelectorAll('#customerList option');
        var addr = '';
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].value === val) {
                addr = opts[i].dataset.address || '';
                break;
            }
        }
        document.getElementById('addressField').value = addr;
    });

    function addItemRow() {
        var tbody = document.getElementById('itemsBody');
        var idx = tbody.rows.length;
        var opts = '<option value="">-- Select --</option>';
        for (var id in products) {
            opts += '<option value="'+id+'">'+products[id].name+' ('+products[id].stock+' avail)</option>';
        }
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="item_product[]" onchange="calcRow(this)" required>'+opts+'</select></td>'+
            '<td><input type="number" name="item_qty[]" value="1" min="1" onchange="calcRow(this)" style="width:60px"></td>'+
            '<td class="row-price">₱0.00</td>'+
            '<td class="row-total">₱0.00</td>'+
            '<td><button type="button" onclick="removeRow(this)" class="btn-rm">&times;</button></td>';
        tbody.appendChild(tr);
    }

    function calcRow(el) {
        var tr = el.closest('tr');
        var sel = tr.querySelector('select');
        var qtyInput = tr.querySelector('input[type=number]');
        var pid = sel.value;
        if (pid && products[pid]) {
            qtyInput.max = products[pid].stock;
            if (parseInt(qtyInput.value) > products[pid].stock) {
                qtyInput.value = products[pid].stock;
            }
        }
        var qty = parseInt(tr.querySelector('input[type=number]').value) || 1;
        var pid = sel.value;
        var price = pid && products[pid] ? products[pid].price : 0;
        tr.querySelector('.row-price').textContent = '₱' + price.toFixed(2);
        tr.querySelector('.row-total').textContent = '₱' + (price * qty).toFixed(2);
        calcGrand();
    }

    function calcGrand() {
        var total = 0;
        document.querySelectorAll('.row-total').forEach(function(td){
            total += parseFloat(td.textContent.replace('₱','')) || 0;
        });
        document.getElementById('grandTotal').textContent = total.toFixed(2);
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        calcGrand();
    }

    // Start with one row
    addItemRow();
    </script>

<?php elseif ($action === 'edit'): ?>
    <!-- ==================== EDIT ORDER ==================== -->
    <h2 class="page-title">EDIT ORDER #<?= $edit_data['order_id'] ?></h2>
    <form method="post" action="orders.php?action=edit" class="crud-form">
        <input type="hidden" name="order_id" value="<?= $edit_data['order_id'] ?>">
        <label>Order Date
            <input type="date" name="order_date" value="<?= htmlspecialchars($edit_data['order_date']) ?>" required>
        </label>
        <label>Total Amount (₱)
            <input type="number" name="total_amount" step="0.01" min="0" value="<?= htmlspecialchars($edit_data['total_amount']) ?>" required>
        </label>
        <label>Status
            <select name="status">
                <?php foreach (['Pending','Completed','Cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($edit_data['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Customer
            <select name="customer_id">
                <option value="">-- Select --</option>
                <?php $customers->data_seek(0); while ($c = $customers->fetch_assoc()): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($edit_data['customer_id'] == $c['customer_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <button type="submit" class="btn-dark btn-save">Update Order</button>
        <a href="orders.php" class="btn-dark btn-cancel" style="margin-left:10px;">Cancel</a>
    </form>

<?php else: ?>
    <!-- ==================== ORDER LIST ==================== -->
    <h2 class="page-title">ORDER MANAGEMENT</h2>

    <div class="order-toolbar">
        <a href="orders.php?action=add" class="btn-add-new">+ Create New Order</a>
        <form method="get" action="orders.php" class="search-bar">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search by Customer or Order ID">
            <?php if ($filter !== 'All'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
        </form>
    </div>

    <div class="filter-bar">
        Filter:
        <?php foreach (['All','Pending','Completed','Cancelled'] as $f): ?>
            <a href="orders.php?filter=<?= $f ?>&search=<?= urlencode($search) ?>"
               class="filter-chip <?= $filter === $f ? 'active' : '' ?>">[<?= $f ?>]</a>
        <?php endforeach; ?>
    </div>

    <table>
        <thead>
        <tr>
            <th>ID</th><th>Customer</th><th>Courier</th><th>Total</th><th>Status</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $list_sql = "SELECT o.*, c.full_name AS customer_name, d.delivery_service
                     FROM orders o
                     LEFT JOIN customers c ON o.customer_id = c.customer_id
                     LEFT JOIN delivery d ON o.order_id = d.order_id
                     WHERE $where
                     ORDER BY o.order_id DESC LIMIT ? OFFSET ?";
        $params2 = array_merge($params, [$per_page, $offset]);
        $types2  = $types . 'ii';
        $lstmt = $mysqli->prepare($list_sql);
        $lstmt->bind_param($types2, ...$params2);
        $lstmt->execute();
        $result = $lstmt->get_result();
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['order_id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['delivery_service'] ?? '-') ?></td>
            <td>₱ <?= number_format($row['total_amount'], 2) ?></td>
            <td><span class="status-badge status-<?= strtolower($row['status']) ?>"><?= strtoupper($row['status']) ?></span></td>
            <td class="actions">
                <a href="orders.php?action=edit&id=<?= $row['order_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="orders.php?action=delete&id=<?= $row['order_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this order and its items/delivery?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; $lstmt->close(); ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="orders.php?page=<?= $page-1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>">&lt; Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="orders.php?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>"
               class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="orders.php?page=<?= $page+1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>">Next &gt;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<?php endif; ?>
</div>

</body>
</html>
