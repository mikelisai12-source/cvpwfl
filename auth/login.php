<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['userID'];
        $_SESSION['first_name'] = $user['firstName'];
        $_SESSION['teamID'] = $user['teamID'];
        $_SESSION['admin'] = $user['admin'];
        $_SESSION['headCoach'] = $user['headCoach'];
        $_SESSION['asstCoach'] = $user['asstCoach'];
        $_SESSION['stats'] = $user['stats'];
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE userID = ?");
        $stmt->execute([$user['userID']]);
        header('Location: /cvpwfl/index.php');
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/nav.php'; ?>
    </header>
    <main>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" style="width: 300px; margin: 0 auto;">
            <h1>Login</h1>
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
            <p><a href="/cvpwfl/auth/forgot_password.php">Forgot Password?</a></p>
        </form>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>