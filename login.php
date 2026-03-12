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

            if (password_verify($password, $hash ?? '')) {
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
<body class="auth-page">

<nav class="auth-navbar">
    <div class="auth-navbar-links">
        <a href="register.php" class="auth-nav-btn">Sign up</a>
        <a href="login.php" class="auth-nav-btn active">Log in</a>
    </div>
</nav>

<div class="login-wrapper">
    <div class="login-card">
        <img src="logo.png" alt="Amah Mary's Kitchen Logo" class="login-logo">
        <h2 class="login-title">Welcome Back</h2>
        <p class="login-subtitle">Please enter your details to sign in.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <?= htmlspecialchars($e); ?><br>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="alert alert-success">Account created! Please log in.</div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="login-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="e.g. Admin" required>
            </div>
            
            <div class="login-field">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="toggle-pw" onclick="togglePassword()" title="Toggle Password Visibility">👁</button>
                </div>
            </div>
            
            <a href="#" class="forgot-link">Forgot Password?</a>
            
            <button type="submit" class="btn btn-login">LOG IN</button>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    var pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>