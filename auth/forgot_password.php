<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE userID = ?");
        $stmt->execute([$token, $expiry, $user['userID']]);
        $reset_link = "https://$_SERVER[HTTP_HOST]/cvpwfl/auth/reset_password.php?token=$token";
        $body = "<p>Dear " . htmlspecialchars($user['firstName']) . ",</p>
                 <p>Please click the following link to reset your password: <a href='$reset_link'>Reset Password</a></p>
                 <p>This link will expire in 1 hour.</p>";
        if (send_email($user['email'], "Password Reset Request", $body)) {
            $success = "A password reset link has been sent to your email.";
        } else {
            $error = "Failed to send reset email. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Forgot Password</h1>
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/nav.php'; ?>
    </header>
    <main>
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php elseif (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <button type="submit">Send Reset Link</button>
        </form>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>