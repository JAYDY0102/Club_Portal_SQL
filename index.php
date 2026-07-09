<?php
session_start();

$secret = require __DIR__ . '/auth/secret.php';
$SignedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;

$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

$role = null;
$admin = null;

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

if($SignedIn){
    $email = $user['Email'];
    $stmt = $conn->prepare("SELECT Role, AdminFlag FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $role = $row['Role'];
    $admin = $row['AdminFlag'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal</title>
    <link rel="stylesheet" href="styles.css"/>
</head>
<body>
<div id="top-nav-bar" class="classic">
    <div id="pagetop" class="sis-bar notranslate primary-white">
        <a id="sis-logo" href="index.php" class="sis-bar-item sis-button sis-left" title="Home">
            <i class="fa" aria-hidden="true">1</i>
        </a>
        <nav class="tnb-desktop-nav sis-bar-item">
            <a id="active" href="index.php" class="sis-bar-item sis-padding-16 sis-button ">Home</a>
            <?php if ($SignedIn): ?>
                <a id="inactive" href="feed" class="sis-bar-item  sis-padding-16 sis-button">Feed</a>
                <a id="inactive" href="calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
                <?php if ($admin == '1'): ?>
                    <a id="inactive" href="dashboard/admin.php" class="sis-bar-item sis-padding-16 sis-button">Admin
                        Dashboard</a>
                <?php elseif ($role == 'advisor'): ?>
                    <a id="inactive" href="dashboard/advisor.php" class="sis-bar-item sis-padding-16 sis-button">Advisor
                        Dashboard</a>
                <?php elseif ($role == 'executive'): ?>
                    <a id="inactive" href="dashboard/executive.php" class="sis-bar-item sis-padding-16 sis-button">Executive
                        Dashboard</a>
                <?php else: ?>
                    <a id="inactive" onClick="alert('You do not have permissions to use the Dashboard')"
                       class="sis-bar-item sis-padding-16 sis-button">Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a id="inactive" onClick="alert('You do not have permissions to use the Feed')"
                   class="sis-bar-item sis-padding-16 sis-button">Feed</a>
                <a id="inactive" onClick="alert('You do not have permissions to use the Calendar')"
                   class="sis-bar-item sis-padding-16 sis-button ">Calendar</a>
                <a id="inactive" onClick="alert('You do not have permissions to use the Dashboard')"
                   class="sis-bar-item sis-padding-16 sis-button">Dashboard</a>
            <?php endif; ?>
        </nav>
        <div class="tnb-right-section">
            <?php if ($SignedIn): ?>
            <a href="auth/signout.php">
                <div id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button"
                     title="Sign in to your account">
                    <span class="button-text">Sign Out</span>
                </div>
            </a>
            <?php else: ?>
            <a href="auth/signin.php">
                <div id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button"
                     title="Sign in to your account">
                    <span class="button-text">Sign In</span>
                </div>
            </a>
            <?php endif; ?>
            <a href="assets/site_images/fair_map.png" class="tnb-right-side-btn sis-bar-item sis-button sis-right" title="Club Fair Map" aria-label="Club Fair Map">Fair Map</a>
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
            <div class="main-banner">
                <div class="banner-text">
                    <h1>Discover Clubs at SIS</h1>
                    <p>Find your passion, make lasting memories, and develop new skills through our diverse range of student clubs.</p>
                </div>
            </div>
            <div class="content">
                <div class="section-head">
                    <h2>Discover Clubs</h2>
                    <p>Browse clubs and click a card to see more details.</p>
                </div>
                <div class="filters">
                    <div class="filter-row">
                        <button class="chip active" data-filter="all">All</button>
                        <button class="chip" data-filter="STEM">STEM</button>
                        <button class="chip" data-filter="Academic">Academic</button>
                        <button class="chip" data-filter="Arts & Culture">Arts & Culture</button>
                        <button class="chip" data-filter="Community Service">Community Service</button>
                        <button class="chip" data-filter="Journalism">Journalism</button>
                        <button class="chip" data-filter="Sports">Sports</button>
                    </div>
                    <div class="filter-row">
                        <button class="chip active" data-filter="all">All Days</button>
                        <button class="chip" data-filter="Monday">Monday</button>
                        <button class="chip" data-filter="Wednesday">Wednesday</button>
                        <button class="chip" data-filter="Thursday (A)">Thursday (A)</button>
                        <button class="chip" data-filter="Thursday (B)">Thursday (B)</button>
                        <button class="chip" data-filter="Friday">Friday</button>
                        <button class="chip" data-filter="Other">Other</button>
                    </div>
                </div>
                <div class="layout">
                    <section class="grid" id="clubGrid">
                        <?php
                        $sql = "SELECT DirName, Name, ClubType, MemberCount, MeetDay, Summary FROM clubs";
                        $result = $conn->query($sql);
                        if (!$result) {
                            die("Query failed: " . $conn->error);
                        }

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $clubTypeTags = '';
                                $clubTypes = array_map('trim', explode(',', $row["ClubType"]));

                                foreach ($clubTypes as $type) {
                                    if ($type !== '') {
                                        $clubTypeTags .= "<span class='card-tag'>" . htmlspecialchars($type) . "</span>";
                                    }
                                }
                                echo "
<article class='card'>
    <div class='card-banner'>
        <img class='card-image' src='assets/banners/" . $row["DirName"] . ".png' alt='" . $row["Name"] . "'>
        <div class='card-tags'>
            $clubTypeTags
            <span class='card-tag'>" . $row["MeetDay"] . "</span>
        </div>
    </div>
    <div class='card-content'>
        <div class='card-meta'>
            <h3>" . $row["Name"] . "</h3>
            <!--<h3 id='count'>" . $row["MemberCount"] . " Members</h3>-->
        </div>
        <div class='card-summary'>" . $row["Summary"] . "</div>
    </div>
</article>";
                            }
                        } else {
                            echo "0 results";
                        }
                        $conn->close();
                        ?>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>