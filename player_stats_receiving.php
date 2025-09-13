<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$current_season = $pdo->query("SELECT MAX(lCurrentSeason) FROM league")->fetchColumn();
$stmt = $pdo->prepare("SELECT p.firstName, p.lastName, t.teamID, t.name as team_name, 
    SUM(pg.receptions) as receptions, SUM(pg.receiving_yards) as receiving_yards, SUM(pg.receiving_tds) as receiving_tds 
    FROM players p 
    JOIN playergamestats pg ON p.playerID = pg.pID 
    JOIN teams t ON p.teamID = t.teamID 
    WHERE p.seasonID = ? AND p.active = 1 
    GROUP BY p.playerID 
    ORDER BY receiving_yards DESC");
$stmt->execute([$current_season]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiving Stats - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Receiving Stats</h1>
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
                    <th>Receptions</th>
                    <th>Receiving Yards</th>
                    <th>Receiving TDs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $stat): ?>
                    <?php if ($stat['receptions'] == 0 && $stat['receiving_yards'] == 0 && $stat['receiving_tds'] == 0) continue; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['firstName'] . ' ' . $stat['lastName']); ?></td>
                        <td><img src="/cvpwfl/images/<?php echo $stat['teamID']; ?>.gif" width="25" height="25" border="0"></td>
                        <td><?php echo $stat['receptions']; ?></td>
                        <td><?php echo $stat['receiving_yards']; ?></td>
                        <td><?php echo $stat['receiving_tds']; ?></td>
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