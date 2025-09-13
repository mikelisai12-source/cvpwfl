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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_info') {
        $fields = ['dCamp', 'dWeighin1', 'dWeighin2', 'dEquipment', 'dPractice', 'dWeek1', 'w1openingceremony', 'w1flag', 'w1third', 'w1game1', 'w1game2', 'dWeek2', 'w2flag', 'w2third', 'w2game1', 'w2game2', 'dWeek3', 'w3flag', 'w3third', 'w3game1', 'w3game2', 'dWeek4', 'w4flag', 'w4third', 'w4game1', 'w4game2', 'dWeek5', 'w5flag', 'w5third', 'w5game1', 'w5game2', 'dWeek6', 'w6flag', 'w6third', 'w6game1', 'w6game2', 'dWeek7', 'w7playoff', 'dBanquet', 'dSatNightGame'];
        $params = [];
        $sql = "UPDATE league SET ";
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) && $_POST[$field] !== '' ? $_POST[$field] : null;
            $sql .= "$field = ?, ";
            $params[] = $value;
        }
        $sql = rtrim($sql, ', ') . " WHERE lCurrentSeason = ?";
        $params[] = $league['lCurrentSeason'];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success = "League info updated successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update league info: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'flip_mode') {
        $new_mode = $league['seasonMode'] == 'season' ? 'offseason' : 'season';
        if ($new_mode == 'offseason') {
            $pdo->beginTransaction();
            try {
                $new_season = $league['lCurrentSeason'] + 1;
                $stmt = $pdo->prepare("INSERT INTO players (playerID, firstName, lastName, birthday, email, phone, jerseyNumber, seasonID, teamID, grade, height, weight, fortySpeed, active, web, draft_season, draft_round, draft_slot, draft_time) 
                    SELECT playerID, firstName, lastName, birthday, email, phone, IF(grade < 6, jerseyNumber, NULL), ?, IF(grade >= 6, 0, teamID), grade + 1, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL 
                    FROM players WHERE seasonID = ? AND grade < 12");
                $stmt->execute([$new_season, $league['lCurrentSeason']]);
                $stmt = $pdo->prepare("INSERT INTO standings (steamID, ssesaonID, steam, sheadcoach, sWins, sLosses, sTies, sPointsFor, sPointsAgainst, schamps, soff) 
                    SELECT steamID, ?, steam, sheadcoach, 0, 0, 0, 0, 0, 0, 0 FROM standings WHERE ssesaonID = ?");
                $stmt->execute([$new_season, $league['lCurrentSeason']]);
                $stmt = $pdo->prepare("UPDATE league SET seasonMode = ?, lCurrentSeason = ?, lCurrentWeek = 0, curRd = 0, curSlot = 0, draft_time = 0, draft_team = 0, dSatNightGame = 0, 
                    dCamp = NULL, dWeighin1 = NULL, dWeighin2 = NULL, dEquipment = NULL, dPractice = NULL, dWeek1 = NULL, 
                    w1openingceremony = NULL, w1flag = NULL, w1third = NULL, w1game1 = NULL, w1game2 = NULL, 
                    dWeek2 = NULL, w2flag = NULL, w2third = NULL, w2game1 = NULL, w2game2 = NULL, 
                    dWeek3 = NULL, w3flag = NULL, w3third = NULL, w3game1 = NULL, w3game2 = NULL, 
                    dWeek4 = NULL, w4flag = NULL, w4third = NULL, w4game1 = NULL, w4game2 = NULL, 
                    dWeek5 = NULL, w5flag = NULL, w5third = NULL, w5game1 = NULL, w5game2 = NULL, 
                    dWeek6 = NULL, w6flag = NULL, w6third = NULL, w6game1 = NULL, w6game2 = NULL, 
                    dWeek7 = NULL, w7playoff = NULL, dBanquet = NULL 
                    WHERE lCurrentSeason = ?");
                $stmt->execute([$new_mode, $new_season, $league['lCurrentSeason']]);
                $pdo->commit();
                $success = "League flipped to offseason mode.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to flip mode: " . $e->getMessage();
            }
        } else {
            $stmt = $pdo->prepare("UPDATE league SET seasonMode = ?, lCurrentWeek = 1 WHERE lCurrentSeason = ?");
            $stmt->execute([$new_mode, $league['lCurrentSeason']]);
            $success = "League flipped to season mode.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit League Info - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <h1>Edit League Info</h1>
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
            <input type="hidden" name="action" value="update_info">
            <label>Camp Date: <input type="datetime-local" name="dCamp" value="<?php echo $league['dCamp'] ? (new DateTime($league['dCamp']))->format('Y-m-d\TH:i') : ''; ?>" step="900"></label>
            <label>Weigh-In 1: <input type="datetime-local" name="dWeighin1" value="<?php echo $league['dWeighin1'] ? (new DateTime($league['dWeighin1']))->format('Y-m-d\TH:i') : ''; ?>" step="900"></label>
            <label>Weigh-In 2: <input type="datetime-local" name="dWeighin2" value="<?php echo $league['dWeighin2'] ? (new DateTime($league['dWeighin2']))->format('Y-m-d\TH:i') : ''; ?>" step="900"></label>
            <label>Equipment Pickup: <input type="datetime-local" name="dEquipment" value="<?php echo $league['dEquipment'] ? (new DateTime($league['dEquipment']))->format('Y-m-d\TH:i') : ''; ?>" step="900"></label>
            <label>First Practice: <input type="datetime-local" name="dPractice" value="<?php echo $league['dPractice'] ? (new DateTime($league['dPractice']))->format('Y-m-d\TH:i') : ''; ?>" step="900"></label>
            <h3>Week 1</h3>
            <label>Date: <input type="date" name="dWeek1" value="<?php echo $league['dWeek1'] ? substr($league['dWeek1'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Flag: <input type="time" name="w1flag" value="<?php echo $league['w1flag'] ?? ''; ?>"></label>
                <label>3rd Grade: <input type="time" name="w1third" value="<?php echo $league['w1third'] ?? ''; ?>"></label>
                <label>Opening Ceremony: <input type="time" name="w1openingceremony" value="<?php echo $league['w1openingceremony'] ?? ''; ?>"></label>
                <label>Game 1: <input type="time" name="w1game1" value="<?php echo $league['w1game1'] ?? ''; ?>"></label>
                <label>Game 2: <input type="time" name="w1game2" value="<?php echo $league['w1game2'] ?? ''; ?>"></label>
            </div>
            <h3>Week 2</h3>
            <label>Date: <input type="date" name="dWeek2" value="<?php echo $league['dWeek2'] ? substr($league['dWeek2'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Flag: <input type="time" name="w2flag" value="<?php echo $league['w2flag'] ?? ''; ?>"></label>
                <label>3rd Grade: <input type="time" name="w2third" value="<?php echo $league['w2third'] ?? ''; ?>"></label>
                <label>Game 1: <input type="time" name="w2game1" value="<?php echo $league['w2game1'] ?? ''; ?>"></label>
                <label>Game 2: <input type="time" name="w2game2" value="<?php echo $league['w2game2'] ?? ''; ?>"></label>
            </div>
            <h3>Week 3</h3>
            <label>Date: <input type="date" name="dWeek3" value="<?php echo $league['dWeek3'] ? substr($league['dWeek3'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Flag: <input type="time" name="w3flag" value="<?php echo $league['w3flag'] ?? ''; ?>"></label>
                <label>3rd Grade: <input type="time" name="w3third" value="<?php echo $league['w3third'] ?? ''; ?>"></label>
                <label>Game 1: <input type="time" name="w3game1" value="<?php echo $league['w3game1'] ?? ''; ?>"></label>
                <label>Game 2: <input type="time" name="w3game2" value="<?php echo $league['w3game2'] ?? ''; ?>"></label>
            </div>
            <h3>Week 4</h3>
            <label>Date: <input type="date" name="dWeek4" value="<?php echo $league['dWeek4'] ? substr($league['dWeek4'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Flag: <input type="time" name="w4flag" value="<?php echo $league['w4flag'] ?? ''; ?>"></label>
                <label>3rd Grade: <input type="time" name="w4third" value="<?php echo $league['w4third'] ?? ''; ?>"></label>
                <label>Game 1: <input type="time" name="w4game1" value="<?php echo $league['w4game1'] ?? ''; ?>"></label>
                <label>Game 2: <input type="time" name="w4game2" value="<?php echo $league['w4game2'] ?? ''; ?>"></label>
            </div>
            <h3>Week 5</h3>
            <label>Date: <input type="date" name="dWeek5" value="<?php echo $league['dWeek5'] ? substr($league['dWeek5'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Flag: <input type="time" name="w5flag" value="<?php echo $league['w5flag'] ?? ''; ?>"></label>
                <label>3rd Grade: <input type="time" name="w5third" value="<?php echo $league['w5third'] ?? ''; ?>"></label>
                <label>Game 1: <input type="time" name="w5game1" value="<?php echo $league['w5game1'] ?? ''; ?>"></label>
                <label>Game 2: <input type="time" name="w5game2" value="<?php echo $league['w5game2'] ?? ''; ?>"></label>
            </div>
            <h3>Week 6</h3>
            <label>Date: <input type="date" name="dWeek6" value="<?php echo $league['dWeek6'] ? substr($league['dWeek6'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Flag: <input type="time" name="w6flag" value="<?php echo $league['w6flag'] ?? ''; ?>"></label>
                <label>3rd Grade: <input type="time" name="w6third" value="<?php echo $league['w6third'] ?? ''; ?>"></label>
                <label>Game 1: <input type="time" name="w6game1" value="<?php echo $league['w6game1'] ?? ''; ?>"></label>
                <label>Game 2: <input type="time" name="w6game2" value="<?php echo $league['w6game2'] ?? ''; ?>"></label>
            </div>
            <h3>Week 7</h3>
            <label>Date: <input type="date" name="dWeek7" value="<?php echo $league['dWeek7'] ? substr($league['dWeek7'], 0, 10) : ''; ?>"></label>
            <div style="margin-left: 20px;">
                <label>Playoff: <input type="time" name="w7playoff" value="<?php echo $league['w7playoff'] ?? ''; ?>"></label>
            </div>
            <label>Banquet: <input type="datetime-local" name="dBanquet" value="<?php echo $league['dBanquet'] ? (new DateTime($league['dBanquet']))->format('Y-m-d\TH:i') : ''; ?>" step="900"></label>
            <label>Saturday Night Game Week: <input type="number" name="dSatNightGame" value="<?php echo htmlspecialchars($league['dSatNightGame'] ?? ''); ?>" min="0" max="6"></label>
            <button type="submit">Update League Info</button>
        </form>
        <hr>
        <div style="text-align: right;">
            <form method="POST" onsubmit="return confirm('Are you sure you want to flip to <?php echo $league['seasonMode'] == 'season' ? ($league['lCurrentSeason'] + 1) . ' offseason' : $league['lCurrentSeason'] . ' season'; ?> mode?');">
                <input type="hidden" name="action" value="flip_mode">
                <?php if ($league['seasonMode'] == 'season'): ?>
                    <button type="submit">Flip to <?php echo $league['lCurrentSeason'] + 1; ?> Offseason</button>
                    <p>This will Flip the league from the current <?php echo $league['lCurrentSeason']; ?> Season mode to the <?php echo $league['lCurrentSeason'] + 1; ?> Offseason mode. This is meant to be done after the season is complete and it's time to display offseason data (run it around January maybe).</p>
                <?php else: ?>
                    <button type="submit">Flip to <?php echo $league['lCurrentSeason']; ?> Season</button>
                    <p>This will Flip the league from the current <?php echo $league['lCurrentSeason']; ?> Offseason mode to the <?php echo $league['lCurrentSeason']; ?> Season mode. This is meant to be done after the draft is complete and any late signups have been onboarded (run it after the first week of practice maybe).</p>
                <?php endif; ?>
            </form>
        </div>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>