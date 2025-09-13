<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || (!$_SESSION['admin'] && !$_SESSION['headCoach'] && !$_SESSION['asstCoach'])) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$isAdmin = $_SESSION['admin'];
$isHeadCoach = $_SESSION['headCoach'];
$coachTeamID = $_SESSION['teamID'] ?? 0;

// Set default filter based on user role
$team_map = ['dolphins' => 1, 'jets' => 2, 'packers' => 3, 'patriots' => 4];
$filter = $_GET['filter'] ?? ($isHeadCoach && !$isAdmin && $coachTeamID ? array_search($coachTeamID, $team_map) : 'active');

$seasonID = $pdo->query("SELECT MAX(lCurrentSeason) FROM league")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_player' && $isAdmin) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $birth_month = $_POST['birth_month'] ?? '';
    $birth_day = $_POST['birth_day'] ?? '';
    $birth_year = $_POST['birth_year'] ?? '';
    $birthday = null;
    if ($birth_month && $birth_day && $birth_year) {
        if (checkdate((int)$birth_month, (int)$birth_day, (int)$birth_year)) {
            $birthday = sprintf('%04d-%02d-%02d', $birth_year, $birth_month, $birth_day);
        } else {
            $error = "Invalid birthday date.";
        }
    }
    $email = $_POST['email'] !== '' ? $_POST['email'] : null;
    $phone = $_POST['phone'] !== '' ? $_POST['phone'] : null;
    $grade = $_POST['grade'];
    $height_feet = $_POST['height_feet'] !== '' ? (int)$_POST['height_feet'] : 0;
    $height_inches = $_POST['height_inches'] !== '' ? (float)$_POST['height_inches'] : 0;
    $height = $height_feet > 0 || $height_inches > 0 ? ($height_feet * 12 + $height_inches) : null;
    $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
    $teamID = isset($_POST['late_signup']) && $_POST['late_signup'] == '1' && isset($_POST['teamID']) ? (int)$_POST['teamID'] : 0;

    if (!isset($error)) {
        if (isset($_POST['late_signup']) && $_POST['late_signup'] == '1' && !isset($_POST['teamID'])) {
            $error = "Team selection is required for late signups.";
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO players (firstName, lastName, birthday, email, phone, seasonID, grade, height, weight, active, teamID) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([$firstName, $lastName, $birthday, $email, $phone, $seasonID, $grade, $height, $weight, $teamID]);
                $player_id = $pdo->lastInsertId();

                if (isset($_POST['late_signup']) && $_POST['late_signup'] == '1' && $teamID) {
                    // Update draft_picks for late signup
                    $stmt = $pdo->prepare("SELECT * FROM draft_picks WHERE dpseasonID = ? AND dpteamID = ? AND dpmade = 0 ORDER BY dpround, dpslot LIMIT 1");
                    $stmt->execute([$seasonID, $teamID]);
                    $pick = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($pick) {
                        $stmt = $pdo->prepare("UPDATE draft_picks SET playerID = ?, dpmade = 1 WHERE autoID = ?");
                        $stmt->execute([$player_id, $pick['autoID']]);
                    }

                    // Update player draft fields for late signup
                    $stmt = $pdo->prepare("UPDATE players SET draft_season = ?, draft_round = 13, draft_slot = NULL, draft_time = NOW() WHERE playerID = ? AND seasonID = ?");
                    $stmt->execute([$seasonID, $player_id, $seasonID]);

                    // Send email to player
                    if ($email) {
                        $stmt = $pdo->prepare("SELECT name FROM teams WHERE teamID = ?");
                        $stmt->execute([$teamID]);
                        $team_name = $stmt->fetchColumn();
                        $subject = "Congratulations on Joining the Team!";
                        $message = "<h2>Welcome to the {$team_name}!</h2>
                                    <p>Dear {$firstName} {$lastName},</p>
                                    <p>Congratulations! You have been assigned to the {$team_name} in the Connecticut Valley Pee Wee Football League for the {$seasonID} season.</p>
                                    <p>We look forward to seeing you at the upcoming preseason camp. Check the <a href='http://localhost/cvpwfl/index.php'>league website</a> for important dates and details.</p>
                                    <p>Best regards,<br>The {$team_name} Coaching Staff</p>";
                        send_email($email, $subject, $message);
                    }
                }

                $pdo->commit();
                $success = "Player added successfully.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Failed to add player: " . $e->getMessage();
            }
        }
    }
}

