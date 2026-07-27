<?php
session_start();

$secret = require __DIR__ . '/../auth/secret.php';
$user = $_SESSION['user'] ?? null;
$SignedIn = isset($_SESSION['user']);

$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

$role = null;
$admin = null;
function e($value): string
{
    return htmlspecialchars(($value ?? ''), ENT_QUOTES, 'UTF-8');
}
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}
$email = $user['Email'];
$stmt = $conn->prepare("SELECT Role, AdminFlag FROM users WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$role = $row['Role'];
$admin = $row['AdminFlag'];

if (!$SignedIn) {
    header('Location: ../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal - Calendar</title>
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
            <a id="inactive" href="../feed" class="sis-bar-item  sis-padding-16 sis-button">Feed</a>
            <a id="active" href="../calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
            <?php if ($admin == '1'): ?>
                <a id="inactive" href="../dashboard/admin.php" class="sis-bar-item sis-padding-16 sis-button">Admin
                    Dashboard</a>
            <?php elseif ($role == 'advisor'): ?>
                <a id="inactive" href="../dashboard/advisor.php" class="sis-bar-item sis-padding-16 sis-button">Advisor
                    Dashboard</a>
            <?php elseif ($role == 'executive'): ?>
                <a id="inactive" href="../dashboard/executive.php" class="sis-bar-item sis-padding-16 sis-button">Executive
                    Dashboard</a>
            <?php else: ?>
                <a id="inactive" onClick="alert('You do not have permissions to use the Dashboard')"
                   class="sis-bar-item sis-padding-16 sis-button">Dashboard</a>
            <?php endif; ?>
        </nav>
        <a id="inactive" class="sis-bar-item sis-button sis-padding-16 mobile-menu" data-state="closed">
            Menu ▾
        </a>
        <div class="spacer sis-bar-item">
            <div class="space-inner"></div>
        </div>
        <div class="tnb-right-section">
            <div id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button"
                 title="Sign out of your account" onClick="window.location.href='auth/signout.php'">
                <span class="button-text">Sign Out</span>
            </div>
            <a href="../assets/site_images/fair_map.png" class="tnb-right-side-btn sis-bar-item sis-button sis-right"
               title="Club Fair Map" aria-label="Club Fair Map">Fair Map</a>
        </div>
    </div>
    <nav id="tnb-mobile-nav" class="tnb-mobile-nav">
        <div class="mobile-container">
            <div class="tnb-mobile-nav-section" data-section="home" onClick="window.location.href='../index.php'">
                <div class="sis-button">
                    <span class="tnb-title">Home</span>
                </div>
            </div>
            <div class="tnb-mobile-nav-section" data-section="feed" onClick="window.location.href='../feed'">
                <div class="sis-button">
                    <span class="tnb-title">Feed</span>
                </div>
            </div>
            <div class="tnb-mobile-nav-section" data-section="calendar" onClick="window.location.href='../calendar'">
                <div class="sis-button">
                    <span class="tnb-title">Calendar</span>
                </div>
            </div>
            <?php if ($admin == '1'): ?>
                <div class="tnb-mobile-nav-section" data-section="admin"
                     onClick="window.location.href='../dashboard/admin.php'">
                    <div class="sis-button">
                        <span class="tnb-title">Admin Dashboard</span>
                    </div>
                </div>
            <?php elseif ($role == 'advisor'): ?>
                <div class="tnb-mobile-nav-section" data-section="advisor"
                     onClick="window.location.href='../dashboard/advisor.php'">
                    <div class="sis-button">
                        <span class="tnb-title">Advisor Dashboard</span>
                    </div>
                </div>
            <?php elseif ($role == 'executive'): ?>
                <div class="tnb-mobile-nav-section" data-section="executive"
                     onClick="window.location.href='../dashboard/executive.php'">
                    <div class="sis-button">
                        <span class="tnb-title">Executive Dashboard</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="tnb-mobile-nav-section" data-section="dashboard"
                     onClick="alert('You do not have permissions to use the Dashboard')">
                    <div class="sis-button">
                        <span class="tnb-title">Dashboard</span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="tnb-mobile-nav-section" data-section="fairmap" onClick="window.location.href='../assets/site_images/fair_map.png'">
                <div class="sis-button">
                    <span class="tnb-title">Club Fair Map</span>
                </div>
            </div>
        </div>
        <div class="sis-button tnb-close-btn">
            <span>×</span>
        </div>
    </nav>
</div>
<div class="topnavbackground"></div>
<div class="topnavcontainer">
    <div class="subtopnav">
        <div class="scroll-left-btn"></div>
        <div class="scroll-right-btn"></div>
        <?php
        $sql = "SELECT Announcement FROM announcements";
        $result = $conn->query($sql);
        $announcements = [];

        while ($row = $result->fetch_assoc()) {
            if (trim($row['Announcement']) !== '') {
                $announcements[] = $row['Announcement'];
            }
        }

        $totalLength = strlen(implode('', $announcements));
        $repeatCount = max(2, ceil(200 / max($totalLength, 1)));

        echo "<div class='announcement-track'>";

        for ($i = 0; $i < $repeatCount * 2; $i++) {
            foreach ($announcements as $announcement) {
                echo "<a>" . e($announcement) . "</a>";
            }
        }

        echo "</div>";
        ?>
    </div>
</div>
<div class="background-image"></div>
<div class="contentcontainer">

</div>
<div class ="wrappercontainer">
    <div class="footerwrapper">
        <div class="spacefooter">
            <div class="footerlinks" style="overflow-x:auto;">
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
                    <?php
                    $sqlContact = "SELECT Executives FROM clubs WHERE DirName='coding_club'";
                    $resultContact = $conn->query($sqlContact);
                    $ExecutivesContacts = $resultContact->fetch_assoc()['Executives'];
                    $ExecutivesContactList = array_map('trim', explode(',', $ExecutivesContacts));
                    $PresidentContact = $ExecutivesContactList[0];
                    echo
                    "<a href='mailto:$PresidentContact' title='Contact Us!'>CONTACT US</a>";
                    $conn->close();
                    ?>
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
<script>
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileNav = document.querySelector('.tnb-mobile-nav');
    const closeNav = document.querySelector('.tnb-close-btn');

    mobileMenu.addEventListener('click', () => {
        const state = mobileMenu.getAttribute('data-state');
        if (state === 'closed') {
            mobileMenu.innerHTML = 'Menu ▴';
            mobileMenu.setAttribute('data-state', 'open');
            mobileNav.style.display = 'block';
        } else if (state === 'open') {
            mobileMenu.innerHTML = 'Menu ▾';
            mobileMenu.setAttribute('data-state', 'closed');
            mobileNav.style.display = 'none';
        }
    })

    closeNav.addEventListener('click', () => {
        mobileMenu.innerHTML = 'Menu ▾';
        mobileMenu.setAttribute('data-state', 'closed');
        mobileNav.style.display = 'none';
    })
</script>