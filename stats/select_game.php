<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || (!$_SESSION['stats'] && !$_SESSION['admin'])) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$isAdmin = $_SESSION['admin'];

// Fetch games that are starting within the next 24 hours or past games that are not complete (no winner set yet)
$stmt = $pdo->prepare("SELECT g.gameID, g.date, 
    GROUP_CONCAT(DISTINCT t.name SEPARATOR ' at ') as matchup
    FROM games g 
    JOIN teams t ON g.teamID = t.teamID 
    WHERE g.gameID IN (
        SELECT gameID FROM games GROUP BY gameID HAVING MAX(winner) = 0
    )
    AND (
        (g.date >= NOW() AND g.date <= DATE_ADD(NOW(), INTERVAL 72 HOUR))
        OR g.date < NOW()
    )
    GROUP BY g.gameID
    ORDER BY g.date");
$stmt->execute();
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Game - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Select Game for Stats</h1>
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
        <?php if ($isAdmin): ?>
            <a href="/cvpwfl/stats/edit_past_games.php" class="link-button">Edit Past Games</a>
        <?php endif; ?>
        <h2>Available Games</h2>
        <ul>
            <?php foreach ($games as $game): ?>
                <li style="padding: 20px 0;"><?php echo htmlspecialchars($game['matchup']) . ' on ' . (new DateTime($game['date']))->format('g:i A'); ?> <a href="/cvpwfl/stats/stats_collection.php?game_id=<?php echo $game['gameID']; ?>" class="link-button">Select this Game</a></li>
            <?php endforeach; ?>
        </ul>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>