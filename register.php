<?php
// register.php - new user signup with role selection
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $mysqli->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $full_name = $mysqli->real_escape_string(trim($_POST['full_name']));
    $email = $mysqli->real_escape_string(trim($_POST['email']));
    $role     = in_array($_POST['role'], ['Admin','Staff']) ? $_POST['role'] : 'Staff';

    if (empty($username) || empty($password) || empty($full_name)) {
        $errors[] = 'Username, full name, and password are required.';
    } else {
        // check if username exists
        $stmt = $mysqli->prepare('SELECT user_id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Username already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $mysqli->prepare('INSERT INTO users (full_name, username, password, role, email) VALUES (?, ?, ?, ?, ?)');
            $insert->bind_param('sssss', $full_name, $username, $hash, $role, $email);
            if ($insert->execute()) {
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Registration failed, please try again.';
            }
            $insert->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
<div class="auth-box">
<h2>🍳 Register Account</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
        <?php echo htmlspecialchars($e); ?><br>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="post" action="">
    <label>Full Name <input type="text" name="full_name" required></label>
    <label>Username <input type="text" name="username" required></label>
    <label>Email <input type="email" name="email"></label>
    <label>Password <input type="password" name="password" required></label>
    <label>Role
        <select name="role">
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
        </select>
    </label>
    <button type="submit" class="btn btn-primary">Register</button>
</form>
<div class="link">Already have an account? <a href="login.php">Login here</a></div>
</div>
</div>
</body>
</html>