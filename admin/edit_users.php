<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['admin']) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $teamID = $_POST['teamID'] ?? 0;
    $admin = isset($_POST['admin']) ? 1 : 0;
    $headCoach = isset($_POST['headCoach']) ? 1 : 0;
    $asstCoach = isset($_POST['asstCoach']) ? 1 : 0;
    $stats = isset($_POST['stats']) ? 1 : 0;
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT INTO users (active, firstName, lastName, email, phone, teamID, admin, headCoach, asstCoach, stats, reset_token, reset_expiry) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() + INTERVAL 60 HOUR)");
    $stmt->execute([$firstName, $lastName, $email, $phone, $teamID, $admin, $headCoach, $asstCoach, $stats, $token]);
    $reset_link = "https://$_SERVER[HTTP_HOST]/cvpwfl/auth/reset_password.php?token=$token";
    $body = "<p>Dear $firstName,</p>
             <p>You have been added to the Connecticut Valley Pee Wee Football League system. Please set your password using this link: <a href='$reset_link'>Set Password</a></p>
             <p>This link will expire in 12 hours.</p>";
    send_email($email, "Welcome to Pee Wee Football", $body);
    $success = "User added successfully. A password setup link has been sent.";
}

$stmt = $pdo->query("SELECT u.*, t.name as team_name FROM users u LEFT JOIN teams t ON u.teamID = t.teamID");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT teamID, name FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Users - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
</head>
<body>
    <header>
        <h1>Edit Users</h1>
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
        <?php endif; ?>
        <h2>Add New User</h2>
        <form method="POST" class="add-user-form">
            <input type="hidden" name="action" value="add_user">
            <div class="form-fields">
                <label>First Name: <input type="text" name="firstName" required></label>
                <label>Last Name: <input type="text" name="lastName" required></label>
                <label>Email: <input type="email" name="email" required></label>
                <label>Phone: <input type="text" name="phone"></label>
                <label>Team: 
                    <select name="teamID">
                        <option value="0">None</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['teamID']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="checkbox-row">
                <label><input type="checkbox" name="admin"> Admin</label>
                <label><input type="checkbox" name="headCoach"> Head Coach</label>
                <label><input type="checkbox" name="asstCoach"> Assistant Coach</label>
                <label><input type="checkbox" name="stats"> Stats</label>
            </div>
            <div class="centered-button">
                <button type="submit">Add User</button>
            </div>
        </form>
        <h2>Existing Users</h2>
        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Team</th>
                    <th>Admin</th>
                    <th>Head Coach</th>
                    <th>Asst Coach</th>
                    <th>Stats</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><input type="text" class="modal-input" data-user-id="<?php echo $user['userID']; ?>" data-field="firstName" value="<?php echo htmlspecialchars($user['firstName']); ?>"></td>
                        <td><input type="text" class="modal-input" data-user-id="<?php echo $user['userID']; ?>" data-field="lastName" value="<?php echo htmlspecialchars($user['lastName']); ?>"></td>
                        <td><input type="email" class="modal-input" data-user-id="<?php echo $user['userID']; ?>" data-field="email" value="<?php echo htmlspecialchars($user['email']); ?>"></td>
                        <td><input type="text" class="modal-input" data-user-id="<?php echo $user['userID']; ?>" data-field="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></td>
                        <td>
                            <select class="modal-input" data-user-id="<?php echo $user['userID']; ?>" data-field="teamID">
                                <option value="0">None</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['teamID']; ?>" <?php echo $user['teamID'] == $team['teamID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="checkbox" class="modal-input role-checkbox" data-user-id="<?php echo $user['userID']; ?>" data-field="admin" <?php echo $user['admin'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" class="modal-input role-checkbox" data-user-id="<?php echo $user['userID']; ?>" data-field="headCoach" <?php echo $user['headCoach'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" class="modal-input role-checkbox" data-user-id="<?php echo $user['userID']; ?>" data-field="asstCoach" <?php echo $user['asstCoach'] ? 'checked' : ''; ?>></td>
                        <td><input type="checkbox" class="modal-input role-checkbox" data-user-id="<?php echo $user['userID']; ?>" data-field="stats" <?php echo $user['stats'] ? 'checked' : ''; ?>></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>