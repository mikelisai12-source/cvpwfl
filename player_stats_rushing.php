<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$current_season = $pdo->query("SELECT MAX(lCurrentSeason) FROM league")->fetchColumn();
$stmt = $pdo->prepare("SELECT p.firstName, p.lastName, t.teamID, t.name as team_name, 
    SUM(pg.rushes) as rushes, SUM(pg.rush_yards) as rush_yards, SUM(pg.rush_tds) as rush_tds 
    FROM players p 
    JOIN playergamestats pg ON p.playerID = pg.pID 
    JOIN teams t ON p.teamID = t.teamID 
    WHERE p.seasonID = ? AND p.active = 1 
    GROUP BY p.playerID 
    ORDER BY rush_yards DESC");
$stmt->execute([$current_season]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rushing Stats - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Rushing Stats</h1>
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
        <table>
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Team</th>
                    <th>Rushes</th>
                    <th>Rush Yards</th>
                    <th>Rush TDs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $stat): ?>
                    <?php if ($stat['rushes'] == 0 && $stat['rush_yards'] == 0 && $stat['rush_tds'] == 0) continue; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['firstName'] . ' ' . $stat['lastName']); ?></td>
                        <td><img src="/cvpwfl/images/<?php echo $stat['teamID']; ?>.gif" width="25" height="25" border="0"></td>
                        <td><?php echo $stat['rushes']; ?></td>
                        <td><?php echo $stat['rush_yards']; ?></td>
                        <td><?php echo $stat['rush_tds']; ?></td>
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