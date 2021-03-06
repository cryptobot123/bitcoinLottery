<?php
/**
 * Created by PhpStorm.
 * User: Frank
 * Date: 10/24/2017
 * Time: 10:16 AM
 */
session_start();

require '/var/www/bitcoinpvp.net/html/vendor/autoload.php';

include "globals.php";
include "function.php";
include "inc/login_checker.php";

$_SESSION['last_url'] = 'user-stats';

$user_search = htmlspecialchars($_GET['user']);

function userStatsLink($user, $page = 1, $gaAsc = 1, $beAsc = 2, $prAsc = 2, $jaAsc = 2, $arrayOrd, $first)
{
    global $base_dir;
    $pos = array_search($first, $arrayOrd);
    array_splice($arrayOrd, $pos, 1);
    array_unshift($arrayOrd, $first);

    $link = $base_dir . "user-stats/" . $user . "/" . $page . "/" . $gaAsc . $beAsc . $prAsc . $jaAsc;
    foreach ($arrayOrd as $i) {
        $link .= $i;
    }

    echo $link;
}

if (!empty($_GET['ga'])) {
    $gaAsc = htmlspecialchars($_GET['ga']);
    filterOnlyNumber($gaAsc, 1, 2, 1);
} else {
    $gaAsc = 1;
}

if (!empty($_GET['be'])) {
    $beAsc = htmlspecialchars($_GET['be']);
    filterOnlyNumber($beAsc, 2, 2, 1);
} else {
    $beAsc = 2;
}

if (!empty($_GET['pr'])) {
    $prAsc = htmlspecialchars($_GET['pr']);
    filterOnlyNumber($prAsc, 2, 2, 1);
} else {
    $prAsc = 2;
}

if (!empty($_GET['ja'])) {
    $jaAsc = htmlspecialchars($_GET['ja']);
    filterOnlyNumber($jaAsc, 2, 2, 1);
} else {
    $jaAsc = 2;
}

if (!empty($_GET['ord'])) {
    $order = $_GET['ord'];
    filterArray($order, 4);
} else {
    $order = array(1, 2, 3, 4);
}

