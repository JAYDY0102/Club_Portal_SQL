<?php
session_start();
$SignedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
$secret = require 'secret.php';
function buildGoogleAuthUrl(string $clientId, string $redirectUri, string $state): string {
    $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
    ]);

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}

$studentUrl = buildGoogleAuthUrl(
        $secret['google_client_id'],
        $secret['google_redirect_uri'],
        'student'
);

$staffUrl = buildGoogleAuthUrl(
        $secret['google_client_id'],
        $secret['google_redirect_uri'],
        'staff'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal - Sign In</title>
    <link rel="stylesheet" href="../styles.css"/>
</head>
<body>
<div id="top-nav-bar" class="classic">
    <div id="pagetop" class="sis-bar notranslate primary-white">
        <a id="sis-logo" href="../index.php" class="sis-bar-item sis-button sis-left" title="Home">
            <i class="fa" aria-hidden="true">1</i>
        </a>
        <nav class="tnb-desktop-nav sis-bar-item">
            <a id="inactive" href="../index.php" class="sis-bar-item sis-padding-16 sis-button ">Home</a>
            <a id="inactive" onClick="alert('You do not have permissions to use the Feed')"
               class="sis-bar-item sis-padding-16 sis-button">Feed</a>
            <a id="inactive" onClick="alert('You do not have permissions to use the Calendar')"
               class="sis-bar-item sis-padding-16 sis-button ">Calendar</a>
            <a id="inactive" onClick="alert('You do not have permissions to use the Dashboard')"
               class="sis-bar-item sis-padding-16 sis-button">Dashboard</a>
        </nav>
        <div class="tnb-right-section">
            <a href="signin.php">
                <div id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button"
                     title="Sign in to your account">
                    <span class="button-text">Sign In</span>
                </div>
            </a>
            <a href="../assets/site_images/fair_map.png" class="tnb-right-side-btn sis-bar-item sis-button sis-right" title="Club Fair Map" aria-label="Club Fair Map">Fair Map</a>
        </div>
    </div>
</div>
<div class="topnavbackground"></div>
<div class="topnavcontainer">
    Placeholder for announcements
</div>
<div class="background-image"></div>
<div class="contentcontainer">
    <div class="belowtopnavcontainer">
        <div class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Sign in with Google</h2>
                    <p>Please choose the account type you are signing in as.</p>
                </div>
                <div class="sign-button-group">
                    <a href="<?=htmlspecialchars($studentUrl)?>" class="sis-button main-button">Student Sign In</a>
                    <a href="<?=htmlspecialchars($staffUrl)?>" class="sis-button main-button">Staff Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class ="wrappercontainer">
    <div class="footerwrapper">
        <div class="spacefooter">
            <div class="footerlinks" style="overflow:hidden;">
                <div class="footerlinks_1">
                    <a href="https://tigerclubs.org/index.php" aria-label="Tigerclubs.org">
                        <i class="fa fa-logo">1</i>
                    </a>
                </div>
                <div class="footerlinks_1">
                    <a href="https://forms.gle/mgUxnthy2izYn4yi8" title="Submit a request to add an image on the main banner">BANNER REQUEST</a>
                </div>
                <div class="footerlinks_1">
                    <a href="https://forms.gle/QwJxodQaQRro4cqB7" title="Submit an interest form cooperatively create a website for your own club with Coding Club">INTEREST FORM</a>
                </div>
                <div class="footerlinks_1">
                    <a href="https://forms.gle/KFqJG2EHqEsWUuB47" title="Submit a bug report that you have encountered on the website">BUG REPORT</a>
                </div>
                <div class="footerlinks_1">
                    <a href="" title="Contact Us!">CONTACT US</a>
                </div>
            </div>
            <div class="footertext">
                Tigerclubs.org is made to promote connectivity across all clubs of SIS. It prioritizes accessibility over functionality.
                <br>
                Select members of Coding Club are constantly working to improve the website, but we cannot warrant that it will be free of bugs.
                <br>
                Please use the links below to submit any main banner request, club-specific website interest form, or bug reports if you happen to notice any.
                <br>
                <br>
                <a href="https://github.com/JAYDY0102/Club_Portal_SQL/blob/master/LICENSE">MIT License</a>
                of the website's source code.
            </div>
        </div>
    </div>
</div>
</body>
</html>
