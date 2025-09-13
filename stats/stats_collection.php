<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

if (!isset($_SESSION['user_id']) || (!$_SESSION['stats'] && !$_SESSION['admin'])) {
    header('Location: /cvpwfl/auth/login.php');
    exit;
}

$game_id = $_GET['game_id'] ?? 0;

if (!$game_id) {
    header('Location: /cvpwfl/stats/select_game.php');
    exit;
}

if (isset($_GET['end_drive'])) {
    $team_id = $_GET['team_id'] ?? 0; // Legacy support if needed, but unused now
    $stmt = $pdo->prepare("UPDATE games SET current_drive = NULL, current_position = NULL, current_qtr = NULL WHERE gameID = ? AND teamID = ?");
    $stmt->execute([$game_id, $team_id]);
    header("Location: stats_collection.php?game_id=$game_id");
    exit;
}

// Fetch both teams for the game
$stmt = $pdo->prepare("SELECT t.teamID, t.name, g.home FROM games g JOIN teams t ON g.teamID = t.teamID WHERE g.gameID = ? GROUP BY t.teamID");
$stmt->execute([$game_id]);
$game_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($game_teams) !== 2) {
    echo "<p>Unexpected number of teams for this game. Please contact admin.</p>";
    exit;
}

// Sort teams for consistency (e.g., by teamID)
usort($game_teams, function($a, $b) { return $a['teamID'] <=> $b['teamID']; });

$off_team_id = $_GET['off_team_id'] ?? null;
$def_team_id = null;
$off_team_name = '';
$def_team_name = '';
$off_players = [];
$def_players = [];
$off_team_data = [];
$def_team_data = [];

if ($off_team_id) {
    // Validate off_team_id is one of the teams
    $team_ids = array_column($game_teams, 'teamID');
    if (!in_array($off_team_id, $team_ids)) {
        $off_team_id = null;
    } else {
        $def_team_id = ($off_team_id == $game_teams[0]['teamID']) ? $game_teams[1]['teamID'] : $game_teams[0]['teamID'];
        $off_team_name = ($off_team_id == $game_teams[0]['teamID']) ? $game_teams[0]['name'] : $game_teams[1]['name'];
        $def_team_name = ($def_team_id == $game_teams[0]['teamID']) ? $game_teams[0]['name'] : $game_teams[1]['name'];

        // Fetch offense players
        $stmt = $pdo->prepare("SELECT p.playerID, p.firstName, p.lastName, p.jerseyNumber, pg.* FROM players p 
            LEFT JOIN playergamestats pg ON p.playerID = pg.pID AND pg.gID = ? 
            WHERE p.teamID = ? AND p.seasonID = (SELECT lCurrentSeason FROM league ORDER BY lCurrentSeason DESC LIMIT 1) AND p.active = 1");
        $stmt->execute([$game_id, $off_team_id]);
        $off_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch defense players
        $stmt->execute([$game_id, $def_team_id]);
        $def_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch offense team data
        $stmt = $pdo->prepare("SELECT * FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $off_team_id]);
        $off_team_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch defense team data
        $stmt->execute([$game_id, $def_team_id]);
        $def_team_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Determine if in a current drive for offense
$off_in_drive = !empty($off_team_data) && !is_null($off_team_data['current_drive']);
$off_current_drive_num = $off_team_data['current_drive'] ?? null;

// If not in drive, find next available drive number for offense
$off_next_drive_num = null;
if (!$off_in_drive && !empty($off_team_data)) {
    for ($d = 1; $d <= 12; $d++) {
        if (is_null($off_team_data["d{$d}_clock"])) {
            $off_next_drive_num = $d;
            break;
        }
    }
}

function reverse_translate_pos($internal) {
    if ($internal > 50) {
        return 100 - $internal;
    } else {
        return - $internal;
    }
}

// NEW: Fetch current season
$stmt = $pdo->query("SELECT lCurrentSeason FROM league ORDER BY lCurrentSeason DESC LIMIT 1");
$current_season = $stmt->fetchColumn();

// NEW: Fetch game details for box score (adapted from index.php)
$stmt = $pdo->prepare("SELECT g.gameID, g.week, g.date, 
    MAX(CASE WHEN g.home = 1 THEN t.name ELSE NULL END) as home_team, 
    MAX(CASE WHEN g.home = 0 THEN t.name ELSE NULL END) as away_team,
    MAX(CASE WHEN g.home = 1 THEN t.teamID ELSE NULL END) as home_team_id, 
    MAX(CASE WHEN g.home = 0 THEN t.teamID ELSE NULL END) as away_team_id,
    MAX(CASE WHEN g.home = 1 THEN g.score_final ELSE NULL END) as home_score_final,
    MAX(CASE WHEN g.home = 0 THEN g.score_final ELSE NULL END) as away_score_final,
    MAX(CASE WHEN g.home = 1 THEN g.winner ELSE NULL END) as home_winner,
    MAX(CASE WHEN g.home = 0 THEN g.winner ELSE NULL END) as away_winner,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr1 ELSE NULL END) as home_qtr1,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr1 ELSE NULL END) as away_qtr1,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr2 ELSE NULL END) as home_qtr2,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr2 ELSE NULL END) as away_qtr2,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr3 ELSE NULL END) as home_qtr3,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr3 ELSE NULL END) as away_qtr3,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr4 ELSE NULL END) as home_qtr4,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr4 ELSE NULL END) as away_qtr4,
    MAX(CASE WHEN g.home = 1 THEN g.score_qtr5 ELSE NULL END) as home_qtr5,
    MAX(CASE WHEN g.home = 0 THEN g.score_qtr5 ELSE NULL END) as away_qtr5,
    MAX(g.current_qtr) as current_qtr
    FROM games g 
    JOIN teams t ON g.teamID = t.teamID 
    WHERE g.gameID = ? 
    GROUP BY g.gameID