$rowPerPage = 25;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbuser, $dbpass);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    /* Selecting user information */
    $stmt = $conn->prepare('SELECT u.net_profit, u.games_played
                                      FROM user AS u
                                      WHERE u.username = :username');

    $stmt->execute(array('username' => $user_search));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $net_profit = $result['net_profit'];
    $games_played = $result['games_played'];

    $stmt = $conn->prepare('SELECT FIND_IN_SET( net_profit, (
                SELECT GROUP_CONCAT( net_profit
                ORDER BY net_profit DESC )
                FROM user 
                 WHERE user.games_played <> 0)
                ) AS rank
                FROM user
                WHERE user.username = :username');
    $stmt->execute(array('username' => htmlspecialchars($user_search)));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $rank = $row['rank'];

    /* Getting page count */
    $stmt = $conn->prepare('SELECT COUNT(game_id) AS pageCount FROM gamexuser WHERE user_id = 
                                     (SELECT user_id FROM user WHERE username = :username)');
    $stmt->execute(array('username' => $user_search));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pageCount = ceil($result['pageCount'] / $rowPerPage);

    if (isset($_GET['p']) && !empty($_GET['p'])) {
        $page = htmlspecialchars($_GET['p']);
        filterOnlyNumber($page, 1, $pageCount, 1);
    } else
        $page = 1;

    if ($gaAsc == 2)
        $gaString = "gu.game_id ASC";
    else
        $gaString = "gu.game_id DESC";

    if ($beAsc == 2)
        $beString = "gu.bet ASC";
    else
        $beString = "gu.bet DESC";

    if ($prAsc == 2)
        $prString = "gu.profit ASC";
    else
        $prString = "gu.profit DESC";

    if ($jaAsc == 2)
        $jaString = "ga.amount ASC";
    else
        $jaString = "ga.amount DESC";

    $statement = 'SELECT
                                      gu.game_id,
                                      gu.win,
                                      gu.bet,
                                      gu.profit,
                                      ga.amount
                                    FROM user AS u
                                      INNER JOIN gamexuser AS gu
                                        ON u.user_id = gu.user_id                                
                                      INNER JOIN game AS ga
                                        ON gu.game_id = ga.game_id      
                                    WHERE u.username = :username
                                    ORDER BY ';

    for ($i = 0; $i <= 3; $i++) {
        if ($order[$i] == 1)
            $statement = $statement . $gaString;
        if ($order[$i] == 2)
            $statement = $statement . $beString;
        if ($order[$i] == 3)
            $statement = $statement . $prString;
        if ($order[$i] == 4)
            $statement = $statement . $jaString;

        if ($i < 3)
            $statement = $statement . ", ";
    }

    $statement = $statement . " LIMIT :rows OFFSET :the_offset";

    $stmt = $conn->prepare($statement);

    $stmt->execute(array('username' => $user_search, 'rows' => $rowPerPage, 'the_offset' => (($page - 1) * $rowPerPage)));
    $rowTable = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
$title = "User Stats - BitcoinPVP";
include 'inc/header.php'; ?>
<main>
    <div class="container">
        <div class="row top-buffer-15">
            <div class="col l4 offset-l4 m8 offset-m2 s12">
                <div class="input-field col s12">
                    <i class="material-icons prefix">search</i>
                    <input id="search_user" class="validate" type="text" ">
                    <label for="search_user">Username</label>
                </div>
            </div>
        </div>
        <?php if (empty($user_search)): ?>
            <div class="row"></div>
            <div class="row"></div>
            <div class="row"></div>
            <div class="row centerWrap">
                <div class="centeredDiv">
                    <span class="h5Span"><i class="material-icons left">error</i>No user selected.</span>
                </div>
            </div>
        <?php elseif (count($rowTable) > 0): ?>
            <div class="row">
                <h3><b><?php echo $user_search; ?></b></h3>
                <h5><b>Rank# </b><?php if ($rank != 0) echo $rank; else echo "Unranked"; ?></h5>
                <h5><b>Net profit: </b><?php echo($net_profit / 100); ?> bits</h5>
                <h5><b>Games played: </b><?php echo $games_played ?></h5>
            </div>
            <div class="row">
                <div class="col l10 offset-l1 m10 offset-m1 s12">
                    <table class="highlight">
                        <thead>
                        <tr>
                            <th><a href="<?php
                                if ($gaAsc == 2)
                                    userStatsLink($user_search, $page, 1, $beAsc, $prAsc, $jaAsc,
                                        $order, 1);
                                else
                                    userStatsLink($user_search, $page, 2, $beAsc, $prAsc, $jaAsc,
                                        $order, 1); ?>">Game #<i class="tiny material-icons sorter"><?php
                                        if ($gaAsc == 2)
                                            echo 'arrow_drop_down';
                                        else
                                            echo 'arrow_drop_up';
                                        ?></i></a></th>
                            <th><a href="<?php
                                if ($beAsc == 2)
                                    userStatsLink($user_search, $page, $gaAsc, 1, $prAsc, $jaAsc,
                                        $order, 2);
                                else
                                    userStatsLink($user_search, $page, $gaAsc, 2, $prAsc, $jaAsc,
                                        $order, 2); ?>">Bet<i class="tiny material-icons sorter"><?php
                                        if ($beAsc == 2)
                                            echo 'arrow_drop_down';
                                        else
                                            echo 'arrow_drop_up';
                                        ?></i></a></th>
                            <th><a href="<?php
                                if ($prAsc == 2)
                                    userStatsLink($user_search, $page, $gaAsc, $beAsc, 1, $jaAsc,
                                        $order, 3);
                                else
                                    userStatsLink($user_search, $page, $gaAsc, $beAsc, 2, $jaAsc,
                                        $order, 3); ?>">Profit<i class="tiny material-icons sorter"><?php
                                        if ($prAsc == 2)
                                            echo 'arrow_drop_down';
                                        else
                                            echo 'arrow_drop_up';
                                        ?></i></a></th>
                            <th><a href="<?php
                                if ($jaAsc == 2)
                                    userStatsLink($user_search, $page, $gaAsc, $beAsc, $prAsc, 1,
                                        $order, 4);
                                else
                                    userStatsLink($user_search, $page, $gaAsc, $beAsc, $prAsc, 2,
                                        $order, 4); ?>">Jackpot<i class="tiny material-icons sorter"><?php
                                        if ($jaAsc == 2)
                                            echo 'arrow_drop_down';
                                        else
                                            echo 'arrow_drop_up';
                                        ?></i></a></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($rowTable as $item) : ?>

                            <tr class="<?php
                            if ($item['win'] == 1)
                                echo 'win';
                            else
                                echo 'lose';
                            ?>">

                                <td>
                                    <a href="<?php echo $base_dir . "game-info/" . $item['game_id']; ?>"><?php echo $item['game_id']; ?></a>
                                </td>
                                <td><?php echo $item['bet'] / 100; ?> bits</td>
                                <td><?php

                                    if ($item['profit'] > 0)
                                        echo '<span class="win-text">+';
                                    elseif ($item['profit'] == 0)
                                        echo '<span class="neutral-text">';
                                    else
                                        echo '<span class="lose-text">';

                                    echo($item['profit'] / 100); ?> bits</span></td>
                                <td><?php echo $item['amount'] / 100; ?> bits</td>
                            </tr>


                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row centerWrap">
                <div class="centeredDiv">
                    <?php if ($pageCount > 1): ?>
                        <ul class="pagination">
                            <!--                        Go left (pagination) -->
                            <li class="<?php
                            if ($page > 1)
                                echo 'waves-effect';
                            else
                                echo 'disabled';
                            ?>"><a href="<?php
                                if ($page > 1)
                                    userStatsLink($user_search, $page - 1, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]);
                                else
                                    echo '#!';
                                ?>">
                                    <i class="material-icons">chevron_left</i></a></li>
                            <!--Pages-->
                            <?php
                            if ($pageCount <= 15) {
                                for ($i = 1; $i <= $pageCount; $i++) : ?>
                                    <li class="<?php if ($page == $i)
                                        echo 'active';
                                    else
                                        echo 'waves-effect'; ?>"><a
                                                href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                            <?php echo $i; ?></a></li>
                                <?php endfor;
                            } else {
                                if ($page <= 8) {
                                    for ($i = 1; $i <= 14; $i++) :?>
                                        <li class="<?php if ($page == $i)
                                            echo 'active';
                                        else
                                            echo 'waves-effect'; ?>"><a
                                                    href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                                <?php echo $i; ?></a></li>
                                    <?php endfor;
                                    echo '<li>...</li>'; ?>
                                    <li class="<?php if ($page == $pageCount)
                                        echo 'active';
                                    else
                                        echo 'waves-effect'; ?>"><a
                                                href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                            <?php echo $pageCount; ?></a></li>
                                    <?php
                                } else { ?>
                                    <li class="<?php if ($page == 1)
                                        echo 'active';
                                    else
                                        echo 'waves-effect'; ?>"><a
                                                href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                            <?php echo 1; ?></a></li>
                                    <?php
                                    echo '<li>...</li>';
                                    if ($pageCount - $page > 7) {
                                        for ($i = $page - 6; $i <= $page + 6; $i++) :?>
                                            <li class="<?php if ($page == $i)
                                                echo 'active';
                                            else
                                                echo 'waves-effect'; ?>"><a
                                                        href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                                    <?php echo $i; ?></a></li>
                                        <?php endfor;
                                        echo '<li>...</li>'; ?>
                                        <li class="<?php if ($page == $pageCount)
                                            echo 'active';
                                        else
                                            echo 'waves-effect'; ?>"><a
                                                    href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                                <?php echo $pageCount; ?></a></li>
                                        <?php
                                    } else {
                                        for ($i = $pageCount - 13; $i <= $pageCount; $i++) :?>
                                            <li class="<?php if ($page == $i)
                                                echo 'active';
                                            else
                                                echo 'waves-effect'; ?>"><a
                                                        href="<?php userStatsLink($user_search, $i, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]); ?>">
                                                    <?php echo $i; ?></a></li>
                                        <?php endfor;
                                    }
                                }

                            } ?>
                            <!--                        Go right (pagination) -->
                            <li class="<?php
                            if ($page < $pageCount)
                                echo 'waves-effect';
                            else
                                echo 'disabled';
                            ?>"><a href="<?php
                                if ($page < $pageCount)
                                    userStatsLink($user_search, $page + 1, $gaAsc, $beAsc, $prAsc, $jaAsc, $order, $order[0]);
                                else
                                    echo '#!';
                                ?>">
                                    <i class="material-icons">chevron_right</i></a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="row"></div>
            <div class="row"></div>
            <div class="row"></div>
            <div class="row centerWrap">
                <div class="centeredDiv">
                    <span class="h5Span"><i class="material-icons left">error</i>User
                    '<?php echo htmlspecialchars($user_search); ?>'
                    does not exist or has not played enough games yet.</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<!--    Jquery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!-- Compiled and minified JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>
<script>
    $(function () {

        var searchUser = $("#search_user");
        searchUser.on('keypress', function (e) {
            if (e.which === 13) {
                window.location.href = '<?php echo $base_dir; ?>user-stats/' + searchUser.val();
            }
        });
    });

    $(document).ready(function () {
        M.AutoInit();

    });
</script>
<?php include 'inc/footer.php'; ?>


