<?php
// v4.3.1

$inc_path = dirname(__FILE__).'/';



use Symfony\Component\HttpFoundation\Request;

$app->match('/admin/db', function (Request $request) use ($app) {
    global $bv, $inc_path;

    admin_auth();
    error_reporting(0);

    function adminer_object()
    {
        class AdminerSoftware extends Adminer
        {
            public function name()
            {
                // custom name in title and heading
                return 'App';
            }

            public function database()
            {
                // database name, will be escaped by Adminer
                global $bv;
                return $bv->db_name;
            }

            public function credentials()
            {
                global $bv;
                // server, username and password for connecting to database
                return array($bv->dbcreds['host'], $bv->dbcreds['user'], $bv->dbcreds['pass']);
            }

            public function loginForm()
            {
                global $drivers; ?>
<table cellspacing="0">
<tr><th><?php echo lang('System'); ?><td><?php echo html_select("auth[driver]", ['pgsql','mysql'], DRIVER); ?>
<tr><th><?php echo lang('Password'); ?><td><input type="password" name="auth[password]">
</table>
<?php
        echo "<p><input type='submit' value='" . lang('Login') . "'>\n";
                echo checkbox("auth[permanent]", 1, $_COOKIE["adminer_permanent"], lang('Permanent login')) . "\n";
            }
        }

        return new AdminerSoftware;
    }

    include  $inc_path."adminer-en.php";
    return true;
});
