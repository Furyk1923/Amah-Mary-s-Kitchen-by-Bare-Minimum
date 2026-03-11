<?php
require_once 'config.php';
require_once 'auth_check.php';

// Admin-only page
if ($_SESSION['role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$action  = $_GET['action'] ?? 'list';
$errors  = [];
$success = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Prevent self-delete
    if ($id === (int)$_SESSION['user_id']) {
        $errors[] = 'You cannot delete your own account.';
    } else {
        $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            header('Location: users.php?msg=deleted');
            exit;
        }
        $stmt->close();
    }
}

// ---------- CREATE / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $role      = in_array($_POST['role'], ['Admin','Staff']) ? $_POST['role'] : 'Staff';
    $password  = trim($_POST['password'] ?? '');

    if (empty($full_name) || empty($username)) {
        $errors[] = 'Full name and username are required.';
    }

    if (empty($errors)) {
        if ($action === 'edit' && isset($_POST['user_id'])) {
            $id = (int)$_POST['user_id'];
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET full_name=?, username=?, email=?, role=?, password=? WHERE user_id=?");
                $stmt->bind_param('sssssi', $full_name, $username, $email, $role, $hash, $id);
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET full_name=?, username=?, email=?, role=? WHERE user_id=?");
                $stmt->bind_param('ssssi', $full_name, $username, $email, $role, $id);
            }
            $stmt->execute();
            $stmt->close();
            header('Location: users.php?msg=updated');
            exit;
        } else {
            if (empty($password)) {
                $errors[] = 'Password is required for new users.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO users (full_name, username, password, role, email) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssss', $full_name, $username, $hash, $role, $email);
                if ($stmt->execute()) {
                    header('Location: users.php?msg=added');
                    exit;
                } else {
                    $errors[] = 'Failed to add user. Username may already exist.';
                }
                $stmt->close();
            }
        }
    }
}

// ---------- FETCH FOR EDIT ----------
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (isset($_GET['msg'])) {
    $map = ['added'=>'User added.','updated'=>'User updated.','deleted'=>'User deleted.'];
    $success = $map[$_GET['msg']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Users Management (Admin Only)</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e).'<br>'; ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <form method="post" action="users.php?action=<?= $action ?>" class="crud-form">
        <?php if ($edit_data): ?>
            <input type="hidden" name="user_id" value="<?= $edit_data['user_id'] ?>">
        <?php endif; ?>
        <label>Full Name
            <input type="text" name="full_name" value="<?= htmlspecialchars($edit_data['full_name'] ?? '') ?>" required>
        </label>
        <label>Username
            <input type="text" name="username" value="<?= htmlspecialchars($edit_data['username'] ?? '') ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>">
        </label>
        <label>Role
            <select name="role">
                <?php foreach (['Staff','Admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= (($edit_data['role'] ?? '') === $r) ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Password <?= $edit_data ? '(leave blank to keep current)' : '' ?>
            <input type="password" name="password" <?= $edit_data ? '' : 'required' ?>>
        </label>
        <button type="submit" class="btn btn-primary"><?= $edit_data ? 'Update' : 'Add' ?> User</button>
        <a href="users.php" class="btn btn-secondary">Cancel</a>
    </form>

    <?php else: ?>
    <a href="users.php?action=add" class="btn btn-add-new">+ Add User</a>
    <table>
        <thead>
        <tr>
            <th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $result = $mysqli->query("SELECT * FROM users ORDER BY user_id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['user_id'] ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td class="actions">
                <a href="users.php?action=edit&id=<?= $row['user_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                <?php if ($row['user_id'] !== (int)$_SESSION['user_id']): ?>
                    <a href="users.php?action=delete&id=<?= $row['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Delete</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
