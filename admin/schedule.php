<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['admin']) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT lCurrentSeason FROM league WHERE lCurrentSeason = (SELECT MAX(lCurrentSeason) FROM league)");
$current_season = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_schedule') {
    $pdo->beginTransaction();
    try {
        // Find next gameID
        $stmt = $pdo->query("SELECT MAX(gameID) + 1 AS next_game_id FROM games");
        $next_game_id = $stmt->fetchColumn() ?: 1;

        $inserted = false; // Track if anything was added

        for ($week = 1; $week <= 7; $week++) {
            for ($game = 1; $game <= 2; $game++) {
                $away_key = "week{$week}_game{$game}_away";
                $home_key = "week{$week}_game{$game}_home";
                $date_key = "week{$week}_game{$game}_date";

                if (isset($_POST[$away_key]) && isset($_POST[$home_key]) && isset($_POST[$date_key])) {
                    $away_team = trim($_POST[$away_key]);
                    $home_team = trim($_POST[$home_key]);
                    $date = trim($_POST[$date_key]);

                    if ($away_team && $home_team && $date && $away_team != $home_team) {
                        // Insert two rows for the game
                        $stmt = $pdo->prepare("INSERT INTO games (gameID, seasonID, week, date, teamID, home) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$next_game_id, $current_season, $week, $date, $away_team, 0]);  // Away
                        $stmt->execute([$next_game_id, $current_season, $week, $date, $home_team, 1]);  // Home

                        // Add player stats rows for both teams
                        $stmt = $pdo->prepare("SELECT playerID FROM players WHERE seasonID = ? AND teamID IN (?, ?) AND active = 1");
                        $stmt->execute([$current_season, $away_team, $home_team]);
                        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($players as $player) {
                            $stmt = $pdo->prepare("INSERT INTO playergamestats (gID, pID) VALUES (?, ?)");
                            $stmt->execute([$next_game_id, $player['playerID']]);
                        }

                        $next_game_id++;
                        $inserted = true;
                    } // else skip silently
                }
            }
        }

        $pdo->commit();
        $success = $inserted ? "New games added successfully." : "No new games to add.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to save schedule: " . $e->getMessage();
    }
}

// Fetch current schedule (group by gameID to avoid duplicates)
$stmt = $pdo->prepare("SELECT g.gameID, g.seasonID, g.week, g.date, 
    MAX(CASE WHEN g.home = 1 THEN t.name ELSE NULL END) as home_team, 
    MAX(CASE WHEN g.home = 0 THEN t.name ELSE NULL END) as away_team,
    MAX(CASE WHEN g.home = 1 THEN g.score_final ELSE NULL END) as home_score_final,
    MAX(CASE WHEN g.home = 0 THEN g.score_final ELSE NULL END) as away_score_final,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr1 ELSE NULL END) as home_qtr1,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr1 ELSE NULL END) as away_qtr1,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr2 ELSE NULL END) as home_qtr2,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr2 ELSE NULL END) as away_qtr2,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr3 ELSE NULL END) as home_qtr3,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr3 ELSE NULL END) as away_qtr3,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr4 ELSE NULL END) as home_qtr4,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr4 ELSE NULL END) as away_qtr4,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr5 ELSE NULL END) as home_qtr5,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr5 ELSE NULL END) as away_qtr5
    FROM games g 
    JOIN teams t ON g.teamID = t.teamID 
    WHERE g.seasonID = ? 
    GROUP BY g.gameID, g.seasonID, g.week, g.date
    ORDER BY g.week, g.date, g.gameID");
$stmt->execute([$current_season]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

$schedule = [];
foreach ($games as $game) {
    $week = $game['week'];
    if (!isset($schedule[$week])) {
        $schedule[$week] = [];
    }
    $schedule[$week][] = $game;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <h1>Schedule</h1>
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
    <main style="text-align: center;">
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php elseif (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" style="max-width: 600px; margin: 0 auto;">
            <input type="hidden" name="action" value="save_schedule">
            <button type="submit" style="width: 300px; display: block; margin: 0 auto 20px;">Save Schedule</button>
            <?php for ($week = 1; $week <= 7; $week++): ?>
                <h2>Week <?php echo $week; ?><?php if ($week == 7) echo ' (Playoffs)'; ?></h2>
                <?php
                $existing_games = $schedule[$week] ?? [];
                // Sort existing games by date (ascending)
                usort($existing_games, function($a, $b) {
                    $dateA = strtotime($a['date']);
                    $dateB = strtotime($b['date']);
                    if ($dateA == $dateB) {
                        return $a['gameID'] <=> $b['gameID'];
                    }
                    return $dateA <=> $dateB;
                });
                $max_games = ($week == 7) ? 1 : 2; // Only 1 game for week 7
                ?>
                <?php for ($game = 1; $game <= $max_games; $game++): ?>
                    <?php if (isset($existing_games[$game - 1])): ?>
                        <?php $g = $existing_games[$game - 1]; ?>
                        <div style="margin-left: 30px;"><?php echo $g['date'] ? (new DateTime($g['date']))->format('Y-m-d H:i') : 'TBD'; ?></div>
                        <div style="margin-left: 60px;"><?php echo htmlspecialchars($g['away_team']); ?> at <?php echo htmlspecialchars($g['home_team']); ?></div>
                    <?php else: ?>
                        <div style="margin-left: 30px;">
                            <input type="datetime-local" name="week<?php echo $week; ?>_game<?php echo $game; ?>_date">
                        </div>
                        <div style="margin-left: 60px;">
                            <select name="week<?php echo $week; ?>_game<?php echo $game; ?>_away" style="width: 200px;">
                                <option value="">Select Away Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['teamID']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span>at</span>
                            <select name="week<?php echo $week; ?>_game<?php echo $game; ?>_home" style="width: 200px;">
                                <option value="">Select Home Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['teamID']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
            <?php endfor; ?>
        </form>
        <h2>Current Schedule</h2>
        <table>
            <thead>
                <tr>
                    <th>Week</th>
                    <th>Date and Time</th>
                    <th>Away Team</th>
                    <th>Home Team</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?php echo $game['week']; ?></td>
                        <td><?php echo $game['date'] ? (new DateTime($game['date']))->format('F j, Y g:i A') : 'TBD'; ?></td>
                        <td><?php echo htmlspecialchars($game['away_team']); ?></td>
                        <td><?php echo htmlspecialchars($game['home_team']); ?></td>
                        <td><?php echo $game['away_score_final'] . ' - ' . $game['home_score_final'] . ' (Q1: ' . $game['away_qtr1'] . '-' . $game['home_qtr1'] . ', Q2: ' . $game['away_qtr2'] . '-' . $game['home_qtr2'] . ', Q3: ' . $game['away_qtr3'] . '-' . $game['home_qtr3'] . ', Q4: ' . $game['away_qtr4'] . '-' . $game['home_qtr4'] . ', OT: ' . $game['away_qtr5'] . '-' . $game['home_qtr5'] . ')'; ?></td>
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