$where = "WHERE p.seasonID = :seasonID ";
$params = [':seasonID' => $seasonID];
if ($filter === 'active') {
    $where .= "AND p.active = 1 AND p.grade <= 6 ";
} else if ($filter === 'rookies') {
    $where .= "AND p.teamID = 0 AND p.grade >= 4 AND p.grade <= 6 AND (SELECT COUNT(*) FROM players p3 WHERE p3.playerID = p.playerID) = 1 ";
} else if ($filter !== 'all') {
    if (isset($team_map[$filter])) {
        $where .= "AND p.teamID = :teamID ";
        $params[':teamID'] = $team_map[$filter];
    } else {
        $where .= "AND p.active = 1 AND p.grade <= 6 ";
    }
}

$stmt = $pdo->prepare("SELECT p.*, t.name AS team_name 
    FROM players p 
    LEFT JOIN teams t ON p.teamID = t.teamID 
    $where
    ORDER BY p.grade DESC, p.lastName ASC");
$stmt->execute($params);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($players as &$player) {
    if (!$player['team_name']) {
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM players WHERE playerID = ?");
        $stmt_count->execute([$player['playerID']]);
        $count = $stmt_count->fetchColumn();

        if ($count > 1) {
            $stmt_prev = $pdo->prepare("SELECT t.name FROM players p2 
                LEFT JOIN teams t ON p2.teamID = t.teamID 
                WHERE p2.playerID = ? AND p2.seasonID < ? AND p2.teamID != 0 
                ORDER BY p2.seasonID DESC LIMIT 1");
            $stmt_prev->execute([$player['playerID'], $seasonID]);
            $prev_team = $stmt_prev->fetchColumn();
            if ($prev_team) {
                $player['team_name'] = $prev_team . ' (v)';
            }
        }
    }
}
unset($player);

// Fetch next 4 unmade draft picks
$stmt = $pdo->prepare("SELECT dp.*, t.name FROM draft_picks dp JOIN teams t ON dp.dpteamID = t.teamID WHERE dp.dpseasonID = ? AND dp.dpmade = 0 ORDER BY dpround, dpslot LIMIT 4");
$stmt->execute([$seasonID]);
$next_picks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch team roster counts
$stmt = $pdo->prepare("SELECT t.name, COUNT(*) as roster_count FROM players p JOIN teams t ON p.teamID = t.teamID WHERE p.seasonID = ? AND p.active = 1 AND p.grade <= 6 GROUP BY t.teamID");
$stmt->execute([$seasonID]);
$roster_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$roster_counts_map = array_column($roster_counts, 'roster_count', 'name');
foreach (['Dolphins', 'Jets', 'Packers', 'Patriots'] as $team) {
    if (!isset($roster_counts_map[$team])) {
        $roster_counts_map[$team] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit/Add Players - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
</head>
<body>
    <header>
        <h1>Edit/Add Players</h1>
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
    <main class="edit-players-main">
        <?php if (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php elseif (isset($error)): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
            <div class="add-player-section">
                <h2 class="centered">Add New Player</h2>
                <form method="POST" class="form-inline" id="add-player-form">
                    <input type="hidden" name="action" value="add_player">
                    <div class="form-row">
                        <label>First Name: <input type="text" name="firstName" class="form-input" required></label>
                        <label>Last Name: <input type="text" name="lastName" class="form-input" required></label>
                    </div>
                    <div class="form-row">
                        <label>Email: <input type="email" name="email" class="form-input"></label>
                        <label>Phone: <input type="tel" name="phone" class="form-input"></label>
                    </div>
                    <div class="form-row">
                        <label>Grade: 
                            <select name="grade" class="form-select" required>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                            </select>
                        </label>
                        <label>Birthday: 
                            <select name="birth_month" class="birthday-select">
                                <option value="">Month</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo sprintf('%02d', $m); ?>"><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                                <?php endfor; ?>
                            </select>
                            <input type="number" name="birth_day" min="1" max="31" placeholder="Day" class="form-input-narrow">
                            <input type="number" name="birth_year" min="2000" max="<?php echo date('Y'); ?>" placeholder="Year" class="form-input">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>Weight: <input type="number" name="weight" placeholder="Pounds" step="0.1" min="0" class="weight-input"></label>
                        <label>Height: 
                            <input type="number" name="height_feet" placeholder="Feet" min="0" max="7" class="form-input-narrow"> 
                            <input type="number" name="height_inches" placeholder="Inches" min="0" max="11" step="0.1" class="form-input-narrow">
                        </label>
                    </div>
                    <div class="form-row">
                        <label><input type="checkbox" name="late_signup" id="late-signup" value="1"> Late Signup</label>
                    </div>
                    <div class="form-row">
                        <label>Assign Team:</label>
                        <?php foreach ($team_map as $key => $id): ?>
                            <label><input type="radio" name="teamID" class="team-id-radio" value="<?php echo $id; ?>"> <?php echo ucfirst($key); ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($next_picks): ?>
                        <h3>Next 4 Unmade Draft Picks</h3>
                        <ul>
                            <?php foreach ($next_picks as $pick): ?>
                                <li>Round <?php echo $pick['dpround']; ?>, Slot <?php echo $pick['dpslot']; ?>: <?php echo htmlspecialchars($pick['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <h3>Team Roster Counts</h3>
                    <div class="roster-counts">
                        <?php foreach ($roster_counts_map as $team => $count): ?>
                            <span><?php echo htmlspecialchars($team); ?>: <?php echo $count; ?> players</span>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="add-player-button">Add Player</button>
                </form>
            </div>
        <?php endif; ?>      
        <div class="link-buttons">
            <a href="/cvpwfl/coach/available_rookies.php" class="link-button draft-link-button">Available Rookies</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1 || isset($_SESSION['headCoach']) && $_SESSION['headCoach'] == 1 || isset($_SESSION['asstCoach']) && $_SESSION['asstCoach'] == 1): ?>
                <a href="/cvpwfl/coach/draft.php" class="link-button draft-link-button">Draft</a>
                <a href="/cvpwfl/coach/edit_players.php" class="link-button draft-link-button">Edit Roster</a>
            <?php endif; ?>
        </div>
        <h2 class="centered">Current Players</h2>
        <div class="filter-buttons">
            <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All Players</a>
            <a href="?filter=active" class="<?php echo $filter === 'active' ? 'active' : ''; ?>">Active Players</a>
            <a href="?filter=rookies" class="<?php echo $filter === 'rookies' ? 'active' : ''; ?>">Rookies</a>
            <a href="?filter=dolphins" class="<?php echo $filter === 'dolphins' ? 'active' : ''; ?>">Dolphins</a>
            <a href="?filter=jets" class="<?php echo $filter === 'jets' ? 'active' : ''; ?>">Jets</a>
            <a href="?filter=packers" class="<?php echo $filter === 'packers' ? 'active' : ''; ?>">Packers</a>
            <a href="?filter=patriots" class="<?php echo $filter === 'patriots' ? 'active' : ''; ?>">Patriots</a>
        </div>
<table class="players-table">
    <thead>
        <tr>
            <th class="sortable" data-sort="firstName">First Name <span class="sort-indicator"></span></th>
            <th class="sortable" data-sort="lastName">Last Name <span class="sort-indicator"></span></th>
            <th class="sortable" data-sort="grade">Grade <span class="sort-indicator"></span></th>
            <th class="sortable" data-sort="team_name">Team <span class="sort-indicator"></span></th>
            <th class="sortable" data-sort="weight">Weight <span class="sort-indicator"></span></th>
            <th class="sortable" data-sort="active">Active <span class="sort-indicator"></span></th>
            <th class="sortable" data-sort="web">Web <span class="sort-indicator"></span></th>
            <th>Details</th>
            <?php if ($isAdmin): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($players as $player): ?>
            <?php
            $isEditable = $isAdmin || ($isHeadCoach && $player['teamID'] == $coachTeamID && $player['grade'] < 7);
            $birthday = $player['birthday'] ? (new DateTime($player['birthday']))->format('m-d-Y') : '';
            $height_feet = $player['height'] ? floor($player['height'] / 12) : '';
            $height_inches = $player['height'] ? $player['height'] - ($height_feet * 12) : '';
            $height_display = $player['height'] ? $height_feet . ' ft ' . $height_inches . ' in' : '';
            $active_display = $player['active'] ? 'Yes' : 'No';
            $web_display = $player['web'] ? 'Yes' : 'No'; // New display variable for web
            ?>
            <tr data-player-id="<?php echo $player['playerID']; ?>">
                <td><?php if ($isAdmin): ?><input type="text" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="firstName" value="<?php echo htmlspecialchars($player['firstName']); ?>"><?php else: ?><?php echo htmlspecialchars($player['firstName']); ?><?php endif; ?></td>
                <td><?php if ($isAdmin): ?><input type="text" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="lastName" value="<?php echo htmlspecialchars($player['lastName']); ?>"><?php else: ?><?php echo htmlspecialchars($player['lastName']); ?><?php endif; ?></td>
                <td style="text-align: center;"><?php if ($isAdmin): ?><input type="number" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="grade" value="<?php echo $player['grade']; ?>" min="3" max="12"><?php else: ?><?php echo $player['grade']; ?><?php endif; ?></td>
                <td>
                    <?php if ($isAdmin): ?>
                        <select class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="teamID">
                            <option value="0" <?php echo $player['teamID'] == 0 ? 'selected' : ''; ?>>None</option>
                            <option value="1" <?php echo $player['teamID'] == 1 ? 'selected' : ''; ?>>Dolphins</option>
                            <option value="2" <?php echo $player['teamID'] == 2 ? 'selected' : ''; ?>>Jets</option>
                            <option value="3" <?php echo $player['teamID'] == 3 ? 'selected' : ''; ?>>Packers</option>
                            <option value="4" <?php echo $player['teamID'] == 4 ? 'selected' : ''; ?>>Patriots</option>
                        </select>
                    <?php else: ?>
                        <?php echo htmlspecialchars($player['team_name'] ?? ''); ?>
                    <?php endif; ?>
                </td>
                <td><?php if ($isEditable): ?><input type="number" step="0.1" class="modal-input weight-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="weight" value="<?php echo htmlspecialchars($player['weight'] ?? ''); ?>"><?php else: ?><?php echo htmlspecialchars($player['weight'] ?? ''); ?><?php endif; ?></td>
                <td><?php if ($isEditable): ?><input type="checkbox" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="active" <?php echo $player['active'] ? 'checked' : ''; ?>><?php else: ?><?php echo $active_display; ?><?php endif; ?></td>
                <td><?php if ($isEditable): ?><input type="checkbox" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="web" <?php echo $player['web'] ? 'checked' : ''; ?>><?php else: ?><?php echo $web_display; ?><?php endif; ?></td> <!-- New web column -->
                <td><button class="toggle-details" data-player-id="<?php echo $player['playerID']; ?>">Show</button></td>
                <?php if ($isAdmin): ?>
                    <td><button class="small-button delete-player" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>">X</button></td>
                <?php endif; ?>
            </tr>
            <tr class="details-row" data-player-id="<?php echo $player['playerID']; ?>" style="display: none;">
                <td colspan="<?php echo $isAdmin ? '9' : '8'; ?>"> <!-- Adjusted colspan for new actions column -->
                    <div class="details-content">
                        <label>Height: 
                            <?php if ($isEditable): ?>
                                <input type="number" class="modal-input height-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="height_feet" value="<?php echo htmlspecialchars($height_feet); ?>" min="0" max="7" placeholder="Feet">
                                <input type="number" step="0.1" class="modal-input height-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="height_inches" value="<?php echo htmlspecialchars($height_inches); ?>" min="0" max="11" placeholder="Inches">
                            <?php else: ?>
                                <span><?php echo $height_display; ?></span>
                            <?php endif; ?>
                        </label>
                        <label>Email: 
                            <?php if ($isAdmin): ?>
                                <input type="email" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="email" value="<?php echo htmlspecialchars($player['email'] ?? ''); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($player['email'] ?? ''); ?></span>
                            <?php endif; ?>
                        </label>
                        <label>Phone: 
                            <?php if ($isEditable): ?>
                                <input type="tel" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="phone" value="<?php echo htmlspecialchars($player['phone'] ?? ''); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($player['phone'] ?? ''); ?></span>
                            <?php endif; ?>
                        </label>
                        <label>Birthday: <span><?php echo htmlspecialchars($birthday); ?></span></label>
                        <label>Jersey #: 
                            <?php if ($isEditable): ?>
                                <input type="number" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="jerseyNumber" value="<?php echo htmlspecialchars($player['jerseyNumber'] ?? ''); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($player['jerseyNumber'] ?? ''); ?></span>
                            <?php endif; ?>
                        </label>
                        <label>40yd Dash: 
                            <?php if ($isEditable): ?>
                                <input type="number" step="0.01" class="modal-input" data-player-id="<?php echo $player['playerID']; ?>" data-season-id="<?php echo $player['seasonID']; ?>" data-field="fortySpeed" value="<?php echo htmlspecialchars($player['fortySpeed'] ?? ''); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($player['fortySpeed'] ?? ''); ?></span>
                            <?php endif; ?>
                        </label>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
    <script>
        $(document).ready(function() {
            // Toggle required attribute for teamID radio buttons based on late_signup checkbox
            $('#late-signup').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.team-id-radio').prop('required', true);
                } else {
                    $('.team-id-radio').prop('required', false);
                }
            });

            // Ensure teamID is not required on page load if late_signup is unchecked
            if (!$('#late-signup').is(':checked')) {
                $('.team-id-radio').prop('required', false);
            }
        });
    </script>
</body>
</html>