");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

// NEW: Fetch away team stats (adapted from index.php)
$stmt_away = $pdo->prepare("
    SELECT 
        SUM(pgs.rush_yards) + SUM(pgs.pass_yards) AS total_yards,
        SUM(pgs.rushes) AS rush_att,
        SUM(pgs.rush_yards) AS rush_yds,
        SUM(pgs.rush_tds) AS rush_td,
        SUM(pgs.pass_completions) AS pass_comp,
        SUM(pgs.pass_attempts) AS pass_att,
        SUM(pgs.pass_yards) AS pass_yds,
        SUM(pgs.pass_tds) AS pass_td,
        SUM(pgs.pass_ints) AS pass_int,
        SUM(pgs.fumbles_lost) AS fumbles_lost,
        COUNT(pgs.pID) AS num_stats
    FROM playergamestats pgs
    JOIN players p ON pgs.pID = p.playerID
    WHERE pgs.gID = ? AND p.teamID = ? AND p.seasonID = ?
");
$stmt_away->execute([$game_id, $game['away_team_id'], $current_season]);
$away_stats = $stmt_away->fetch(PDO::FETCH_ASSOC);

// NEW: Fetch home team stats (adapted from index.php)
$stmt_home = $pdo->prepare("
    SELECT 
        SUM(pgs.rush_yards) + SUM(pgs.pass_yards) AS total_yards,
        SUM(pgs.rushes) AS rush_att,
        SUM(pgs.rush_yards) AS rush_yds,
        SUM(pgs.rush_tds) AS rush_td,
        SUM(pgs.pass_completions) AS pass_comp,
        SUM(pgs.pass_attempts) AS pass_att,
        SUM(pgs.pass_yards) AS pass_yds,
        SUM(pgs.pass_tds) AS pass_td,
        SUM(pgs.pass_ints) AS pass_int,
        SUM(pgs.fumbles_lost) AS fumbles_lost,
        COUNT(pgs.pID) AS num_stats
    FROM playergamestats pgs
    JOIN players p ON pgs.pID = p.playerID
    WHERE pgs.gID = ? AND p.teamID = ? AND p.seasonID = ?
");
$stmt_home->execute([$game_id, $game['home_team_id'], $current_season]);
$home_stats = $stmt_home->fetch(PDO::FETCH_ASSOC);

// Add this function if not already present (copied from save_stats.php for consistency)
function translate_pos($input) {
    if ($input < 0) {
        return -$input;
    } else {
        return 100 - $input;
    }
}

// Helper function to convert mm:ss to seconds
function time_to_seconds($time_str) {
    list($min, $sec) = explode(':', $time_str);
    return (int)$min * 60 + (int)$sec;
}

// Helper function to convert seconds to mm:ss
function seconds_to_time($seconds) {
    $min = floor($seconds / 60);
    $sec = $seconds % 60;
    return sprintf('%02d:%02d', $min, $sec);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stats Collection - Pee Wee Football</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
    <style>
        #extra-point-modal .modal-content label {
            display: block;
            margin-bottom: 10px;
        }
        #extra-point-modal .modal-content input[type="radio"] {
            margin-right: 5px;
        }
        #xp-sub-type-label label {
            //margin-left: 250px;
        }
        #td-time-min, #td-time-sec {
            width: 50px;
            text-align: center;
        }
        #td-time-colon {
            margin: 0 5px;
        }
        #td-time-container {
            display: flex;
            align-items: center;
        }
        #td-qtr {
            width: 150px;
        }
        #td-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #td-time-label {
            flex: 1;
        }
        #td-qtr-label {
            flex: 1;
            margin-left: 20px;
        }
        #xp-rush-result-label {
            //margin-left: 250px;
            margin-bottom: 10px;
            margin-top: 20px;
        }
        #xp-pass-result-label {
            //margin-left: 250px;
            margin-bottom: 10px;
            margin-top: 20px;
        }
        #xp-rusher-label {
            //margin-left: 250px;
            margin-bottom: 10px;
        }        
        #xp-rusher, #xp-passer, #xp-receiver {
            width: 200px;
        }
        #kick-player-label select {
            width: 200px;
        }
        #result-label {
            display: none;
        }
        #extra-point-modal .modal-buttons {
            text-align: center;
            margin-top: 20px;
        }
        #extra-point-modal .stat-button:active, #extra-point-modal .cancel:active {
            transform: scale(0.98);
            box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
        }
        .drive-qtr,
        .drive-start {
            display: inline-block;
        }
    .drive-summary {
        //display: flex;
        justify-content: center;
        //flex-wrap: wrap;
        margin: 0px 0;
    }
    .team-drive-table {
        margin: 0px;
        text-align: center;
        clear:both;
        width: auto;
    }
    .drive-table {
        /* width: 100%; */
        border-collapse: collapse;
        margin-top: 5px;
        table-layout: fixed;
        width: auto;
    }
    .drive-table th, .drive-table td {
        border: 1px solid #ddd;
        padding: 4px;
        font-size: .85em;
        width: 40px;
    }
    .drive-table th {
        background-color: #f2f2f2;
        color: black;
        word-wrap: break-word;
    } 
    </style>
