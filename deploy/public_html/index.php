<?php
/**
 * Atlanta PHP Contests Micro-site
 *
 * @copyright Copyright (c) 2009 Atlanta PHP, LLC
 * @license http://github.com/atlantaphp/mysql-training-contest/raw/master/LICENSE New BSD License
 */

ini_set('display_errors', 0);
require_once 'Inspekt.php';
require_once 'recaptcha/recaptchalib.php';

// This pulls the reCAPTCHA private key from an environment variable. Set this
// in a .htaccess file in the web root, like so:
//
//     SetEnv RECAPTCHA_PRIVATE_KEY xxxxxxxxxxxxxxxxxxxxx
//
define('RECAPTCHA_PRIVATE_KEY', getenv('RECAPTCHA_PRIVATE_KEY'));
define('RECAPTCHA_PUBLIC_KEY', '6LdqewkAAAAAAEX_hHRqeQrCq8My6LPr9V72zMyD');

$endDate = strtotime('November 24, 2009 11:59 PM EST');
$db = new SQLite3('../../db/mysql-contest.db');
$postCage = Inspekt::makePostCage();
$errors = array();

// Determine whether the contest is over
$isOver = false;
if (time() > $endDate) {
    $isOver = true;
}

if (!$isOver) {
    // Determine whether this user has already registered
    $isSignedUp = false;
    if (isset($_COOKIE['atlphp_mysql_contest'])) {
        $isSignedUp = true;
    } else {
        $stmt = $db->prepare('SELECT email FROM entries WHERE ip_addr = :ip_addr');
        $stmt->bindValue(':ip_addr', $_SERVER['REMOTE_ADDR']);
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            $isSignedUp = true;
        }
    }

    // Process the form post
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$isSignedUp) {

        $clean = array();
        $clean['name'] = $postCage->testRegex('name', "/^[\w\'\- ]+$/iu");
        $clean['email'] = $postCage->testEmail('email');

        // Check for errors
        if ($clean['name'] === false) {
            $errors['name'] = 'Please enter a valid name.';
        }
        if ($clean['email'] === false) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // Check the CAPTCHA
        $response = recaptcha_check_answer(
            RECAPTCHA_PRIVATE_KEY,
            $_SERVER['REMOTE_ADDR'],
            $postCage->getRaw('recaptcha_challenge_field'),
            $postCage->getRaw('recaptcha_response_field'));

        if (!$response->is_valid) {
            $errors['captcha'] = $response->error;
        }

        // Check to see if this email already exists
        $stmt = $db->prepare('SELECT email FROM entries WHERE email = :email');
        $stmt->bindValue(':email', $clean['email']);
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            setcookie('atlphp_mysql_contest', 1, time()+2592000);
            header('Location: /');
        }

        // If there aren't any errors, then save the data
        if (!$errors) {
            $stmt = $db->prepare('
                INSERT INTO entries (
                    name,
                    email,
                    ip_addr,
                    time)
                VALUES (
                    :name,
                    :email,
                    :ip_addr,
                    :time)');

            $stmt->bindValue(':name', $clean['name']);
            $stmt->bindValue(':email', $clean['email']);
            $stmt->bindValue(':ip_addr', $_SERVER['REMOTE_ADDR']);
            $stmt->bindValue(':time', time());
            $result = $stmt->execute();

            if ($result === false) {
                $errors['db'] = $db->lastErrorMsg();
            } else {
                setcookie('atlphp_mysql_contest', 1, time()+2592000);
                $isSignedUp = true;
            }
        }
    }
}

?>
<!doctype html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Atlanta PHP - MySQL Training Raffle</title>
    <link href="css/style.css" type="text/css" rel="stylesheet" media="all" charset="utf-8" />
    <script type="text/javascript">
        var RecaptchaOptions = {
            theme : 'white',
            tabindex : 3,
        };
    </script>
