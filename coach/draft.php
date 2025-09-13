<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !($_SESSION['admin'] || $_SESSION['headCoach'] || $_SESSION['asstCoach'])) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM league WHERE lCurrentSeason = (SELECT MAX(lCurrentSeason) FROM league)");
$league = $stmt->fetch(PDO::FETCH_ASSOC);
if ($league['seasonMode'] != 'offseason') {
    header('Location: /cvpwfl/index.php');
    exit;
}

$draft_started = $league['draft_team'] > 0;
$current_team_name = 'Unknown';
if ($draft_started && $league['draft_team'] > 0) {
    $stmt = $pdo->prepare("SELECT name FROM teams WHERE teamID = ?");
    $stmt->execute([$league['draft_team']]);
    $current_team_name = $stmt->fetchColumn() ?: 'Unknown';
}

$stmt = $pdo->prepare("SELECT dp.*, t.name, p.firstName, p.lastName, p.grade, p.weight, p.height, p.fortySpeed 
    FROM draft_picks dp 
    JOIN teams t ON dp.dpteamID = t.teamID 
    LEFT JOIN players p ON dp.playerID = p.playerID AND p.seasonID = dp.dpseasonID 
    WHERE dp.dpseasonID = ? 
    ORDER BY dp.dpround, dpslot");
$stmt->execute([$league['lCurrentSeason']]);
$draft_picks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teams
$stmt = $pdo->query("SELECT * FROM teams ORDER BY teamID");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sort teams by draft order if draft started
$draft_order = $league['draft_order'] ? explode(',', $league['draft_order']) : [];
if ($draft_order) {
    usort($teams, function($a, $b) use ($draft_order) {
        $pos_a = array_search($a['teamID'], $draft_order);
        $pos_b = array_search($b['teamID'], $draft_order);
        if ($pos_a === false) $pos_a = PHP_INT_MAX;
        if ($pos_b === false) $pos_b = PHP_INT_MAX;
        return $pos_a <=> $pos_b;
    });
}

// Calculate per-team stats
$team_stats = [];
foreach ($teams as $team) {
    $team_id = $team['teamID'];

    // Returning players (confirmed_count)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE seasonID = ? AND teamID = ? AND grade BETWEEN 4 AND 6 AND active = 1 AND draft_season = 0");
    $stmt->execute([$league['lCurrentSeason'], $team_id]);
    $returning = $stmt->fetchColumn();

    // Total draft picks (number_of_picks)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM draft_picks WHERE dpseasonID = ? AND dpteamID = ?");
    $stmt->execute([$league['lCurrentSeason'], $team_id]);
    $total_picks = $stmt->fetchColumn();

    // Picks made
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM draft_picks WHERE dpseasonID = ? AND dpteamID = ? AND dpmade = 1");
    $stmt->execute([$league['lCurrentSeason'], $team_id]);
    $picks_made = $stmt->fetchColumn();

    // Roster target
    $roster_target = $draft_started ? $returning + $total_picks : '-';

    // Actual roster
    $actual_roster = $draft_started ? $returning + $picks_made : '-';

    $team_stats[] = [
        'name' => $team['name'],
        'returning' => $returning,
        'total_picks' => $total_picks,
        'roster_target' => $roster_target,
        'picks_made' => $picks_made,
        'actual_roster' => $actual_roster
    ];
}

// Calculate dynamic pre-draft stats if draft has not started
if (!$draft_started) {
    $confirmed = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
    $stmt = $pdo->prepare("SELECT teamID, COUNT(*) as confirmed_count FROM players WHERE seasonID = ? AND grade BETWEEN 4 AND 6 AND active = 1 AND draft_season = 0 GROUP BY teamID");
    $stmt->execute([$league['lCurrentSeason']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $confirmed[$row['teamID']] = $row['confirmed_count'];
    }
    $total_returning_dynamic = array_sum($confirmed);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE seasonID = ? AND grade IN (4,5,6) AND active = 1 AND teamID = 0");
    $stmt->execute([$league['lCurrentSeason']]);
    $incoming_rookies_dynamic = $stmt->fetchColumn();

    $total_league_players_dynamic = $total_returning_dynamic + $incoming_rookies_dynamic;
    $players_per_team_dynamic = number_format($total_league_players_dynamic / 4, 1);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
</head>
<body>
    <header>
        <h1>Draft</h1>
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
        <div class="link-buttons">
            <a href="/cvpwfl/coach/available_rookies.php" class="link-button draft-link-button">Available Rookies</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1 || isset($_SESSION['headCoach']) && $_SESSION['headCoach'] == 1 || isset($_SESSION['asstCoach']) && $_SESSION['asstCoach'] == 1): ?>
                <a href="/cvpwfl/coach/draft.php" class="link-button draft-link-button">Draft</a>
                <a href="/cvpwfl/coach/edit_players.php" class="link-button draft-link-button">Edit Roster</a>
            <?php endif; ?>
        </div>
        <?php if ($league['curRd'] == 13): ?>
            <p class="centered" style="color: green; font-weight: bold;">The draft is complete!</p>
        <?php elseif (!$draft_started): ?>
            <p class="centered" style="color: red;">The draft has not started yet. We're waiting for an admin to set the draft order.</p>
        <?php else: ?>
            <p class="centered">The <?php echo htmlspecialchars($current_team_name); ?> are on the clock and have been up for <span id="draft-timer" data-draft-time="<?php echo $league['draft_time'] ? htmlspecialchars((new DateTime($league['draft_time'], new DateTimeZone('America/New_York')))->format('c')) : ''; ?>"></span>.</p>
        <?php endif; ?>
        <h2 class="centered">Pre-Draft League Stats</h2>
        <?php if ($draft_started): ?>
            <ul style="width: fit-content; margin: 0 auto;">
                <li>Total returning players: <?php echo $league['total_returning']; ?></li>
                <li>Incoming rookies: <?php echo $league['incoming_rookies']; ?></li>
                <li>Total league players: <?php echo $league['total_league_players']; ?></li>
                <li>Players per team: <?php echo number_format($league['total_league_players'] / 4, 1); ?></li>
            </ul>
        <?php else: ?>
            <ul style="width: fit-content; margin: 0 auto;">
                <li>Total returning players: <?php echo $total_returning_dynamic; ?></li>
                <li>Incoming rookies: <?php echo $incoming_rookies_dynamic; ?></li>
                <li>Total league players: <?php echo $total_league_players_dynamic; ?></li>
                <li>Players per team: <?php echo $players_per_team_dynamic; ?></li>
            </ul>
        <?php endif; ?>
        <h2 class="centered">Team Draft Summary</h2>
        <table class="draft-table">
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Returning Players</th>
                    <th>Total Draft Picks</th>
                    <th>Picks Made</th>
                    <th>Target Roster</th>
                    <th>Actual Roster</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($team_stats as $stats): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stats['name']); ?></td>
                        <td><?php echo $stats['returning']; ?></td>
                        <td><?php echo $stats['total_picks']; ?></td>
                        <td><?php echo $stats['picks_made']; ?></td>
                        <td><?php echo $stats['roster_target']; ?></td>
                        <td><?php echo $stats['actual_roster']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2 class="centered">Draft Order and Picks</h2>
        <table class="draft-table">
            <thead>
                <tr>
                    <th>Round.Slot</th>
                    <th>Team</th>
                    <th>Player</th>
                    <th>Grade</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($draft_picks as $pick): ?>
                    <?php
                    $height_feet = $pick['height'] ? floor($pick['height'] / 12) : '';
                    $height_inches = $pick['height'] ? $pick['height'] - ($height_feet * 12) : '';
                    $height_display = $pick['height'] ? $height_feet . ' ft ' . $height_inches . ' in' : '';
                    ?>
                    <tr>
                        <td><?php echo $pick['dpround'].'.'.$pick['dpslot']; ?></td>
                        <td><?php echo htmlspecialchars($pick['name']); ?></td>
                        <td><?php echo $pick['playerID'] ? htmlspecialchars($pick['firstName'] . ' ' . $pick['lastName']) : '-'; ?></td>
                        <td><?php echo $pick['playerID'] ? $pick['grade'] : ''; ?></td>
                        <td><?php echo $pick['playerID'] ? htmlspecialchars($pick['weight'] ?? '') : ''; ?></td>
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