</head>
<body>
    <div id="game-info" data-game-id="<?php echo $game_id; ?>"></div>
    <header>
        <h1>Stats Collection</h1>
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
        <!-- NEW: Box score display (adapted from index.php) -->
        <?php
        // NEW: Logic for box score (copied/adapted from index.php)
        $time_str = $game['date'] ? (new DateTime($game['date']))->format('g:ia') : 'TBD';
        $matchup_str = htmlspecialchars($game['away_team']) . ' @ ' . htmlspecialchars($game['home_team']) . '<br>' . $time_str;

        $currentQtr = $game['current_qtr'] ?? 0;
        $isGameOver = $game['home_winner'] || $game['away_winner'];
        $isStarted = $currentQtr > 0;
        
        // Infer the current quarter based on where scores > 0 exist (for either team)
        $inferred_qtr = 0;
        if ($game['away_qtr1'] > 0 || $game['home_qtr1'] > 0) $inferred_qtr = max($inferred_qtr, 1);
        if ($game['away_qtr2'] > 0 || $game['home_qtr2'] > 0) $inferred_qtr = max($inferred_qtr, 2);
        if ($game['away_qtr3'] > 0 || $game['home_qtr3'] > 0) $inferred_qtr = max($inferred_qtr, 3);
        if ($game['away_qtr4'] > 0 || $game['home_qtr4'] > 0) $inferred_qtr = max($inferred_qtr, 4);
        if ($game['away_qtr5'] > 0 || $game['home_qtr5'] > 0) $inferred_qtr = max($inferred_qtr, 5);

        // Use the max of stored currentQtr and inferred for effective quarter
        $effective_qtr = max($currentQtr, $inferred_qtr);

        // Adjust isStarted to use effective_qtr
        $isStarted = $effective_qtr > 0;

        // Use effective_qtr in conditions
        $awayQ1 = ($effective_qtr >= 1 || $isGameOver) ? $game['away_qtr1'] : '-';
        $awayQ2 = ($effective_qtr >= 2 || $isGameOver) ? $game['away_qtr2'] : '-';
        $awayQ3 = ($effective_qtr >= 3 || $isGameOver) ? $game['away_qtr3'] : '-';
        $awayQ4 = ($effective_qtr >= 4 || $isGameOver) ? $game['away_qtr4'] : '-';

        $homeQ1 = ($effective_qtr >= 1 || $isGameOver) ? $game['home_qtr1'] : '-';
        $homeQ2 = ($effective_qtr >= 2 || $isGameOver) ? $game['home_qtr2'] : '-';
        $homeQ3 = ($effective_qtr >= 3 || $isGameOver) ? $game['home_qtr3'] : '-';
        $homeQ4 = ($effective_qtr >= 4 || $isGameOver) ? $game['home_qtr4'] : '-';

        $awayFinal = $isStarted ? $game['away_score_final'] : '-';
        $homeFinal = $isStarted ? $game['home_score_final'] : '-';

        // Determine display for away team
        $away_stats_collected = ($away_stats['num_stats'] ?? 0) > 0;
        if (!$away_stats_collected && !$isGameOver) {
            $away_total_display = '-<span style="font-size:9px;"> yds<br>total</span>';
            $away_rush_display = '-<span style="font-size:8px;"> for </span>-<span style="font-size:8px;">yds</span><br>-<span style="font-size:9px;"> td</span>';
            $away_pass_display = '-<span style="font-size:12px;">/</span>-<span style="font-size:8px;"> for </span>-<span style="font-size:8px;">yds</span><br>-<span style="font-size:9px;">td </span>-<span style="font-size:9px;">int</span>';
            $away_fumbles_lost_display = '-';
        } else {
            $away_total = $away_stats['total_yards'] ?? 0;
            $away_rush_att = $away_stats['rush_att'] ?? 0;
            $away_rush_yds = $away_stats['rush_yds'] ?? 0;
            $away_rush_td = $away_stats['rush_td'] ?? 0;
            $away_pass_comp = $away_stats['pass_comp'] ?? 0;
            $away_pass_att = $away_stats['pass_att'] ?? 0;
            $away_pass_yds = $away_stats['pass_yds'] ?? 0;
            $away_pass_td = $away_stats['pass_td'] ?? 0;
            $away_pass_int = $away_stats['pass_int'] ?? 0;
            $away_fumbles_lost = $away_stats['fumbles_lost'] ?? 0;

            $away_total_display = $away_total . '<span style="font-size:9px;"> yds<br>total</span>';
            $away_rush_display = $away_rush_att . '<span style="font-size:8px;"> for </span>' . $away_rush_yds . '<span style="font-size:8px;">yds</span><br>' . $away_rush_td . '<span style="font-size:9px;"> td</span>';
            $away_pass_display = $away_pass_comp . '<span style="font-size:12px;">/</span>' . $away_pass_att . '<span style="font-size:8px;"> for </span>' . $away_pass_yds . '<span style="font-size:8px;">yds</span><br>' . $away_pass_td . '<span style="font-size:9px;">td </span>' . $away_pass_int . '<span style="font-size:9px;">int</span>';
            $away_fumbles_lost_display = $away_fumbles_lost;
        }

        // Determine display for home team
        $home_stats_collected = ($home_stats['num_stats'] ?? 0) > 0;
        if (!$home_stats_collected && !$isGameOver) {
            $home_total_display = '-<span style="font-size:9px;"> yds<br>total</span>';
            $home_rush_display = '-<span style="font-size:8px;"> for </span>-<span style="font-size:8px;">yds</span><br>-<span style="font-size:9px;"> td</span>';
            $home_pass_display = '-<span style="font-size:12px;">/</span>-<span style="font-size:8px;"> for </span>-<span style="font-size:8px;">yds</span><br>-<span style="font-size:9px;">td </span>-<span style="font-size:9px;">int</span>';
            $home_fumbles_lost_display = '-';
        } else {
            $home_total = $home_stats['total_yards'] ?? 0;
            $home_rush_att = $home_stats['rush_att'] ?? 0;
            $home_rush_yds = $home_stats['rush_yds'] ?? 0;
            $home_rush_td = $home_stats['rush_td'] ?? 0;
            $home_pass_comp = $home_stats['pass_comp'] ?? 0;
            $home_pass_att = $home_stats['pass_att'] ?? 0;
            $home_pass_yds = $home_stats['pass_yds'] ?? 0;
            $home_pass_td = $home_stats['pass_td'] ?? 0;
            $home_pass_int = $home_stats['pass_int'] ?? 0;
            $home_fumbles_lost = $home_stats['fumbles_lost'] ?? 0;

            $home_total_display = $home_total . '<span style="font-size:9px;"> yds<br>total</span>';
            $home_rush_display = $home_rush_att . '<span style="font-size:8px;"> for </span>' . $home_rush_yds . '<span style="font-size:8px;">yds</span><br>' . $home_rush_td . '<span style="font-size:9px;"> td</span>';
            $home_pass_display = $home_pass_comp . '<span style="font-size:12px;">/</span>' . $home_pass_att . '<span style="font-size:8px;"> for </span>' . $home_pass_yds . '<span style="font-size:8px;">yds</span><br>' . $home_pass_td . '<span style="font-size:9px;">td </span>' . $home_pass_int . '<span style="font-size:9px;">int</span>';
            $home_fumbles_lost_display = $home_fumbles_lost;
        }
        ?>
