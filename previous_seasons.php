<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$selected_season = $_GET['season'] ?? $pdo->query("SELECT MAX(lCurrentSeason) FROM league")->fetchColumn();
$stmt = $pdo->prepare("SELECT s.*, t.name as team_name 
    FROM standings s 
    JOIN teams t ON s.steamID = t.teamID 
    WHERE s.ssesaonID = ? 
    ORDER BY s.sWins DESC, s.sPointsFor DESC");
$stmt->execute([$selected_season]);
$standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT g.*, t1.name as home_team, t2.name as away_team 
    FROM games g 
    JOIN teams t1 ON g.homeTeamID = t1.teamID 
    JOIN teams t2 ON g.awayTeamID = t2.teamID 
    WHERE g.seasonID = ? 
    ORDER BY g.week");
$stmt->execute([$selected_season]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT DISTINCT seasonID FROM games ORDER BY seasonID DESC");
$seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previous Seasons - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Previous Seasons</h1>
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
        <select onchange="window.location.href='/cvpwfl/previous_seasons.php?season=' + this.value;">
            <?php foreach ($seasons as $season): ?>
                <option value="<?php echo $season['seasonID']; ?>" <?php echo $selected_season == $season['seasonID'] ? 'selected' : ''; ?>><?php echo $season['seasonID']; ?></option>
            <?php endforeach; ?>
        </select>
        <h2>Standings</h2>
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
                <?php foreach ($standings as $standing): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($standing['team_name']); ?></td>
                        <td><?php echo $standing['sWins']; ?></td>
                        <td><?php echo $standing['sLosses']; ?></td>
                        <td><?php echo $standing['sTies']; ?></td>
                        <td><?php echo $standing['sPointsFor']; ?></td>
                        <td><?php echo $standing['sPointsAgainst']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Games</h2>
        <table>
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Date and Time</th>
                    <th>Home Team</th>
                    <th>Home Score</th>
                    <th>Away Team</th>
                    <th>Away Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?php echo $game['week']; ?></td>
                        <td><?php echo $game['date'] ? (new DateTime($game['date']))->format('F j, Y g:i A') : 'TBD'; ?></td>
                        <td><?php echo htmlspecialchars($game['home_team']); ?></td>
                        <td><?php echo $game['homeScore']; ?></td>
                        <td><?php echo htmlspecialchars($game['away_team']); ?></td>
                        <td><?php echo $game['awayScore']; ?></td>
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