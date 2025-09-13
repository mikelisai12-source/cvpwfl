<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    $error = "Invalid or missing token.";
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = "Invalid or expired token.";
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $password = $_POST['password'];
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE userID = ?");
        $stmt->execute([$hashed_password, $user['userID']]);
        $success = "Password reset successfully. Please <a href='/cvpwfl/auth/login.php'>login</a>.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Reset Password</h1>
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/nav.php'; ?>
    </header>
    <main>
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php elseif (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php else: ?>
            <form method="POST">
                <label for="password">New Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>