<div>        
    <div style="display: inline-block; vertical-align: top;">
        <div id="BS_whole_wrap">
            <div id="BS_header">
                <div id="BS_header_score">
                    <?php echo $matchup_str; ?>
                </div>
            </div>

            <div id="BS_stats">
                <div id="BS_logo_wrap">
                    <div id="BS_logo" style="margin-top:-2px;">
                        <img src="/cvpwfl/images/<?php echo $game['away_team_id']; ?>.gif" width="30" height="30" border="0">
                    </div>
                    <div style="font-size:10px;margin-top:-2px;margin-bottom:-2px;margin-left:10px;font-family:Comic Sans MS, cursive, sans-serif; ">
                        @
                    </div>
                    <div id="BS_logo">
                        <img src="/cvpwfl/images/<?php echo $game['home_team_id']; ?>.gif" width="30" height="30" border="0">
                    </div>
                </div>
                <div id="BS_qtr_scr_wrap">
                    <div id="BS_qtr_scr" style="border-right: 1px dotted black;border-bottom: 1px dotted black;"><?php echo $awayQ1; ?></div> 
                    <div id="BS_qtr_scr" style="border-right: 1px dotted black;border-bottom: 1px dotted black;"><?php echo $awayQ2; ?></div> 
                    <div id="BS_qtr_scr" style="border-right: 1px dotted black;border-bottom: 1px dotted black;"><?php echo $awayQ3; ?></div> 
                    <div id="BS_qtr_scr" style="border-bottom: 1px dotted black;"><?php echo $awayQ4; ?></div> 
                    <div id="BS_qtr_scr" style="border-right: 1px dotted black;border-top: 1px dotted black;"><?php echo $homeQ1; ?></div> 
                    <div id="BS_qtr_scr" style="border-right: 1px dotted black;border-top: 1px dotted black;"><?php echo $homeQ2; ?></div> 
                    <div id="BS_qtr_scr" style="border-right: 1px dotted black;border-top: 1px dotted black;"><?php echo $homeQ3; ?></div> 
                    <div id="BS_qtr_scr" style="border-top: 1px dotted black;"><?php echo $homeQ4; ?></div> 
                </div>

                <div id="BS_fin_scr_wrap"> 
                    <div id="BS_fin_scr" style="border-bottom: 1px dotted black;"><?php echo $awayFinal; ?></div>
                    <div id="BS_fin_scr" style="border-top: 1px dotted black;"><?php echo $homeFinal; ?></div>
                </div>
                
                
                <div id="BS_turnovers_wrap">
                    
                        <div id="BS_turnovers_label">Fumbles</div>
                    
                        <div id="BS_turnovers_innerwrap">
                            <div id="BS_turnovers" class="BS_turnovers_bottomborder"><?php echo $away_fumbles_lost_display; ?></div>
                            <div id="BS_turnovers"><?php echo $home_fumbles_lost_display; ?></div>
                        </div>
                    
                        <div id="BS_turnovers_label2">Lost</div>
                    
                </div>  
                
            
                <div id="BS_header_gamestats">
                    Total&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Rushing&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Passing
                </div>
               
                
                <div id="BS_teamstats_wrap">
                    <div id="BS_teamstats_innerwrap">
                        <div id="BS_teamstats" class="BS_teamstats_total" style="border-bottom: 1px dotted gray;"><?php echo $away_total_display; ?></div>
                        <div id="BS_teamstats" class="BS_teamstats_rushing" style="border-bottom: 1px dotted gray;"><?php echo $away_rush_display; ?></div>
                        <div id="BS_teamstats" class="BS_teamstats_passing" style="border-bottom: 1px dotted gray;"><?php echo $away_pass_display; ?></div>
                        
                    </div>
                    <div id="BS_teamstats_innerwrap">
                        <div id="BS_teamstats" class="BS_teamstats_total"><?php echo $home_total_display; ?></div>
                        <div id="BS_teamstats" class="BS_teamstats_rushing"><?php echo $home_rush_display; ?></div>
                        <div id="BS_teamstats" class="BS_teamstats_passing"><?php echo $home_pass_display; ?></div>
                    </div>                
                </div>
                <div id="BS_logo_wrap">
                    <div id="BS_logo" style="margin-top:0px; margin-left:5px;">
                        <img src="/cvpwfl/images/<?php echo $game['away_team_id']; ?>.gif" width="30" height="30" border="0">
                    </div>
                    <div id="BS_logo" style="margin-left:5px;">
                        <img src="/cvpwfl/images/<?php echo $game['home_team_id']; ?>.gif" width="30" height="30" border="0">
                    </div>
                </div>
            </div>
        </div> 
        
        <div class="container_ot_gameover_buttons">
                <button type="button" class="stat-button ot-scoring-button" data-team-id="<?php echo $game_teams[0]['teamID']; ?>"><?php echo htmlspecialchars($game_teams[0]['name']); ?> OT Scoring</button>
                <button type="button" class="stat-button ot-scoring-button" data-team-id="<?php echo $game_teams[1]['teamID']; ?>"><?php echo htmlspecialchars($game_teams[1]['name']); ?> OT Scoring</button>
                <button type="button" class="stat-button game-over-button">Game Over</button>
        </div>
    </div>

        <!-- END NEW: Box score display -->

        
