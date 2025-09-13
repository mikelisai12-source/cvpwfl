<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.*, t.name as team_name FROM users u LEFT JOIN teams t ON u.teamID = t.teamID WHERE u.userID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$roles = [];
if ($user['admin']) $roles[] = 'Admin';
if ($user['headCoach']) $roles[] = 'Head Coach';
if ($user['asstCoach']) $roles[] = 'Assistant Coach';
if ($user['stats']) $roles[] = 'Stats';
$role_display = !empty($roles) ? implode(', ', $roles) : 'None';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? $user['email'];
    $phone = $_POST['phone'] !== '' ? $_POST['phone'] : null;
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $error = null;
    $success = null;

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email is already used by another user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND userID != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email is already in use by another user.";
        }
    }

    // Validate password if provided
    if ($password !== '') {
        if ($password !== $password_confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        }
    }

    if (!$error) {
        $pdo->beginTransaction();
        try {
            // Update email and phone
            $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE userID = ?");
            $stmt->execute([$email, $phone, $user_id]);

            // Update password if provided
            if ($password !== '') {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE userID = ?");
                $stmt->execute([$hashed_password, $user_id]);
            }

            $pdo->commit();
            $success = "Profile updated successfully.";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT u.*, t.name as team_name FROM users u LEFT JOIN teams t ON u.teamID = t.teamID WHERE u.userID = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
</head>
<body>
    <header>
        <h1>User Profile</h1>
        <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/nav.php'; ?>
        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                <form action="/cvpwfl/auth/logout.php" method="post" style="display: inline;">
                    <button type="submit" class="small-button">Logout</button>
                </form>
            <?php else: ?>
                <a href="/cvpwfl/auth/login.php" class="small-button">Login</a>
            <?php endif; ?>
        </div>
    </header>
    <main>
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php elseif (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <h2>Profile Information</h2>
        <form method="POST" class="profile-form">
            <div class="form-fields">
                <label>
                    First Name:
                    <span><?php echo htmlspecialchars($user['firstName']); ?></span>
                </label>
                <label>
                    Last Name:
                    <span><?php echo htmlspecialchars($user['lastName']); ?></span>
                </label>
                <label>
                    Email:
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </label>
                <label>
                    Phone:
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </label>
                <label>
                    Team:
                    <span><?php echo htmlspecialchars($user['team_name'] ?? 'None'); ?></span>
                </label>
                <label>
                    Roles:
                    <span><?php echo htmlspecialchars($role_display); ?></span>
                </label>
                <label>
                    Password:
                    <input type="password" name="password" placeholder="Enter new password (leave blank to keep current)">
                </label>
                <label>
                    Confirm Password:
                    <input type="password" name="password_confirm" placeholder="Confirm new password">
                </label>
            </div>
            <div class="centered-button">
                <button type="submit">Update Profile</button>
            </div>
        </form>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>

