<?php
/**
 * Created by PhpStorm.
 * User: luckiestguyever
 * Date: 11/22/17
 * Time: 1:12 PM
 */
?>
<footer class="page-footer grey lighten-4">
    <div class="container">
        <div class="row">
            <div class="col l6 s12">
                <h5 class="black-text">License</h5>
                <p class="black-text text-lighten-4">No License Yet</p>
                <p class="black-text">JavaScript is required</p>
            </div>
            <div class="col l4 offset-l2 s12">
                <h5 class="black-text">Useful Links</h5>
                <ul>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>help">Help</a></li>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>rank">Ranking</a></li>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>stats">Server Stats</a></li>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>game_info">Game Info</a></li>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>games_history">Games
                            History</a></li>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>user_stats">User Stats</a>
                    </li>
                    <li><a class="black-text text-lighten-3"
                           href="<?php echo $base_dir; ?>registration">Registration</a></li>
                    <li><a class="black-text text-lighten-3" href="<?php echo $base_dir; ?>account">My Account</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <div class="container black-text">
            © <?php echo date('Y'); ?> Copyright BitcoinPVP
        </div>
    </div>
</footer>
<script>
    $(document).ready(function () {
        $('.dropdown-trigger').dropdown({constrainWidth: false});
    });
</script>
</body>
</html>