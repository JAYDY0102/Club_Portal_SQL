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

function e($value): string
{
    return htmlspecialchars(($value ?? ''), ENT_QUOTES, 'UTF-8');
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
                        $sql = "SELECT * FROM clubs ORDER BY Name ASC";
                        $result = $conn->query($sql);
                        if (!$result) {
                            die("Query failed: " . $conn->error);
                        }

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $clubTypeTags = '';
                                $clubTypes = array_map('trim', explode(',', $row["ClubType"]));

                                $Advisors = array_map('trim', explode(',', $row["Advisors"]));
                                $Executives = array_map('trim', explode(',', $row["Executives"]));

                                $AdvisorsList = '';
                                $ExecutivesList = '';

                                foreach ($clubTypes as $type) {
                                    if ($type !== '') {
                                        $clubTypeTags .= "<span class='card-tag'>" . htmlspecialchars($type) . "</span>";
                                    }
                                }

                                $cycleAdvisor = count($Advisors);
                                foreach ($Advisors as $advisor) {
                                    $cycleAdvisor--;
                                    if ($advisor !== '') {
                                        $sqlAdvisor = "SELECT Name FROM users WHERE Email = '$advisor'";
                                        $resultAdvisor = $conn->query($sqlAdvisor);
                                        if ($cycleAdvisor === 0) {
                                            $AdvisorsList .= $resultAdvisor->fetch_assoc()['Name'];
                                        } else {
                                            $AdvisorsList .= $resultAdvisor->fetch_assoc()['Name'] . ', ';
                                        }
                                    }
                                }

                                $cycleExecutive = count($Executives);
                                foreach ($Executives as $executive) {
                                    $cycleExecutive--;
                                    if ($executive !== '') {
                                        $sqlExecutive = "SELECT Name FROM users WHERE Email = '$executive'";
                                        $resultExecutive = $conn->query($sqlExecutive);
                                        if ($cycleExecutive === 0) {
                                            $ExecutivesList .= $resultExecutive->fetch_assoc()['Name'];
                                        } else {
                                            $ExecutivesList .= $resultExecutive->fetch_assoc()['Name'] . ', ';
                                        }
                                    }
                                }
                                echo "
                                <article class='card' 
                                data-dir-name='" . e($row["DirName"]) . "' 
                                data-name='" . e($row["Name"]) . "'
                                data-club-type='" . e($row["ClubType"]) . "'
                                data-member-count='" . e($row["MemberCount"]) . "'
                                data-meet-day='" . e($row["MeetDay"]) . "'
                                data-location='" . e($row["Location"]) . "'
                                data-about='" . e($row["about"]) . "'
                                data-instagram='" . e($row["Instagram"]) . "'
                                data-youtube='" . e($row["Youtube"]) . "'
                                data-website='" . e($row["Website"]) . "'
                                data-social='" . e($row["Social"]) . "'
                                data-signed='" . ($SignedIn ? "true" : "false") . "'
                                data-advisors='" . e($AdvisorsList) . "'
                                data-executive='" . e($ExecutivesList) . "'
                                >
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
                    <aside class="drawer">
                        <div class="drawer-banner"></div>
                        <button class="drawer-close" id="closeDrawer">×</button>
                        <div class="drawer-content"></div>
                    </aside>
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
<script>
    const layout = document.querySelector(".layout");
    const drawer = document.querySelector('.drawer');
    const clubCards = document.querySelectorAll('.card');

    function makeLink(url, text) {
        return `<a href="${url}" target="_blank">${text}</a>`;
    }

    drawer.addEventListener('click', (event) => {
        if (event.target.closest('#closeDrawer')) {
            layout.classList.remove('drawer-open');
        }
    });

    clubCards.forEach(card => {
        card.addEventListener('click', () => {
            const clubDirName = card.getAttribute('data-dir-name');
            const clubName = card.getAttribute('data-name');
            const clubType = card.getAttribute('data-club-type');
            const memberCount = card.getAttribute('data-member-count');
            const meetDay = card.getAttribute('data-meet-day');
            const location = card.getAttribute('data-location');
            const about = card.getAttribute('data-about');
            const instagram = card.getAttribute('data-instagram');
            const youtube = card.getAttribute('data-youtube');
            const website = card.getAttribute('data-website');
            const social = card.getAttribute('data-social');
            const advisors = card.getAttribute('data-advisors');
            const executive = card.getAttribute('data-executive');
            const signed = card.getAttribute('data-signed');

            const typeTags = clubType
                .split(',')
                .map(type => `<span class="card-tag">${type}</span>`)
                .join('');

            const links = []
            if (instagram) links.push(makeLink(`https://www.instagram.com/${instagram}`, 'Instagram'));
            if (youtube) links.push(makeLink(youtube, 'YouTube'));
            if (website) links.push(makeLink(website, 'Website'));
            if (social) links.push(makeLink(social, 'Social Media'));

            if (links.length === 0){
                if (signed==='true'){
                    drawer.innerHTML = `
                <div class="drawer-banner">
                    <img class="drawer-image" src="assets/banners/${clubDirName}.png" alt="${clubName}">
                    <div class="drawer-tags">
                        ${typeTags}
                    </div>
                </div>
                <button class="drawer-close" id="closeDrawer">×</button>
                <div class="drawer-content">
                    <h2>${clubName}</h2>
                    <p><strong>Meet Day:</strong> ${meetDay}</p>
                    <p><strong>Location:</strong> ${location}</p>
                    <p><strong>Members:</strong> ${memberCount}</p>
                    <h3>About</h3>
                    <p>${about}</p>
                    <h3>Contact</h3>
                    <p><strong>Advisors:</strong> ${advisors}</p>
                    <p><strong>Executive:</strong> ${executive}</p>
                </div>
                `
                } else {
                    drawer.innerHTML = `
                <div class="drawer-banner">
                    <img class="drawer-image" src="assets/banners/${clubDirName}.png" alt="${clubName}">
                    <div class="drawer-tags">
                        ${typeTags}
                    </div>
                </div>
                <button class="drawer-close" id="closeDrawer">×</button>
                <div class="drawer-content">
                    <h2>${clubName}</h2>
                    <p><strong>Meet Day:</strong> ${meetDay}</p>
                    <p><strong>Location:</strong> ${location}</p>
                    <p><strong>Members:</strong> ${memberCount}</p>
                    <h3>About</h3>
                    <p>${about}</p>
                </div>
                `
                }
            } else {
                if (signed==='true'){
                    drawer.innerHTML = `
                <div class="drawer-banner">
                    <img class="drawer-image" src="assets/banners/${clubDirName}.png" alt="${clubName}">
                    <div class="drawer-tags">
                        ${typeTags}
                    </div>
                </div>
                <button class="drawer-close" id="closeDrawer">×</button>
                <div class="drawer-content">
                    <h2>${clubName}</h2>
                    <p><strong>Meet Day:</strong> ${meetDay}</p>
                    <p><strong>Location:</strong> ${location}</p>
                    <p><strong>Members:</strong> ${memberCount}</p>
                    <h3>About</h3>
                    <p>${about}</p>
                    <h3>Contact</h3>
                    <p><strong>Advisors:</strong> ${advisors}</p>
                    <p><strong>Executive:</strong> ${executive}</p>
                    ${links ? `<h3>Links</h3><p>${links}</p>` : ''}
                </div>
                `
                } else {
                    drawer.innerHTML = `
                <div class="drawer-banner">
                    <img class="drawer-image" src="assets/banners/${clubDirName}.png" alt="${clubName}">
                    <div class="drawer-tags">
                        ${typeTags}
                    </div>
                </div>
                <button class="drawer-close" id="closeDrawer">×</button>
                <div class="drawer-content">
                    <h2>${clubName}</h2>
                    <p><strong>Meet Day:</strong> ${meetDay}</p>
                    <p><strong>Location:</strong> ${location}</p>
                    <p><strong>Members:</strong> ${memberCount}</p>
                    <h3>About</h3>
                    <p>${about}</p>
                    ${links ? `<h3>Links</h3><p>${links}</div>` : ''}
                </div>
                `
                }
            }
            layout.classList.add("drawer-open");
        })
    })

</script>