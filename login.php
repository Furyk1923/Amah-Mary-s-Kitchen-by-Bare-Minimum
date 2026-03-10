<?php
// login.php - authenticate users and start session
require_once 'config.php';

// if user is already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $mysqli->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $errors[] = 'Username and password are required.';
    } else {
        $stmt = $mysqli->prepare('SELECT user_id, password, role FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $hash, $role);
            $stmt->fetch();

            if (password_verify($password, $hash)) {
                // credentials OK
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Invalid username or password.';
            }
        } else {
            $errors[] = 'Invalid username or password.';
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
    <title>Login - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
<div class="auth-box">
<h2>🍳 Amah Mary's Kitchen</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
        <?php echo htmlspecialchars($e); ?><br>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="post" action="">
    <label>Username <input type="text" name="username" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit" class="btn btn-primary">Login</button>
</form>
<div class="link">Don't have an account? <a href="register.php">Register here</a></div>
</div>
</div>
</body>
</html>