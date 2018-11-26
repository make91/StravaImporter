<?php

require 'vendor/autoload.php';

//********** somewhere in your routes or controllers...
//functionality here
use fjgarlin\StravaImporter;

$credentials = json_decode(file_get_contents('.cred'));
$config = [
    'id' => $credentials->id,
    'secret' => $credentials->secret,
    'redirect_url' => 'http://192.168.0.13/StravaImporter/'
];
$importer = new StravaImporter($config);

$code = isset($_GET['code']) ? $_GET['code'] : false;
if ($code) {
    $importer->authorize($code);
}

$authorized = $importer->authorized();
$authorize_url = ($authorized) ? false : $importer->getAuthorizeUrl();
$athlete = $importer->getAthlete();

$res = null;
if ($authorized and !empty($_POST) and !empty($_FILES)) {
    $res = $importer->upload($_FILES['activities']['tmp_name']);
}
//********** somewhere in your routes or controllers...

?>
<!doctype html>
<html>
    <head>
        <title>Strava Uploader</title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </head>
    <body>
        <div class="container">
            <h1>
                Strava CSV Uploader <br>
                <small>Upload your CSV with activities to Strava.</small>
            </h1>
            <hr>

            <?php if (!$authorized): ?>
                <p>
                    <a href="<?php echo $authorize_url; ?>" title="Authorize">
                        <img src="img/btn_strava_connectwith_orange.png" alt="Connect with Strava">
                    </a>
                </p>
            <?php else: ?>
                <div class="row">
                    <div class="col-sm-6">
                        <p>
                            Use same format as <a href="https://www.runningahead.com/help/custom_csv">RunningAhead.com</a> custom CSV. Attach the file and click on Submit. Some notes:
                            <ul>
                                <li><b>Time</b> : Must be in HH:MM:SS or HH:MM format</li>
                                <li><b>Name</b> : will be based on type of workout and course if exists</li>
                            </ul>
                            Only date, time, activity, workout, distance, duration, course and notes are sent to Strava, rest of data is ignored.
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <form method="post" action="" class="well" enctype="multipart/form-data">
                            <input type="hidden" name="_submitted">
                            <?php if ($athlete): ?>
                                <div class="thumbnail text-center">
                                    <div class="caption">
                                        <h3><?php echo htmlspecialchars($athlete->firstname . " " . $athlete->lastname); ?></h3>
                                        <p><?php echo htmlspecialchars($athlete->email); ?></p>
                                        <p><a target="_blank" href="https://www.strava.com/athletes/<?php echo (int)$athlete->id; ?>" class="btn btn-primary" role="button">View profile</a></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="activities">File input</label>
                                <input type="file" id="activities" required="required" name="activities">
                                <p class="help-block">CSV containing the activities.</p>
                            </div>
                            <button type="submit" class="btn btn-default">Submit</button>
                        </form>
                    </div>
                </div>

                <?php if (!is_null($res)): ?>
                    <hr>
                    <?php if ($res->status): ?>
                        <script>console.dir(<?php echo $res->added ?>);</script>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($res->message); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            Couldn't upload activities. <?php echo htmlspecialchars($res->message); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div><!-- .container -->
        <footer class="text-right container">
            <hr>
            <img height="40" src="img/api_logo_pwrdBy_strava_horiz_light.png" alt="Powered by Strava">
        </footer>
    </body>
</html>
