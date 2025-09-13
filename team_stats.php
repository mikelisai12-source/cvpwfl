<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$current_season = $pdo->query("SELECT MAX(lCurrentSeason) FROM league")->fetchColumn();
$stmt = $pdo->prepare("SELECT s.*, t.name 
    FROM standings s 
    JOIN teams t ON s.steamID = t.teamID 
    WHERE s.ssesaonID = ?");
$stmt->execute([$current_season]);
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Stats - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Team Stats</h1>
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
        <h2>Team Standings</h2>
        <table>
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Wins</th>
                    <th>Losses</th>
                    <th>Ties</th>
                    <th>Points For</th>
                    <th>Points Against</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($standings as $team): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($team['name']); ?></td>
                        <td><?php echo $team['sWins']; ?></td>
                        <td><?php echo $team['sLosses']; ?></td>
                        <td><?php echo $team['sTies']; ?></td>
                        <td><?php echo $team['sPointsFor']; ?></td>
                        <td><?php echo $team['sPointsAgainst']; ?></td>
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