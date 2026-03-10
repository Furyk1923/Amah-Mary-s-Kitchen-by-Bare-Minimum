<?php
// register.php - new user signup with role selection
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username       = trim($_POST['username']);
    $password       = trim($_POST['password']);
    $confirm_pass   = trim($_POST['confirm_password']);
    $full_name      = trim($_POST['full_name']);
    $email          = trim($_POST['email']);
    $phone          = trim($_POST['phone']);
    $role           = in_array($_POST['role'], ['Admin','Staff']) ? $_POST['role'] : 'Staff';

    if (empty($username) || empty($password) || empty($full_name)) {
        $errors[] = 'Full name, username, and password are required.';
    } elseif ($password !== $confirm_pass) {
        $errors[] = 'Password and Confirm Password do not match.';
    } else {
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
                header('Location: login.php?msg=registered');
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
    <title>Sign Up - Amah Mary's Kitchen</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<!-- Top Navbar -->
<nav class="auth-navbar">
    <div class="auth-navbar-brand">
        <span class="brand-icon"></span>
        <span>AMAH MARY'S KITCHEN</span>
    </div>
    <div class="auth-navbar-links">
        <a href="register.php" class="auth-nav-btn active">Sign up</a>
        <a href="login.php" class="auth-nav-btn">Log in</a>
    </div>
</nav>

<div class="signup-wrapper">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="max-width:620px;margin:0 auto 16px;">
        <?php foreach ($errors as $e): ?>
            <?= htmlspecialchars($e); ?><br>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="signup-form">

        <!-- Personal Information -->
        <fieldset class="form-section">
            <legend>Personal Information</legend>
            <div class="form-row">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-row">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-row">
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone">
            </div>
        </fieldset>

        <!-- Account Security -->
        <fieldset class="form-section">
            <legend>Account Security</legend>
            <div class="form-row">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-row">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-row">
                <label for="confirm_password">Confirm Pass:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
        </fieldset>

        <!-- Role / Access Level -->
        <div class="role-section">
            <span class="role-label">Role / Access Level:</span>
            <label class="radio-option">
                <input type="radio" name="role" value="Staff" checked>
                <span class="radio-text"><strong>Staff</strong> &nbsp;(Can View/add orders, cannot delete)</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="role" value="Admin">
                <span class="radio-text"><strong>Admin</strong> &nbsp;(Full access to reports &amp; settings)</span>
            </label>
        </div>

        <!-- Buttons -->
        <div class="signup-buttons">
            <button type="submit" class="btn-dark">Create</button>
            <a href="login.php" class="btn-dark btn-cancel">Cancel</a>
        </div>

        <p class="signup-note">*An email will be sent to the user for verification.</p>
    </form>
</div>

</body>
</html>