<div style="display:inline-block; margin-left:20px;">
<div class="drive-summary">
    <?php foreach ($game_teams as $team): ?>
        <?php
        $team_id = $team['teamID'];
        $team_name = $team['name'];
        $home_away = $team['home'] ? '(home)' : '(away)';
        $stmt = $pdo->prepare("SELECT * FROM games WHERE gameID = ? AND teamID = ?");
        $stmt->execute([$game_id, $team_id]);
        $team_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_yards = 0;
        $total_seconds = 0;
        $drive_count = 0;
        ?>
        <div class="team-drive-table">
            <h2 style="margin: 0px;"><?php echo htmlspecialchars($team_name) . ' ' . $home_away; ?> Drives</h2>
            <table class="drive-table">
                <thead>
                    <tr>
                        <th>Drive #</th>
                        <th>Start Time</th>
                        <th>Start Qtr</th>
                        <th>Start Field Pos</th>
                        <th>End Time</th>
                        <th>End Qtr</th>
                        <th>End Field Pos</th>
                        <th>Total Yards</th>
                        <th>Drive Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($d = 1; $d <= 12; $d++) {
                        $clock_end = $team_data["d{$d}_clock_end"];
                        if (!is_null($clock_end)) {
                            $drive_count++;
                            $start_time = substr($team_data["d{$d}_clock"], 3, 5);
                            $start_qtr = $team_data["d{$d}_qtr"];
                            $start_pos = $team_data["d{$d}_start"];
                            $end_time = substr($clock_end, 3, 5);
                            $end_qtr = $team_data["d{$d}_qtr_end"];
                            $end_pos_raw = $team_data["d{$d}_fp_end"];
                            $end_pos = ($end_pos_raw == 0) ? "TD" : $end_pos_raw;

                            // Calculate total yards using internal positions
                            $start_internal = translate_pos($start_pos);
                            if ($end_pos_raw == 0) {
                                $yards = 100 - $start_internal;
                            } else {
                                $end_internal = translate_pos($end_pos_raw);
                                $yards = $end_internal - $start_internal;
                            }
                            $total_yards += $yards;

                            // Calculate drive time
                            $quarter_length = 360; // 6:00 in seconds
                            $start_secs = time_to_seconds($start_time);
                            $end_secs = time_to_seconds($end_time);
                            if ($start_qtr == $end_qtr) {
                                $elapsed = $start_secs - $end_secs;
                            } else {
                                $elapsed = $start_secs; // Time used in start quarter
                                $full_quarters = $end_qtr - $start_qtr - 1;
                                $elapsed += $full_quarters * $quarter_length;
                                $elapsed += $quarter_length - $end_secs; // Time used in end quarter
                            }
                            $total_seconds += $elapsed;
                            $drive_time = seconds_to_time($elapsed);

                            echo "<tr>
                                <td>$d</td>
                                <td>$start_time</td>
                                <td>$start_qtr</td>
                                <td>$start_pos</td>
                                <td>$end_time</td>
                                <td>$end_qtr</td>
                                <td>$end_pos</td>
                                <td>$yards</td>
                                <td>$drive_time</td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
                <?php if ($drive_count > 0): ?>
                    <tfoot>
                        <tr>
                            <td colspan="7"></td>
                            <td><?php echo $total_yards; ?></td>
                            <td><?php echo seconds_to_time($total_seconds); ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    <?php endforeach; ?>
</div>            
</div>
</div>
        
       <div style="margin-top: 10px;">
            <div class="stat-collection-poss-buttons" style="text-align: center; padding:10px; border-radius: 7px; background-color:#505050;">
                <button type="button" class="stat-button new-poss-button" data-team-id="<?php echo $game_teams[0]['teamID']; ?>"><img src="/cvpwfl/images/<?php echo $game_teams[0]['teamID']; ?>.gif" width="30" height="30" border="0"> new <?php echo htmlspecialchars($game_teams[0]['name']); ?> poss.</button>
                <button type="button" class="stat-button new-poss-button" data-team-id="<?php echo $game_teams[1]['teamID']; ?>"><img src="/cvpwfl/images/<?php echo $game_teams[1]['teamID']; ?>.gif" width="30" height="30" border="0"> new <?php echo htmlspecialchars($game_teams[1]['name']); ?> poss.</button>
                
            </div>
        </div>
        
        <?php if ($off_team_id): ?>
            <div id="offense-section" class="stat-section" data-team-id="<?php echo $off_team_id; ?>" style="display: block;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 25px;">
                    <h2><?php echo htmlspecialchars($off_team_name); ?> Offense</h2>
                    <button type="button" id="drive-over-button" class="stat-button">End OFF poss.</button>
                    <button type="button" class="stat-button penalty-button" style="background-color: yellow; color: black;">Offensive Penalty</button> <!-- NEW: Penalty button added -->
                </div>
                <?php if (!$off_in_drive && $off_next_drive_num): ?>
                    <div class="drive-inputs">
                        <div class="drive-input">
                            <label>Drive #<?php echo $off_next_drive_num; ?> clock start time: 
                                <span id="drive-time-container" style="padding-right: 30px;">
                                    <input type="text" id="drive-time-min" maxlength="2" placeholder="mm" style="width: 50px; text-align: center;">
                                    <span id="drive-time-colon">:</span>
                                    <input type="text" id="drive-time-sec" maxlength="2" placeholder="ss" style="width: 50px; text-align: center;">
                                </span>
                                <input type="hidden" class="drive-clock" data-drive="<?php echo $off_next_drive_num; ?>" data-field="d_clock" value="<?php echo $off_team_data["d{$off_next_drive_num}_clock"] ?? ''; ?>">
                            </label>
                            <label>Qtr: 
                                <span style="padding-right: 30px;">
                                <select class="drive-qtr" data-drive="<?php echo $off_next_drive_num; ?>" data-field="d_qtr" style="width: 50px; text-align: center;">
                                    <option value="1" <?php echo $off_team_data["d{$off_next_drive_num}_qtr"] == 1 ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo $off_team_data["d{$off_next_drive_num}_qtr"] == 2 ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo $off_team_data["d{$off_next_drive_num}_qtr"] == 3 ? 'selected' : ''; ?>>3</option>
                                    <option value="4" <?php echo $off_team_data["d{$off_next_drive_num}_qtr"] == 4 ? 'selected' : ''; ?>>4</option>
                                    <option value="5" <?php echo $off_team_data["d{$off_next_drive_num}_qtr"] == 5 ? 'selected' : ''; ?>>5</option>
                                </select>
                                </span>
                            </label>
                            <label>Start Field Position:
                                <span style="padding-right: 30px;">
                                <input type="number" class="drive-start" data-drive="<?php echo $off_next_drive_num; ?>" data-field="d_start" min="-49" max="49" value="<?php echo $off_team_data["d{$off_next_drive_num}_start"] ?? ''; ?>" style="width: 50px; text-align: center;">
                                </span>
                            </label>
                        </div>
                        <button type="button" id="submit-drive-start" style="margin-left: auto; margin-right: auto;">Submit</button>
                    </div>
                <?php endif; ?>

                <?php if ($off_in_drive): ?>
                    <h4 style="margin: 0px; text-align: center;">Current Drive #<?php echo $off_current_drive_num; ?> - Started Q<?php echo $off_team_data["d{$off_current_drive_num}_qtr"]; ?> at <?php echo $off_team_data["d{$off_current_drive_num}_clock"]; ?> from <?php echo $off_team_data["d{$off_current_drive_num}_start"]; ?>, Current Pos: <?php echo reverse_translate_pos($off_team_data['current_position']); ?></h4>
                    <table class="players-table-stat-coll">
                        <thead>
                            <tr>
                                <th>Jersey</th>
                                <th>Name</th>
                                <th>Rush</th>
                                <th>Rush Stats</th>
                                <th>Pass</th>
                                <th>Pass Stats</th>
                                <th>Rec Stats</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($off_players as $player): ?>
                                <tr data-player-id="<?php echo $player['playerID']; ?>">
                                    <td><?php echo htmlspecialchars($player['jerseyNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($player['firstName'] . ' ' . $player['lastName']); ?></td>
                                    <td><button type="button" class="rush-button stat-button" data-player-id="<?php echo $player['playerID']; ?>">Rush</button></td>
                                    <td>
                                        <?php
                                        $rushes = $player['rushes'] ?? 0;
                                        $rush_yards = $player['rush_yards'] ?? 0;
                                        $rush_tds = $player['rush_tds'] ?? 0;
                                        if ($rushes > 0) {
                                            echo "{$rushes} for {$rush_yards} yds, {$rush_tds} td";
                                        }
                                        ?>
                                    </td>
                                    <td><button type="button" class="pass-button stat-button" data-player-id="<?php echo $player['playerID']; ?>">Pass</button></td>
                                    <td>
                                        <?php
                                        $pass_completions = $player['pass_completions'] ?? 0;
                                        $pass_attempts = $player['pass_attempts'] ?? 0;
                                        $pass_yards = $player['pass_yards'] ?? 0;
                                        $pass_tds = $player['pass_tds'] ?? 0;
                                        $pass_ints = $player['pass_ints'] ?? 0;
                                        if ($pass_attempts > 0) {
                                            echo "{$pass_completions}/{$pass_attempts} for {$pass_yards} yds, {$pass_tds} td, {$pass_ints} int";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $receptions = $player['receptions'] ?? 0;
                                        $receiving_yards = $player['receiving_yards'] ?? 0;
                                        $receiving_tds = $player['receiving_tds'] ?? 0;
                                        if ($receptions > 0) {
                                            echo "{$receptions} for {$receiving_yards} yds, {$receiving_tds} td";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        
            <div id="defense-section" class="stat-section" data-team-id="<?php echo $def_team_id; ?>" style="display: block;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 25px;">
                    <h2><?php echo htmlspecialchars($def_team_name); ?> Defense</h2>
                    <button type="button" class="stat-button penalty-button" style="background-color: yellow; color: black;">Defensive Penalty</button> <!-- NEW: Penalty button added -->
                </div>
                <table class="players-table-stat-coll">
                    <thead>
                        <tr>
                            <th>Jersey</th>
                            <th>Name</th>
                            <th>T Ast</th>
                            <th>T Solo</th>
                            <th>Sack</th>
                            <th>FF</th>
                            <th>FR</th>
                            <th>Int</th>
                            <th>+/-</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($def_players as $player): ?>
                            <tr data-player-id="<?php echo $player['playerID']; ?>">
                                <td><?php echo htmlspecialchars($player['jerseyNumber']); ?></td>
                                <td><?php echo htmlspecialchars($player['firstName'] . ' ' . $player['lastName']); ?></td>
                                <td>
                                    <span class="stat-display" data-stat="tackles_assisted"><?php echo $player['tackles_assisted'] ?? 0; ?></span>
                                    <button type="button" class="stat-btn" data-stat="tackles_assisted">+</button>
                                </td>
                                <td>
                                    <span class="stat-display" data-stat="tackles_unassisted"><?php echo $player['tackles_unassisted'] ?? 0; ?></span>
                                    <button type="button" class="stat-btn" data-stat="tackles_unassisted">+</button>
                                </td>
                                <td>
                                    <span class="stat-display" data-stat="sacks"><?php echo $player['sacks'] ?? 0; ?></span>
                                    <button type="button" class="stat-btn" data-stat="sacks">+</button>
                                </td>
                                <td>
                                    <span class="stat-display" data-stat="fumbles_forced"><?php echo $player['fumbles_forced'] ?? 0; ?></span>
                                    <button type="button" class="stat-btn" data-stat="fumbles_forced">+</button>
                                </td>
                                <td>
                                    <span class="stat-display" data-stat="fumbles_recovered"><?php echo $player['fumbles_recovered'] ?? 0; ?></span>
                                    <button type="button" class="stat-btn" data-stat="fumbles_recovered">+</button>
                                </td>
                                <td>
                                    <span class="stat-display" data-stat="interceptions"><?php echo $player['interceptions'] ?? 0; ?></span>
                                    <button type="button" class="stat-btn" data-stat="interceptions">+</button>
                                </td>
                                <td><button type="button" class="toggle-mode" data-subtract-next="false">+</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

<!-- Rush modal -->
<div id="rush-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Rush</h2>
        <label><input type="radio" name="rush-result" value="normal" checked="checked"> Normal</label>
        <label><input type="radio" name="rush-result" value="td"> TD</label>
        <label><input type="radio" name="rush-result" value="fumble"> Fumble</label>
        <label id="fumble-rec-label" style="display: none; margin: 15px auto; width:175px;">Recovered by: <select id="fumble-rec-player">
            <option value="">None</option>
            <?php foreach ($off_players as $p): ?> <!-- Use off_players for fumble rec (same team) -->
                <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].' '.$p['firstName'].''.$p['lastName']; ?></option>
            <?php endforeach; ?>
        </select></label>
        <label id="rush-pos-label" style="display: block; margin: 15px auto; width:175px;">Field Position: <input type="number" id="rush-pos" class="field-pos-input" min="-49" max="49"></label>
        <div style="display:block; margin-top:15px;">
            <button type="button" id="submit-rush">Submit</button>
            <button type="button" class="cancel">X</button>
        </div>        
    </div>
</div>

        <!-- Pass modal -->
        <div id="pass-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Pass</h2>
                <label><input type="radio" name="pass-result" value="incomplete"> Incomplete</label>
                <label><input type="radio" name="pass-result" value="complete"> Complete</label>
                <span style="display:block; margin-top:15px;">
                    <label><input type="radio" name="pass-result" value="td"> TD</label>
                    <label><input type="radio" name="pass-result" value="intercepted"> Intercepted</label>
                </span>
                <label style="display: block; margin: 15px auto;"><input type="radio" name="pass-result" value="complete_fumble"> Complete then Fumble</label>
                <label id="complete-receiver-label" style="display: none;">Receiver: <select id="pass-receiver" style="text-align:center;">
                    <?php foreach ($off_players as $p): ?>
                        <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].'  '.$p['firstName'].''.$p['lastName']; ?></option>
                    <?php endforeach; ?>
                </select></label>
                <span style="display:block; margin-top:15px;">
                    <label id="complete-pos-label" style="display: none;">Field Position: <input type="number" id="pass-pos" class="field-pos-input" min="-49" max="49" style="text-align:center;"></label>
                </span>    
                <label id="fumble-rec-label-pass" style="display: none;">Recovered by: <select id="fumble-rec-player-pass">
                    <option value="">None</option>
                    <?php foreach ($off_players as $p): ?>
                        <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].'  '.$p['firstName'].''.$p['lastName']; ?></option>
                    <?php endforeach; ?>
                </select></label>
                <span style="display:block; margin-top:15px;">
                    <button type="button" id="submit-pass">Submit</button>
                    <button type="button" class="cancel">X</button>
                </span>
            </div>
        </div>

        <!-- Extra point modal -->
        <div id="extra-point-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                
                
        <div class="drive-inputs">
            <div class="drive-input">
                <div id="td-row">
                    <label id="td-time-label">TD Time: 
                        <span id="td-time-container">
                            <input type="text" id="td-time-min" maxlength="2" placeholder="mm" style="width: 50px; text-align: center;">
                            <span id="td-time-colon">:</span>
                            <input type="text" id="td-time-sec" maxlength="2" placeholder="ss" style="width: 50px; text-align: center;">
                        </span>
                        <input type="hidden" id="td-time" value="">
                    </label>
                    <label id="td-qtr-label">TD Qtr: <select id="td-qtr" style="width: 100px; text-align:center;">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select></label>
                </div>
            </div>
        </div>
                
                <h2>Extra Point</h2>
                <label><input type="radio" name="extra-type" value="rush"> Rush</label>
                <label><input type="radio" name="extra-type" value="pass"> Pass</label>
                <label><input type="radio" name="extra-type" value="kick"> Kick</label>
                <label id="xp-rush-result-label" style="display: none;">Result: <input type="radio" name="extra-result" value="success"> Success <input type="radio" name="extra-result" value="fail"> Fail</label>
                <label id="xp-rusher-label" style="display: none;">Rusher: <select id="xp-rusher" style="width: 200px;">
                    <?php foreach ($off_players as $p): ?>
                        <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].'  '.$p['firstName'].''.$p['lastName']; ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label id="xp-pass-result-label" style="display: none;">Result: <input type="radio" name="extra-result" value="success"> Success <input type="radio" name="extra-result" value="fail"> Fail</label>
                <label id="xp-passer-label" style="display: none;">Passer: <select id="xp-passer" style="width: 200px;">
                    <?php foreach ($off_players as $p): ?>
                        <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].'  '.$p['firstName'].''.$p['lastName']; ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label id="xp-receiver-label" style="display: none;">Receiver: <select id="xp-receiver" style="width: 200px;">
                    <?php foreach ($off_players as $p): ?>
                        <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].'  '.$p['firstName'].''.$p['lastName']; ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label id="kick-player-label" style="display: none;">Kicker: <select id="extra-kick-player">
                    <?php foreach ($off_players as $p): ?>
                        <option value="<?php echo $p['playerID']; ?>"><?php echo $p['jerseyNumber'].'  '.$p['firstName'].''.$p['lastName']; ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label id="result-label" style="display: none;">Result: <input type="radio" name="extra-result" value="success"> Success <input type="radio" name="extra-result" value="fail"> Fail</label>
                <div class="modal-buttons">
                    <button type="button" id="submit-extra" disabled>Submit</button>
                    <button type="button" class="cancel">X</button>
                </div>
            </div>
        </div>

        <!-- Drive over modal -->
        <div id="drive-over-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Drive Over</h2>
                <div id="drive-end-row">
                    <label id="drive-end-time-label">Drive End Time: 
                        <span id="drive-end-time-container">
                            <input type="text" id="drive-end-time-min" maxlength="2" placeholder="mm" style="width: 50px; text-align: center;">
                            <span id="drive-end-time-colon">:</span>
                            <input type="text" id="drive-end-time-sec" maxlength="2" placeholder="ss" style="width: 50px; text-align: center;">
                        </span>
                        <input type="hidden" id="drive-end-time" value="">
                    </label>
                    <label id="drive-end-qtr-label">Drive End Qtr: <select id="drive-end-qtr" style="width: 150px;">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select></label>
                </div>
                <div class="modal-buttons">
                    <button type="button" id="submit-drive-over">Submit</button>
                    <button type="button" class="cancel">X</button>
                </div>
            </div>
        </div>

        <!-- OT scoring modal -->
        <div id="ot-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>OT Scoring</h2>
                <label>Points: <input type="number" id="ot-points"></label>
                <button type="button" id="submit-ot">Submit</button>
                <button type="button" class="cancel">X</button>
            </div>
        </div>

        <!-- Game over modal -->
        <div id="gameover-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Game Over</h2>
                <label><input type="radio" name="winner" value="<?php echo $game_teams[0]['teamID']; ?>"> <?php echo htmlspecialchars($game_teams[0]['name']); ?> Wins</label>
                <label><input type="radio" name="winner" value="<?php echo $game_teams[1]['teamID']; ?>"> <?php echo htmlspecialchars($game_teams[1]['name']); ?> Wins</label>
                <button type="button" id="submit-gameover">Submit</button>
                <button type="button" class="cancel">X</button>
            </div>
        </div>
        
        <!-- NEW: Penalty modal added -->
        <div id="penalty-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Penalty - New Field Position</h2>
                <label>New Field Position:
                    <span style="padding-right: 30px;">
                        <input type="number" id="penalty-pos" min="-49" max="49" style="width: 50px; text-align: center;">
                    </span>
                </label>
                <div class="modal-buttons">
                    <button type="button" id="submit-penalty">Submit</button>
                    <button type="button" class="cancel">X</button>
                </div>
            </div>
        </div>        
        
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>