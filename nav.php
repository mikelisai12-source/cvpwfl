<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/config.php';

// Fetch league data to determine season mode
$stmt = $pdo->query("SELECT * FROM league WHERE lCurrentSeason = (SELECT MAX(lCurrentSeason) FROM league)");
$league = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<nav>
    <ul>
        <li><a href="/cvpwfl/index.php">Home</a></li>
        <li><a href="/cvpwfl/team_stats.php">Team Stats</a></li>
        <li class="dropdown">
            <a href="#" class="dropbtn">Player Stats</a>
            <div class="dropdown-content">
                <a href="/cvpwfl/player_stats_rushing.php">Rushing</a>
                <a href="/cvpwfl/player_stats_passing.php">Passing</a>
                <a href="/cvpwfl/player_stats_receiving.php">Receiving</a>
                <a href="/cvpwfl/player_stats_defense.php">Defense</a>
            </div>
        </li>
        <li><a href="/cvpwfl/official.php">Official</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['admin']): ?>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Admin</a>
                    <div class="dropdown-content">
                        <a href="/cvpwfl/admin/edit_users.php">Edit Users</a>
                        <a href="/cvpwfl/admin/schedule.php">Schedule</a>
                        <a href="/cvpwfl/admin/draft_order.php">Draft Order</a>
                        <a href="/cvpwfl/admin/edit_league_info.php">Edit League Info</a>
                    </div>
                </li>
            <?php endif; ?>
            <?php if ($_SESSION['headCoach'] || $_SESSION['asstCoach'] || $_SESSION['admin']): ?>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Coach</a>
                    <div class="dropdown-content">
                        <a href="/cvpwfl/coach/edit_players.php">Edit Roster</a>
                        <?php if (isset($league['seasonMode']) && $league['seasonMode'] == 'offseason'): ?>
                            <a href="/cvpwfl/coach/draft.php">Draft</a>
                            <a href="/cvpwfl/coach/available_rookies.php">Available Rookies</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>
            <?php if ($_SESSION['stats'] || $_SESSION['admin']): ?>
                <li><a href="/cvpwfl/stats/stats_collection.php">Stats</a></li>
            <?php endif; ?>
            <li><a href="/cvpwfl/auth/profile.php">Profile</a></li>
        <?php endif; ?>
    </ul>
</nav>