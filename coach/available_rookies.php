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

$user_team_id = $_SESSION['teamID'] ?? 0;
$draft_started = $league['draft_team'] > 0;
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] == 1;
$is_your_turn = $league['draft_team'] == $user_team_id;
$can_draft = $draft_started && ($is_admin || ($_SESSION['headCoach'] && $is_your_turn));

// Fetch user's team name for button text if applicable
$team_name = 'Unknown';
if ($user_team_id > 0) {
    $stmt = $pdo->prepare("SELECT name FROM teams WHERE teamID = ?");
    $stmt->execute([$user_team_id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    $team_name = $team ? $team['name'] : 'Unknown';
}

// Fetch drafting team name for status message, timer, and admin override
$drafting_team_name = 'Unknown';
if ($draft_started && $league['draft_team'] > 0) {
    $stmt = $pdo->prepare("SELECT name FROM teams WHERE teamID = ?");
    $stmt->execute([$league['draft_team']]);
    $drafting_team_name = $stmt->fetchColumn() ?: 'Unknown';
}

// Check if draft is complete
$stmt = $pdo->prepare("SELECT COUNT(*) FROM players p WHERE p.seasonID = ? AND p.grade IN (4,5,6) AND p.active = 1 AND p.teamID = 0");
$stmt->execute([$league['lCurrentSeason']]);
$remaining_rookies = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_draft && isset($_POST['player_id'])) {
    $player_id = $_POST['player_id'];
    $assign_team = ($is_admin && !$is_your_turn) ? $league['draft_team'] : $user_team_id;

    // Fetch assign_team_name for emails
    $stmt = $pdo->prepare("SELECT name FROM teams WHERE teamID = ?");
    $stmt->execute([$assign_team]);
    $assign_team_name = $stmt->fetchColumn() ?: 'Unknown';

    $pdo->beginTransaction();
    try {
        // Get current pick for the assign_team
        $stmt = $pdo->prepare("SELECT * FROM draft_picks WHERE dpseasonID = ? AND dpteamID = ? AND dpmade = 0 ORDER BY dpround, dpslot LIMIT 1");
        $stmt->execute([$league['lCurrentSeason'], $assign_team]);
        $pick = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pick) {
            // Update draft_picks
            $stmt = $pdo->prepare("UPDATE draft_picks SET playerID = ?, dpmade = 1 WHERE autoID = ?");
            $stmt->execute([$player_id, $pick['autoID']]);
            
            // Update player
            $stmt = $pdo->prepare("UPDATE players SET teamID = ?, draft_season = ?, draft_round = ?, draft_slot = ?, draft_time = NOW() WHERE playerID = ? AND seasonID = ?");
            $stmt->execute([$assign_team, $league['lCurrentSeason'], $pick['dpround'], $pick['dpslot'], $player_id, $league['lCurrentSeason']]);
            
            // Check remaining rookies
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM players p WHERE p.seasonID = ? AND p.grade IN (4,5,6) AND p.active = 1 AND p.teamID = 0");
            $stmt->execute([$league['lCurrentSeason']]);
            $remaining = $stmt->fetchColumn();
            
            if ($remaining == 0) {
                // Draft complete
                $stmt = $pdo->prepare("UPDATE league SET curRd = 13, curSlot = 0, draft_time = NOW(), draft_team = 0 WHERE lCurrentSeason = ?");
                $stmt->execute([$league['lCurrentSeason']]);
            } else {
                // Get next pick
                $stmt = $pdo->prepare("SELECT dpteamID, dpround, dpslot FROM draft_picks WHERE dpseasonID = ? AND dpmade = 0 ORDER BY dpround, dpslot LIMIT 1");
                $stmt->execute([$league['lCurrentSeason']]);
                $next_pick = $stmt->fetch(PDO::FETCH_ASSOC);
                $next_team = $next_pick ? $next_pick['dpteamID'] : 0;
                $next_round = $next_pick ? $next_pick['dpround'] : 0;
                $next_slot = $next_pick ? $next_pick['dpslot'] : 0;
                
                // Update league
                $stmt = $pdo->prepare("UPDATE league SET curRd = ?, curSlot = ?, draft_time = NOW(), draft_team = ? WHERE lCurrentSeason = ?");
                $stmt->execute([$next_round, $next_slot, $next_team, $league['lCurrentSeason']]);
            }
            
            // Send email to player
            $stmt = $pdo->prepare("SELECT firstName, lastName, email FROM players WHERE playerID = ? AND seasonID = ?");
            $stmt->execute([$player_id, $league['lCurrentSeason']]);
            $player = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($player['email']) {
                $subject = "Congratulations on Being Drafted!";
                $message = "<h2>Welcome to the {$assign_team_name}!</h2>
                            <p>Dear {$player['firstName']} {$player['lastName']},</p>
                            <p>Congratulations! You have been selected by the {$assign_team_name} in the Connecticut Valley Pee Wee Football League for the {$league['lCurrentSeason']} season.</p>
                            <p>We look forward to seeing you at the upcoming preseason camp. Check the <a href='http://localhost/cvpwfl/index.php'>league website</a> for important dates and details.</p>
                            <p>Best regards,<br>The {$assign_team_name} Coaching Staff</p>";
                send_email($player['email'], $subject, $message);
            }
            
            // Send email to all head coaches of the next team
            if ($remaining > 0 && $next_team) {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE teamID = ? AND headCoach = 1");
                $stmt->execute([$next_team]);
                $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($coaches) {
                    $stmt = $pdo->prepare("SELECT name FROM teams WHERE teamID = ?");
                    $stmt->execute([$next_team]);
                    $next_team_name = $stmt->fetchColumn();
                    $subject = "Your Team is Up in the Draft!";
                    $message = "<h2>Draft Notification</h2>
                                <p>Your team, the {$next_team_name}, is now up in the draft at pick {$next_round}.{$next_slot} for the {$league['lCurrentSeason']} season.</p>
                                <p>Please visit the <a href='http://localhost/cvpwfl/coach/available_rookies.php'>Available Rookies</a> page to make your selection.</p>
                                <p>Best regards,<br>Connecticut Valley Pee Wee Football League</p>";
                    foreach ($coaches as $coach) {
                        if ($coach['email']) {
                            send_email($coach['email'], $subject, $message);
                        }
                    }
                }
            }
            
            $pdo->commit();
            header('Location: /cvpwfl/coach/draft.php');
            exit;
        } else {
            throw new Exception("No available draft picks for this team.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to draft player: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT p.* FROM players p WHERE p.seasonID = ? AND p.grade IN (4,5,6) AND p.active = 1 AND p.teamID = 0");
$stmt->execute([$league['lCurrentSeason']]);
$rookies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rookies - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
</head>
<body>
    <header>
        <h1>Available Rookies</h1>
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
        <form method="POST" id="draft-form">
            <div class="link-buttons">
                <a href="/cvpwfl/coach/available_rookies.php" class="link-button draft-link-button">Available Rookies</a>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1 || isset($_SESSION['headCoach']) && $_SESSION['headCoach'] == 1 || isset($_SESSION['asstCoach']) && $_SESSION['asstCoach'] == 1): ?>
                    <a href="/cvpwfl/coach/draft.php" class="link-button draft-link-button">Draft</a>
                    <a href="/cvpwfl/coach/edit_players.php" class="link-button draft-link-button">Edit Roster</a>
                <?php endif; ?>
            </div>
            <?php if ($can_draft): ?>
                <div class="submit-draft-button">
                    <button type="submit" class="draft-submit-button" data-form-id="draft-form">
                        Submit the <?php echo htmlspecialchars(($is_admin && !$is_your_turn) ? $drafting_team_name : $team_name); ?> draft pick
                    </button>
                </div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="centered" style="color: green;"><?php echo $success; ?></p>
            <?php elseif (isset($error)): ?>
                <p class="centered" style="color: red;"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($league['curRd'] == 13): ?>
                <p class="centered" style="color: green; font-weight: bold;">The draft is complete!</p>
            <?php elseif (!$draft_started): ?>
                <p class="centered" style="color: red;">The draft has not started yet. We're waiting for an admin to set the draft order.</p>
            <?php elseif ($can_draft): ?>
                <p class="centered">
                    <?php if ($is_admin && !$is_your_turn): ?>
                        Admin: Draft for <?php echo htmlspecialchars($drafting_team_name); ?>
                    <?php else: ?>
                        Your team is up to draft.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="centered">Waiting for the <?php echo htmlspecialchars($drafting_team_name); ?> to draft.</p>
            <?php endif; ?>
            <?php if ($draft_started && $league['curRd'] != 13): ?>
                <p class="centered">The <?php echo htmlspecialchars($drafting_team_name); ?> are on the clock and have been up for <span id="draft-timer" data-draft-time="<?php echo $league['draft_time'] ? htmlspecialchars((new DateTime($league['draft_time'], new DateTimeZone('America/New_York')))->format('c')) : ''; ?>"></span>.</p>
            <?php endif; ?>
            <p class="centered">Rookie count: <?php echo $remaining_rookies; ?></p>
            <table class="rookies-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th class="sortable" data-sort="firstName">Name <span class="sort-indicator"></span></th>
                        <th class="sortable" data-sort="grade">Grade <span class="sort-indicator"></span></th>
                        <th class="sortable" data-sort="weight">Weight <span class="sort-indicator"></span></th>
                        <th class="sortable" data-sort="height">Height <span class="sort-indicator"></span></th>
                        <th class="sortable" data-sort="fortySpeed">40yd Dash <span class="sort-indicator"></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rookies as $player): ?>
                        <?php
                        $height_feet = $player['height'] ? floor($player['height'] / 12) : '';
                        $height_inches = $player['height'] ? $player['height'] - ($height_feet * 12) : '';
                        $height_display = $player['height'] ? $height_feet . ' ft ' . $height_inches . ' in' : '';
                        ?>
                        <tr>
                            <td><input type="radio" name="player_id" value="<?php echo $player['playerID']; ?>" <?php echo $can_draft ? '' : 'disabled'; ?>></td>
                            <td><?php echo htmlspecialchars($player['firstName'] . ' ' . $player['lastName']); ?></td>
                            <td><?php echo $player['grade']; ?></td>
                            <td><?php echo htmlspecialchars($player['weight'] ?? ''); ?></td>
                            <td><?php echo $height_display; ?></td>
                            <td><?php echo htmlspecialchars($player['fortySpeed'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <div id="confirm-draft-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <p>Are you sure?</p>
                <div class="modal-buttons">
                    <button class="confirm-button">Confirm</button>
                    <button class="cancel-button">Cancel</button>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>