</head>
<body>

    <div id="atlphp">

        <h1>Atlanta PHP</h1>

    </div>

    <div id="details">

        <h2>
            Register for a chance to win a free pass to Performance Optimization
            for MySQL with InnoDB and XtraDB.
        </h2>

        <div id="register">

            <?php if ($isOver): ?>

                <p>
                    The contest is now over. Thank you for participating!
                </p>

                <p>
                    Sign up for the workshop <a href="http://percona-ga-atl.eventbrite.com/" target="top">Performance
                    Optimization for MySQL with InnoDB and XtraDB</a> presented by <strong>Morgan
                    Tocker</strong> and hosted by <a href="http://www.percona.com/" target="top">Percona, Inc.</a>
                </p>

                <p>
                    Please <a href="http://meetup.atlantaphp.org/calendar/11898004/">join us
                    at the Atlanta PHP meeting on December 10, 2009 at 7 PM</a>.
                    Morgan Tocker will be presenting "Quick Wins: Performance
                    Tuning + 3rd Party Patches for MySQL."
                </p>


            <?php elseif ($isSignedUp): ?>

                <p>
                    Thank you for registering! We'll notify you on November 25,
                    2009 if you're the winner of the workshop pass.
                </p>

                <p>
                    In the meantime, please <a href="http://meetup.atlantaphp.org/calendar/11898004/">RSVP
                    for the Atlanta PHP meeting on December 10, 2009 at 7 PM</a>.
                    Morgan Tocker will be presenting "Quick Wins: Performance
                    Tuning + 3rd Party Patches for MySQL."
                </p>

            <?php else: ?>

                <p>
                    Enter your name and email address for a chance to win a pass to
                    the workshop <a href="http://percona-ga-atl.eventbrite.com/" target="top">Performance
                    Optimization for MySQL with InnoDB and XtraDB</a> presented by <strong>Morgan
                    Tocker</strong> and hosted by <a href="http://www.percona.com/" target="top">Percona, Inc.</a>
                </p>

                <form id="registration" method="post" action="/">

                    <div id="errorMsg">
                        <?php if ($errors): ?>
                            <p class="error">
                                <?php if (isset($errors['db'])): ?>
                                    Database error: <?php echo htmlentities($errors['db'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php else: ?>
                                    Your submission has errors. Please correct them and try again.
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <label for="name">Your name:</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlentities($postCage->getRaw('name'), ENT_QUOTES, 'UTF-8'); ?>" />
                    <span class="error" id="nameError"><?php if (isset($errors['name'])): echo $errors['name']; endif; ?></span><br />

                    <label for="email">Your email:</label>
                    <input type="text" name="email" id="email" value="<?php echo htmlentities($postCage->getRaw('email'), ENT_QUOTES, 'UTF-8'); ?>" />
                    <span class="error" id="emailError"><?php if (isset($errors['email'])): echo $errors['email']; endif; ?></span><br />

                    <div id="recaptcha_widget">
                        <?php echo recaptcha_get_html(RECAPTCHA_PUBLIC_KEY, (isset($errors['captcha']) ? $errors['captcha'] : null)); ?>
                    </div>

                    <label for="submit">&nbsp;</label>
                    <input type="submit" value="Register!">

                </form>

            <?php endif; ?>

        </div>

        <div id="info">

            <h3>Contest details</h3>

            <p>
                The contest opens at 12:01 AM EST on Wednesday, November 18, 2009 and
                runs until 11:59 PM EST on Tuesday, November 24, 2009. A single winner
                will be selected in a random drawing and announced on Wednesday,
                November 25, 2009.
            </p>

            <p>
                The winner will receive one (1) pass to Performance Optimization for MySQL
                with InnoDB and XtraDB to be held at MicroTek on December 10, 2009
                from 9:30 AM EST to 5:00 PM EST. Morgan Tocker will lead this intensive
                workshop, covering tuning MySQL when using the InnoDB and XtraDB
                storage engines.
            </p>

            <p>
                For more details, please ask on the <a href="http://groups.google.com/group/atlantaphp">Atlanta
                PHP Google Group</a> (for the benefit of all our members) or send
                email to contests at atlantaphp.org.
            </p>

            <h3>Workshop details</h3>

            <p>
                <strong>What:</strong>
                <em>Performance Optimization for MySQL with InnoDB and XtraDB</em><br />

                <strong>When:</strong>
                <em>Thursday, December 10, 2009 from 9:30 AM - 5:00 PM (ET)</em><br />

                <strong>Where:</strong>
                <em>MicroTek - Atlanta</em><br />

                <strong>Hosted by:</strong>
                <em><a href="http://www.percona.com/" target="top">Percona, Inc.</a></em><br />

                <strong>Register:</strong>
                <em><a href="http://percona-ga-atl.eventbrite.com/" target="top">Sign up at Eventbrite</a></em>
            </p>

            <h3>Morgan Tocker presents at the Atlanta PHP December meeting!</h3>

            <p>
                Following the December 10th workshop, Morgan Tocker will present
                "Quick Wins: Performance Tuning + 3rd Party Patches for MySQL" at
                the Atlanta PHP December meeting on December 10th at 7:00 PM EST.
            </p>

            <p>
                <strong>
                    For more details and to RSVP for the Atlanta PHP December meeting,
                    <a href="http://meetup.atlantaphp.org/calendar/11898004/">please visit
                    Meetup.com</a>.
                </strong>
            </p>


        </div>
    </div>

    <div id="copyright">
        <p>Copyright &copy; 2009 <a href="http://atlantaphp.org/">Atlanta PHP, LLC.</a></p>
    </div>

</body>
</html>
