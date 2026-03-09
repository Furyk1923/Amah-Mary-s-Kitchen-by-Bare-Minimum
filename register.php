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
    <title>Register</title>
</head>
<body>
<h2>Register</h2>
<?php if (!empty($errors)): ?>
    <ul>
        <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<form method="post" action="">
    <label>Full Name: <input type="text" name="full_name" required></label><br>
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Email: <input type="email" name="email"></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <label>Role: 
        <select name="role">
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
        </select>
    </label><br>
    <button type="submit">Register</button>
</form>
</body>
</html>