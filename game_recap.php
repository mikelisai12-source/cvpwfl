<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

// Fetch game_id from GET
$game_id = $_GET['game_id'] ?? 0;

if (!$game_id) {
    echo "<p>Invalid game ID.</p>";
    exit;
}

// Fetch current season
$stmt = $pdo->query("SELECT lCurrentSeason FROM league ORDER BY lCurrentSeason DESC LIMIT 1");
$current_season = $stmt->fetchColumn();

// Fetch game details
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

if (!$game) {
    echo "<p>Game not found.</p>";
    exit;
}

// Fetch away team stats
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

// Fetch home team stats
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

// Fetch both teams for the game
$stmt = $pdo->prepare("SELECT t.teamID, t.name, g.home FROM games g JOIN teams t ON g.teamID = t.teamID WHERE g.gameID = ? GROUP BY t.teamID");
$stmt->execute([$game_id]);
$game_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sort teams for consistency (e.g., by teamID)
usort($game_teams, function($a, $b) { return $a['teamID'] <=> $b['teamID']; });

// Add this function if not already present (copied from save_stats.php for consistency)
function translate_pos($input) {
    if ($input < 0) {
        return -$input;
    } else {
        return 100 - $input;
    }
}

// Helper functions
function time_to_seconds($time_str) {
    list($min, $sec) = explode(':', $time_str);
    return (int)$min * 60 + (int)$sec;
}

function seconds_to_time($seconds) {
    $min = floor($seconds / 60);
    $sec = $seconds % 60;
    return sprintf('%02d:%02d', $min, $sec);
}

// Box score logic
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Summary - Connecticut Valley Pee Wee Football League</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/cvpwfl/js/scripts.js"></script>
</head>
<body>
    <header>
        <h2>The Unofficial Page of the</h2>
        <h1>Connecticut Valley Pee Wee Football League</h1>
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
            </div>

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
                            <h2 style="margin: 0px;"><?php echo htmlspecialchars($team_name); ?> Drives</h2>
                            <table class="drive-table">
                                <thead>
                                    <tr>
                                        <th>Drive</th>
                                        <th>Start Time</th>
                                        <th>Start Qtr</th>
                                        <th>Start Pos</th>
                                        <th>End Time</th>
                                        <th>End Qtr</th>
                                        <th>End Pos</th>
                                        <th>Yards</th>
                                        <th>Time</th>
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
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>

