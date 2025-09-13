<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$current_season = $pdo->query("SELECT MAX(lCurrentSeason) FROM league")->fetchColumn();
$stmt = $pdo->prepare("SELECT p.firstName, p.lastName, t.teamID, t.name as team_name, 
    SUM(pg.tackles_assisted) as tackles_assisted, SUM(pg.tackles_unassisted) as tackles_unassisted, 
    SUM(pg.tackles_assisted) + SUM(pg.tackles_unassisted) as total_tackles,
    SUM(pg.tackles_for_loss) as tackles_for_loss, SUM(pg.sacks) as sacks, 
    SUM(pg.fumbles_forced) as fumbles_forced, SUM(pg.fumbles_recovered) as fumbles_recovered, 
    SUM(pg.fumbles_td) as fumbles_td, SUM(pg.interceptions) as interceptions, 
    SUM(pg.interception_yards) as interception_yards, SUM(pg.interception_tds) as interception_tds 
    FROM players p 
    JOIN playergamestats pg ON p.playerID = pg.pID 
    JOIN teams t ON p.teamID = t.teamID 
    WHERE p.seasonID = ? AND p.active = 1 
    GROUP BY p.playerID 
    ORDER BY total_tackles DESC");
$stmt->execute([$current_season]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defense Stats - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Defense Stats</h1>
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
        <table class="table_stats">
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Team</th>
                    <th>Total Tackles</th>
                    <th>Tackles Ast</th>
                    <th>Tackles Solo</th>
                    <th>Sacks</th>
                    <th>Fumbles Forced</th>
                    <th>Fumbles Rec</th>
                    <th>Fumble TDs</th>
                    <th>Int</th>
                    <th>Int Yards</th>
                    <th>Int TDs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $stat): ?>
                    <?php if ($stat['tackles_assisted'] == 0 && $stat['tackles_unassisted'] == 0 && $stat['sacks'] == 0 && $stat['fumbles_forced'] == 0 && $stat['fumbles_recovered'] == 0 && $stat['fumbles_td'] == 0 && $stat['interceptions'] == 0 && $stat['interception_yards'] == 0 && $stat['interception_tds'] == 0) continue; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['firstName'] . ' ' . $stat['lastName']); ?></td>
                        <td><img src="/cvpwfl/images/<?php echo $stat['teamID']; ?>.gif" width="20" height="20" border="0"></td>
                        <td><?php echo $stat['total_tackles']; ?></td>
                        <td><?php echo $stat['tackles_assisted']; ?></td>
                        <td><?php echo $stat['tackles_unassisted']; ?></td>
                        <td><?php echo $stat['sacks']; ?></td>
                        <td><?php echo $stat['fumbles_forced']; ?></td>
                        <td><?php echo $stat['fumbles_recovered']; ?></td>
                        <td><?php echo $stat['fumbles_td']; ?></td>
                        <td><?php echo $stat['interceptions']; ?></td>
                        <td><?php echo $stat['interception_yards']; ?></td>
                        <td><?php echo $stat['interception_tds']; ?></td>
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