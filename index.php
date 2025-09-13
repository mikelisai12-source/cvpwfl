<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

$stmt = $pdo->query("SELECT * FROM league WHERE lCurrentSeason = (SELECT MAX(lCurrentSeason) FROM league)");
$league = $stmt->fetch(PDO::FETCH_ASSOC);
$season_mode = $league['seasonMode'];
$current_season = $league['lCurrentSeason'];
$current_week = $league['lCurrentWeek'];

$previous_season = $current_season - 1;
$stmt = $pdo->query("SELECT teamID, name, color1, color2, logo FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$teamMap = [];
foreach ($teams as $t) {
    $teamMap[$t['name']] = $t;
}

if ($season_mode == 'season') {
    $stmt = $pdo->prepare("SELECT s.* 
        FROM standings s 
        WHERE s.ssesaonID = ? 
        ORDER BY s.sWins DESC, s.sPointsFor DESC");
    $stmt->execute([$current_season]);
    $standings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query for all games in the season, including quarter scores
    $stmt = $pdo->prepare("SELECT g.gameID, g.week, g.date, 
        MAX(CASE WHEN g.home = 1 THEN t.name ELSE NULL END) as home_team, 
        MAX(CASE WHEN g.home = 0 THEN t.name ELSE NULL END) as away_team,
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
        WHERE g.seasonID = ? 
        GROUP BY g.gameID
        ORDER BY g.week ASC
    ");
    $stmt->execute([$current_season]);
    $all_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch roster summary data
    $stmt = $pdo->query("SELECT t.teamID, t.name, 
        (SELECT COUNT(*) FROM players p WHERE p.seasonID = $current_season AND p.teamID = t.teamID AND p.grade = 6 AND p.draft_season = 0) as returning_6th_count,
        (SELECT COUNT(*) FROM players p WHERE p.seasonID = $current_season AND p.teamID = t.teamID AND p.grade = 5 AND p.draft_season = 0) as returning_5th_count,
        (SELECT COUNT(*) FROM players p WHERE p.seasonID = $current_season AND p.teamID = t.teamID AND p.grade BETWEEN 4 AND 6 AND p.active = 1 AND p.draft_season = 0) as confirmed_count,
        (SELECT COUNT(*) FROM players p WHERE p.seasonID = $previous_season AND p.teamID = t.teamID AND p.grade = 6) as graduated_count,
        (SELECT COUNT(*) FROM players p WHERE p.seasonID = $current_season AND p.teamID = t.teamID AND p.draft_season = $current_season) as drafted_count
        FROM teams t");
    $roster_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connecticut Valley Pee Wee Football League</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/cvpwfl/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        <?php if ($season_mode == 'season'): ?>
        <div class="season-content">
                <div class="season-section">
                    <h2>League Standings</h2>
                    <table style="margin:0px;">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Wins</th>
                                <th>Losses</th>
                                <th>Points For</th>
                                <th>Points Against</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['steam']); ?></td>
                                    <td><?php echo $s['sWins']; ?></td>
                                    <td><?php echo $s['sLosses']; ?></td>
                                    <td><?php echo $s['sPointsFor']; ?></td>
                                    <td><?php echo $s['sPointsAgainst']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                
<div style="display: flex; justify-content: center;">
                    <?php
                    $week_date_key = 'dWeek' . $current_week;
                    $week_date_str = isset($league[$week_date_key]) && $league[$week_date_key] ? (new DateTime($league[$week_date_key]))->format('F jS') : 'TBD';
                    $current_games = array_filter($all_games, function($g) use ($current_week) { return $g['week'] == $current_week; });
                    usort($current_games, function($a, $b) {
                        $a_time = $a['date'] ? strtotime($a['date']) : PHP_INT_MAX;
                        $b_time = $b['date'] ? strtotime($b['date']) : PHP_INT_MAX;
                        return $a_time - $b_time;
                    });
                    ?>
                    <h2 style="margin:0px;">Week <?php echo $current_week.', '.$week_date_str; ?></h2>
</div>                    
<div class="bs-sections">             
<?php foreach ($current_games as $game): ?>
    <?php
    $homeTeam = $teamMap[$game['home_team']];
    $awayTeam = $teamMap[$game['away_team']];
    $time_str = $game['date'] ? (new DateTime($game['date']))->format('g:ia') : 'TBD';
    $matchup_str = htmlspecialchars($game['away_team']) . ' @ ' . htmlspecialchars($game['home_team']) . '<br>' . $time_str;

    $currentQtr = $game['current_qtr'] ?? 0;
    $isGameOver = $game['home_winner'] || $game['away_winner'];
    $isStarted = $currentQtr > 0;
    
    // NEW: Infer the current quarter based on where scores > 0 exist (for either team)
    $inferred_qtr = 0;
    if ($game['away_qtr1'] > 0 || $game['home_qtr1'] > 0) $inferred_qtr = max($inferred_qtr, 1);
    if ($game['away_qtr2'] > 0 || $game['home_qtr2'] > 0) $inferred_qtr = max($inferred_qtr, 2);
    if ($game['away_qtr3'] > 0 || $game['home_qtr3'] > 0) $inferred_qtr = max($inferred_qtr, 3);
    if ($game['away_qtr4'] > 0 || $game['home_qtr4'] > 0) $inferred_qtr = max($inferred_qtr, 4);
    if ($game['away_qtr5'] > 0 || $game['home_qtr5'] > 0) $inferred_qtr = max($inferred_qtr, 5);

    // NEW: Use the max of stored currentQtr and inferred for effective quarter
    $effective_qtr = max($currentQtr, $inferred_qtr);

    // UPDATED: Adjust isStarted to use effective_qtr
    $isStarted = $effective_qtr > 0;

    // UPDATED: Use effective_qtr in conditions
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
    $stmt_away->execute([$game['gameID'], $awayTeam['teamID'], $current_season]);
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
    $stmt_home->execute([$game['gameID'], $homeTeam['teamID'], $current_season]);
    $home_stats = $stmt_home->fetch(PDO::FETCH_ASSOC);

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
  <div class="bs-section">
    <div id="BS_whole_wrap">
        <div id="BS_header">
            <div id="BS_header_score">
                <?php echo $matchup_str; ?>
            </div>
        </div>

        <div id="BS_stats">
            <div id="BS_logo_wrap">
                <div id="BS_logo" style="margin-top:-2px;">
                    <img src="/cvpwfl/images/<?php echo $awayTeam['teamID']; ?>.gif" width="30" height="30" border="0">
                </div>
                <div style="font-size:10px;margin-top:-2px;margin-bottom:-2px;margin-left:10px;font-family:Comic Sans MS, cursive, sans-serif; ">
                    @
                </div>
                <div id="BS_logo">
                    <img src="/cvpwfl/images/<?php echo $homeTeam['teamID']; ?>.gif" width="30" height="30" border="0">
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
                    <img src="/cvpwfl/images/<?php echo $awayTeam['teamID']; ?>.gif" width="30" height="30" border="0">
                </div>
                <div id="BS_logo" style="margin-left:5px;">
                    <img src="/cvpwfl/images/<?php echo $homeTeam['teamID']; ?>.gif" width="30" height="30" border="0">
                </div>
            </div>
            
            
            <div id="BS_recap_wrap">
                <div id="BS_recap">
                    <a href="/cvpwfl/game_recap.php?game_id=<?php echo $game['gameID']; ?>">Full Recap</a>
                </div>
            </div>
        </div>
    </div>   
  </div>
    
    
    
    
<?php endforeach; ?>                     
</div>                
                
        </div>
        <?php else: ?>
            <div class="offseason-content">
                <div class="left-offseason-content">
                    <h2>Offseason Schedule</h2>
                    <ul style="list-style-type: none; line-height: 1.2em; text-align: center;">
                        <li><span class="date"><?php echo $league['dRegistration'] ? (new DateTime($league['dRegistration'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Registration</span></li>
                        <li><span class="date"><?php echo $league['dCamp'] ? (new DateTime($league['dCamp'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Preseason Camp</span></li>
                        <li><span class="date"><?php echo $league['dDraft'] ? (new DateTime($league['dDraft'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Draft</span></li>
                        <li><span class="date"><?php echo $league['dWeigh1'] ? (new DateTime($league['dWeigh1'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Weigh-In 1</span></li>
                        <li><span class="date"><?php echo $league['dWeigh2'] ? (new DateTime($league['dWeigh2'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Weigh-In 2</span></li>
                        <li><span class="date"><?php echo $league['dEquipment'] ? (new DateTime($league['dEquipment'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Equipment Pickup</span></li>
                        <li><span class="date"><?php echo $league['dPractice'] ? (new DateTime($league['dPractice'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">First Practice</span></li>
                    </ul>
                    <h3 style="margin-bottom:5px;">Game Days</h3>
                    <details>
                        <summary style="margin-bottom: 10px;">Week 1: <?php echo $league['dWeek1'] ? (new DateTime($league['dWeek1']))->format('M j') : 'TBD'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Flag: <?php echo $league['w1flag'] ? (new DateTime($league['w1flag']))->format('g:i A') : 'TBD'; ?></li>
                            <li>3rd Grade: <?php echo $league['w1third'] ? (new DateTime($league['w1third']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Opening Ceremony: <?php echo $league['w1openingceremony'] ? (new DateTime($league['w1openingceremony']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 1: <?php echo $league['w1game1'] ? (new DateTime($league['w1game1']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 2: <?php echo $league['w1game2'] ? (new DateTime($league['w1game2']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <details>
                        <summary style="margin-bottom: 10px;">Week 2: <?php echo $league['dWeek2'] ? (new DateTime($league['dWeek2']))->format('M j') : 'TBD'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Flag: <?php echo $league['w2flag'] ? (new DateTime($league['w2flag']))->format('g:i A') : 'TBD'; ?></li>
                            <li>3rd Grade: <?php echo $league['w2third'] ? (new DateTime($league['w2third']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 1: <?php echo $league['w2game1'] ? (new DateTime($league['w2game1']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 2: <?php echo $league['w2game2'] ? (new DateTime($league['w2game2']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <details>
                        <summary style="margin-bottom: 10px;">Week 3: <?php echo $league['dWeek3'] ? (new DateTime($league['dWeek3']))->format('M j') : 'TBD'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Flag: <?php echo $league['w3flag'] ? (new DateTime($league['w3flag']))->format('g:i A') : 'TBD'; ?></li>
                            <li>3rd Grade: <?php echo $league['w3third'] ? (new DateTime($league['w3third']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 1: <?php echo $league['w3game1'] ? (new DateTime($league['w3game1']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 2: <?php echo $league['w3game2'] ? (new DateTime($league['w3game2']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <details>
                        <summary style="margin-bottom: 10px;">Week 4: <?php echo $league['dWeek4'] ? (new DateTime($league['dWeek4']))->format('M j') : 'TBD'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Flag: <?php echo $league['w4flag'] ? (new DateTime($league['w4flag']))->format('g:i A') : 'TBD'; ?></li>
                            <li>3rd Grade: <?php echo $league['w4third'] ? (new DateTime($league['w4third']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 1: <?php echo $league['w4game1'] ? (new DateTime($league['w4game1']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 2: <?php echo $league['w4game2'] ? (new DateTime($league['w4game2']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <details>
                        <summary style="margin-bottom: 10px;">Week 5: <?php echo $league['dWeek5'] ? (new DateTime($league['dWeek5']))->format('M j') : 'TBD'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Flag: <?php echo $league['w5flag'] ? (new DateTime($league['w5flag']))->format('g:i A') : 'TBD'; ?></li>
                            <li>3rd Grade: <?php echo $league['w5third'] ? (new DateTime($league['w5third']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 1: <?php echo $league['w5game1'] ? (new DateTime($league['w5game1']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 2: <?php echo $league['w5game2'] ? (new DateTime($league['w5game2']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <details>
                        <summary style="margin-bottom: 10px;">Week 6: <?php echo $league['dWeek6'] ? (new DateTime($league['dWeek6']))->format('M j') : 'TBD'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Flag: <?php echo $league['w6flag'] ? (new DateTime($league['w6flag']))->format('g:i A') : 'TBD'; ?></li>
                            <li>3rd Grade: <?php echo $league['w6third'] ? (new DateTime($league['w6third']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 1: <?php echo $league['w6game1'] ? (new DateTime($league['w6game1']))->format('g:i A') : 'TBD'; ?></li>
                            <li>Game 2: <?php echo $league['w6game2'] ? (new DateTime($league['w6game2']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <?php if ($league['dWeek7']): ?>
                    <details>
                        <summary style="margin-bottom: 15px;">Week 7: <?php echo $league['dWeek7'] ? (new DateTime($league['dWeek7']))->format('M j') : 'if needed'; ?></summary>
                        <ul style="list-style-type: none; line-height: 1.2em; margin-left: 20px; margin-bottom: 10px; text-align: center; color: graytext;">
                            <li>Playoff: <?php echo $league['w7playoff'] ? (new DateTime($league['w7playoff']))->format('g:i A') : 'TBD'; ?></li>
                        </ul>
                    </details>
                    <?php endif; ?>
                    <ul style="list-style-type: none; line-height: 1.2em; text-align: center; margin-top: 10px;">
                        <li><span class="date"><?php echo $league['dBanquet'] ? (new DateTime($league['dBanquet'], new DateTimeZone('America/New_York')))->format('M j, g:i A') : 'TBD'; ?></span> <span class="label">Banquet</span></li>
                    </ul>
                </div>
                <div class="right-offseason-content">
                    <h2 style="margin:0;"><?php echo htmlspecialchars($current_season . ' ' . $season_mode); ?></h2>
                    <div class="countdown-section">
                        <h3 style="margin-top:0;">Countdown to Preseason Camp</h3>
                        <div id="countdown" data-camp-date="<?php echo $league['dCamp'] ? htmlspecialchars((new DateTime($league['dCamp'], new DateTimeZone('America/New_York')))->format('c')) : ''; ?>"></div>
                    </div>
                    <div class="link-buttons">
                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1 || isset($_SESSION['headCoach']) && $_SESSION['headCoach'] == 1 || isset($_SESSION['asstCoach']) && $_SESSION['asstCoach'] == 1): ?>
                            <a href="/cvpwfl/coach/available_rookies.php" class="link-button">Available Rookies</a>
                            <a href="/cvpwfl/coach/draft.php" class="link-button">Draft</a>
                            <a href="/cvpwfl/coach/edit_players.php" class="link-button">Edit Roster</a>
                        <?php endif; ?>
                    </div>
                    <h3>Roster Summary</h3>
                    <table class="roster-summary">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Graduated</th>
                                <th>Returning<br>6th Grade</th>
                                <th>Returning<br>5th Grade</th>
                                <th>Confirmed <span class="confirmed">&#10003;</span><br>Returning</th>
                                <th>Drafted</th>
                                <th>Roster<br>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster_summary as $summary): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($summary['name']); ?></td>
                                    <td><?php echo $summary['graduated_count']; ?></td>
                                    <td><?php echo $summary['returning_6th_count']; ?></td>
                                    <td><?php echo $summary['returning_5th_count']; ?></td>
                                    <td><?php echo $summary['confirmed_count'] . ' / ' . ($summary['returning_6th_count'] + $summary['returning_5th_count']); ?></td>
                                    <td><?php echo $summary['drafted_count']; ?></td>
                                    <td><?php echo $summary['confirmed_count'] + $summary['drafted_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        <div class="team-sections">
            <?php foreach ($teams as $team): ?>
                <div class="team-section" style="background: <?php echo htmlspecialchars($team['color1']); ?>; border: 5px solid <?php echo htmlspecialchars($team['color2']); ?>;">
                    <img src="/cvpwfl/images/<?php echo htmlspecialchars($team['logo']); ?>" alt="<?php echo htmlspecialchars($team['name']); ?> Logo" class="team-logo-index">
                    <?php
                    $stmt_grad = $pdo->prepare("SELECT * FROM players WHERE seasonID = ? AND teamID = ? AND grade = 6");
                    $stmt_grad->execute([$previous_season, $team['teamID']]);
                    $graduated = $stmt_grad->fetchAll(PDO::FETCH_ASSOC);

                    $stmt_return = $pdo->prepare("SELECT * FROM players WHERE seasonID = ? AND teamID = ? AND grade BETWEEN 4 AND 6 AND draft_season = 0 ORDER BY grade DESC");
                    $stmt_return->execute([$current_season, $team['teamID']]);
                    $returning = $stmt_return->fetchAll(PDO::FETCH_ASSOC);

                    $confirmed = array_reduce($returning, function($carry, $p){ return $carry + ($p['active'] == 1 ? 1 : 0); }, 0);
                    $total_players = $confirmed;

                    $grade_counts = ['4' => 0, '5' => 0, '6' => 0];
                    foreach ($returning as $p) {
                        if ($p['active'] == 1 && isset($grade_counts[$p['grade']])) {
                            $grade_counts[$p['grade']]++;
                        }
                    }

                    $players_list = [];
                    if ($league['curRd'] == 13) {
                        $stmt_all_active = $pdo->prepare("SELECT * FROM players WHERE seasonID = ? AND teamID = ? AND active = 1 AND grade BETWEEN 4 AND 6 ORDER BY jerseyNumber ASC");
                        $stmt_all_active->execute([$current_season, $team['teamID']]);
                        $players_list = $stmt_all_active->fetchAll(PDO::FETCH_ASSOC);

                        $total_players = count($players_list);

                        $grade_counts = ['4' => 0, '5' => 0, '6' => 0];
                        foreach ($players_list as $p) {
                            if (isset($grade_counts[$p['grade']])) {
                                $grade_counts[$p['grade']]++;
                            }
                        }
                    }
                    ?>
                    <?php if ($league['curRd'] != 13): ?>
                        <h3 style="margin-bottom:5px;">Graduated Players (<?= count($graduated) ?>)</h3>
                        <ul>
                            <?php foreach ($graduated as $player): ?>
                                <li><?= htmlspecialchars($player['firstName'] . ' ' . $player['lastName']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h3 style="margin-bottom:0;">Returning Players</h3>
                        <h4 style="margin:5px;"><?= $confirmed ?> of <?= count($returning) ?> Confirmed</h4>
                        <ul style="margin-top:10px;">
                            <?php foreach ($returning as $player): ?>
                                <li><?= htmlspecialchars($player['firstName'] . ' ' . $player['lastName'] . ' (' . $player['grade'] . ')') ?><?php if ($player['active'] == 1): ?> <span class="confirmed">&#10003;</span><?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <h3>Total Players: <?php echo $total_players; ?></h3>
                        <ul style="margin-top:10px;">
                            <?php foreach ($players_list as $player): ?>
                                <li><?= htmlspecialchars($player['jerseyNumber'] . ' ' . $player['firstName'] . ' ' . $player['lastName'] . ' (' . $player['grade'] . ')') ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h4 style="margin-top:5px; margin-bottom:0;">6th Grade: <?= $grade_counts['6'] ?></h4>
                        <h4 style="margin-top:0; margin-bottom:0;">5th Grade: <?= $grade_counts['5'] ?></h4>
                        <h4 style="margin-top:0; margin-bottom:0;">4th Grade: <?= $grade_counts['4'] ?></h4>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <footer>
        <p>© 2025 Connecticut Valley Pee Wee Football League. All rights reserved.</p>
    </footer>
</body>
</html>