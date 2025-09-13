<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['admin']) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM league WHERE lCurrentSeason = (SELECT MAX(lCurrentSeason) FROM league)");
$league = $stmt->fetch(PDO::FETCH_ASSOC);
if ($league['seasonMode'] != 'offseason') {
    header('Location: /cvpwfl/index.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_draft_order') {
    $pdo->beginTransaction();
    try {
        $season = $league['lCurrentSeason'];

        // Calculate confirmed counts per team
        $confirmed = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        $stmt = $pdo->prepare("SELECT teamID, COUNT(*) as confirmed_count FROM players WHERE seasonID = ? AND grade BETWEEN 4 AND 6 AND active = 1 AND draft_season = 0 GROUP BY teamID");
        $stmt->execute([$season]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $confirmed[$row['teamID']] = $row['confirmed_count'];
        }

        $total_returning = array_sum($confirmed);

        // Calculate total rookies
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE seasonID = ? AND grade IN (4,5,6) AND active = 1 AND teamID = 0");
        $stmt->execute([$season]);
        $incoming_rookies = $stmt->fetchColumn();

        if ($incoming_rookies == 0) {
            throw new Exception("There are no rookies available to draft.");
        }

        $total_league_players = $total_returning + $incoming_rookies;
        $team_target_total = floor($total_league_players / 4);
        $remainder = $total_league_players % 4;

        $team_order = [$_POST['pick1'], $_POST['pick2'], $_POST['pick3'], $_POST['pick4']];
        if (count(array_unique($team_order)) != 4) {
            throw new Exception("Each team must be assigned a unique draft slot.");
        }

        $draft_order_str = implode(',', $team_order);

        // Calculate additional picks for first 'remainder' teams in order
        $additional = [];
        for ($i = 0; $i < 4; $i++) {
            $additional[$team_order[$i]] = ($i < $remainder) ? 1 : 0;
        }

        // Calculate max picks per team
        $max_picks = [];
        foreach ($team_order as $team) {
            $target = $team_target_total + $additional[$team];
            $max_picks[$team] = max(0, $target - $confirmed[$team]);
        }

        // Verify total picks match rookies
        if (array_sum($max_picks) != $incoming_rookies) {
            throw new Exception("Calculated picks do not match available rookies.");
        }

        // Delete existing draft picks
        $stmt = $pdo->prepare("DELETE FROM draft_picks WHERE dpseasonID = ?");
        $stmt->execute([$season]);

        // Populate draft_picks with variable picks per team
        $picks_assigned = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        $total_assigned = 0;
        $round = 1;

        while ($total_assigned < $incoming_rookies) {
            $slot = 1;
            foreach ($team_order as $team) {
                if ($picks_assigned[$team] < $max_picks[$team]) {
                    $stmt = $pdo->prepare("INSERT INTO draft_picks (dpteamID, dpseasonID, dpround, dpslot, dpmade) VALUES (?, ?, ?, ?, 0)");
                    $stmt->execute([$team, $season, $round, $slot]);
                    $picks_assigned[$team]++;
                    $total_assigned++;
                    $slot++;
                    if ($total_assigned == $incoming_rookies) {
                        break;
                    }
                }
            }
            $round++;
        }

        // Set league to start draft with first team
        $stmt = $pdo->prepare("SELECT dpteamID FROM draft_picks WHERE dpseasonID = ? ORDER BY dpround, dpslot LIMIT 1");
        $stmt->execute([$season]);
        $first_team = $stmt->fetchColumn();
        if (!$first_team) {
            throw new Exception("Failed to determine first drafting team.");
        }

        // Insert into new league_player_count table
        $stmt = $pdo->prepare("INSERT INTO league_player_count (seasonID, total_returning, incoming_rookies, total_league_players, players_per_team, draft_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$season, $total_returning, $incoming_rookies, $total_league_players, $team_target_total, $draft_order_str]);

        // Update league (only draft progression fields)
        $stmt = $pdo->prepare("UPDATE league SET curRd = 1, curSlot = 1, draft_time = NOW(), draft_team = ? WHERE lCurrentSeason = ?");
        $stmt->execute([$first_team, $season]);

        $pdo->commit();
        $success = "Draft order saved successfully. Draft is now live.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to save draft order: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT dp.*, t.name FROM draft_picks dp JOIN teams t ON dp.dpteamID = t.teamID WHERE dpseasonID = ? ORDER BY dpround, dpslot");
$stmt->execute([$league['lCurrentSeason']]);
$draft_picks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Order - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
</head>
<body>
    <header>
        <h1>Draft Order</h1>
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
        <form method="POST">
            <input type="hidden" name="action" value="save_draft_order">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <label><?php echo $i; ?>st Pick:
                    <select name="pick<?php echo $i; ?>" required>
                        <option value="">Select Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['teamID']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endfor; ?>
            <button type="submit">Save Draft Order</button>
        </form>
        <h2>Current Draft Order</h2>
        <table>
            <thead>
                <tr>
                    <th>Round</th>
                    <th>Slot</th>
                    <th>Team</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($draft_picks as $pick): ?>
                    <tr>
                        <td><?php echo $pick['dpround']; ?></td>
                        <td><?php echo $pick['dpslot']; ?></td>
                        <td><?php echo htmlspecialchars($pick['name']); ?></td>
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