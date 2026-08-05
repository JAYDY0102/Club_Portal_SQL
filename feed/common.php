<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function feedEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function feedAddressList(string $addresses): array
{
    return array_values(array_filter(array_map(
        'trim',
        explode(',', $addresses)
    )));
}

function feedUserCanManageClub(
    array $club,
    string $email,
    bool $isAdmin
): bool {
    if ($isAdmin) {
        return true;
    }

    return in_array(
        $email,
        feedAddressList((string) ($club['Executives'] ?? '')),
        true
    ) || in_array(
        $email,
        feedAddressList((string) ($club['Advisors'] ?? '')),
        true
    );
}

function feedAuthorizedClubs(
    mysqli $conn,
    string $email,
    bool $isAdmin
): array {
    $result = $conn->query(
        'SELECT ClubID, Name, DirName, Executives, Advisors
         FROM clubs
         ORDER BY Name ASC'
    );

    if (!$result) {
        return [];
    }

    $clubs = [];

    while ($club = $result->fetch_assoc()) {
        if (feedUserCanManageClub($club, $email, $isAdmin)) {
            $clubs[] = $club;
        }
    }

    return $clubs;
}

$secret = require __DIR__ . '/../auth/secret.php';
$sessionUser = $_SESSION['user'] ?? null;

if (!$sessionUser || empty($sessionUser['Email'])) {
    header('Location: ../index.php');
    exit;
}

$conn = new mysqli(
    $secret['host'],
    $secret['username'],
    $secret['password'],
    $secret['dbname']
);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

$conn->set_charset('utf8mb4');
$email = trim((string) $sessionUser['Email']);
$userStatement = $conn->prepare(
    'SELECT Role, AdminFlag FROM users WHERE Email = ?'
);

if (!$userStatement) {
    http_response_code(500);
    exit('User verification failed.');
}

$userStatement->bind_param('s', $email);
$userStatement->execute();
$databaseUser = $userStatement->get_result()->fetch_assoc();
$userStatement->close();

if (!$databaseUser) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$role = (string) ($databaseUser['Role'] ?? 'user');
$admin = (int) ($databaseUser['AdminFlag'] ?? 0);
$isAdmin = $admin === 1;

function feedRenderPageStart(
    mysqli $conn,
    string $title,
    string $role,
    bool $isAdmin
): void {
    $dashboardUrl = null;
    $dashboardLabel = 'Dashboard';

    if ($isAdmin) {
        $dashboardUrl = '../dashboard/admin.php';
        $dashboardLabel = 'Admin Dashboard';
    } elseif ($role === 'advisor') {
        $dashboardUrl = '../dashboard/advisor.php';
        $dashboardLabel = 'Advisor Dashboard';
    } elseif ($role === 'executive') {
        $dashboardUrl = '../dashboard/executive.php';
        $dashboardLabel = 'Executive Dashboard';
    }

    $announcementResult = $conn->query(
        'SELECT Announcement FROM announcements'
    );
    $announcements = [];

    if ($announcementResult) {
        while ($announcement = $announcementResult->fetch_assoc()) {
            $text = trim((string) ($announcement['Announcement'] ?? ''));

            if ($text !== '') {
                $announcements[] = $text;
            }
        }
    }

    $totalLength = strlen(implode('', $announcements));
    $repeatCount = max(2, (int) ceil(200 / max($totalLength, 1)));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= feedEscape($title) ?> - Tiger Clubs Portal</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<div id="top-nav-bar" class="classic">
    <div id="pagetop" class="sis-bar notranslate primary-white">
        <a id="sis-logo" href="../index.php" class="sis-bar-item sis-button sis-left" title="Home">
            <i class="fa" aria-hidden="true">1</i>
        </a>
        <nav class="tnb-desktop-nav sis-bar-item">
            <a id="inactive" href="../index.php" class="sis-bar-item sis-padding-16 sis-button">Home</a>
            <a id="active" href="./?view=all" class="sis-bar-item sis-padding-16 sis-button">Feed</a>
            <a id="inactive" href="../calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
            <?php if ($dashboardUrl !== null): ?>
                <a id="inactive" href="<?= feedEscape($dashboardUrl) ?>" class="sis-bar-item sis-padding-16 sis-button">
                    <?= feedEscape($dashboardLabel) ?>
                </a>
            <?php else: ?>
                <a id="inactive" href="#" class="sis-bar-item sis-padding-16 sis-button" data-dashboard-denied>
                    Dashboard
                </a>
            <?php endif; ?>
        </nav>
        <a id="inactive" class="sis-bar-item sis-button sis-padding-16 mobile-menu" data-state="closed">
            Menu ▾
        </a>
        <div class="spacer sis-bar-item"><div class="space-inner"></div></div>
        <div class="tnb-right-section">
            <a id="tnb-sign-btn" class="tnb-sign-btn sis-bar-item sis-right sis-button" href="../auth/signout.php" title="Sign out of your account">
                <span class="button-text">Sign Out</span>
            </a>
            <a href="../assets/site_images/fair_map.png" class="tnb-right-side-btn sis-bar-item sis-button sis-right" title="Club Fair Map">
                Fair Map
            </a>
        </div>
    </div>
    <nav id="tnb-mobile-nav" class="tnb-mobile-nav">
        <div class="mobile-container">
            <div class="tnb-mobile-nav-section" data-link="../index.php"><div class="sis-button"><span class="tnb-title">Home</span></div></div>
            <div class="tnb-mobile-nav-section" data-link="./?view=all"><div class="sis-button"><span class="tnb-title">Feed</span></div></div>
            <div class="tnb-mobile-nav-section" data-link="../calendar"><div class="sis-button"><span class="tnb-title">Calendar</span></div></div>
            <?php if ($dashboardUrl !== null): ?>
                <div class="tnb-mobile-nav-section" data-link="<?= feedEscape($dashboardUrl) ?>"><div class="sis-button"><span class="tnb-title"><?= feedEscape($dashboardLabel) ?></span></div></div>
            <?php else: ?>
                <div class="tnb-mobile-nav-section" data-dashboard-denied><div class="sis-button"><span class="tnb-title">Dashboard</span></div></div>
            <?php endif; ?>
            <div class="tnb-mobile-nav-section" data-link="../assets/site_images/fair_map.png"><div class="sis-button"><span class="tnb-title">Club Fair Map</span></div></div>
        </div>
        <div class="sis-button tnb-close-btn"><span>×</span></div>
    </nav>
</div>
<div class="topnavbackground"></div>
<div class="topnavcontainer">
    <div class="subtopnav">
        <div class="scroll-left-btn"></div>
        <div class="scroll-right-btn"></div>
        <div class="announcement-track">
            <?php for ($i = 0; $i < $repeatCount * 2; $i++): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <a><?= feedEscape($announcement) ?></a>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</div>
<div class="background-image"></div>
    <?php
}

function feedRenderPageEnd(mysqli $conn): void
{
    $presidentContact = '';
    $contactResult = $conn->query(
        "SELECT Executives FROM clubs WHERE DirName = 'coding_club'"
    );

    if ($contactResult && ($contactRow = $contactResult->fetch_assoc())) {
        $contacts = feedAddressList(
            (string) ($contactRow['Executives'] ?? '')
        );
        $presidentContact = $contacts[0] ?? '';
    }
    ?>
<div class="wrappercontainer">
    <div class="footerwrapper">
        <div class="spacefooter">
            <div class="footerlinks" style="overflow-x:auto;">
                <div class="footerlinks_1"><a href="https://tigerclubs.org/index.php" aria-label="Tigerclubs.org"><i class="fa fa-logo">1</i></a></div>
                <div class="footerlinks_1"><a href="https://forms.gle/mgUxnthy2izYn4yi8">BANNER REQUEST</a></div>
                <div class="footerlinks_1"><a href="https://forms.gle/QwJxodQaQRro4cqB7">INTEREST FORM</a></div>
                <div class="footerlinks_1"><a href="https://forms.gle/KFqJG2EHqEsWUuB47">BUG REPORT</a></div>
                <?php if ($presidentContact !== ''): ?>
                    <div class="footerlinks_1"><a href="mailto:<?= feedEscape($presidentContact) ?>">CONTACT US</a></div>
                <?php endif; ?>
            </div>
            <div class="footertext">
                Tigerclubs.org is made to promote connectivity across all clubs of SIS. It prioritizes accessibility over functionality.
                <br>Select members of Coding Club are constantly working to improve the website, but we cannot warrant that it will be free of bugs.
                <br>Please use the links below to submit any main banner request, club-specific website interest form, or bug reports if you happen to notice any.
                <br><br><a href="https://github.com/JAYDY0102/Club_Portal_SQL/blob/master/LICENSE">MIT License</a> of the website's source code.
            </div>
        </div>
    </div>
</div>
<script>
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileNav = document.querySelector('.tnb-mobile-nav');
    const closeNav = document.querySelector('.tnb-close-btn');

    if (mobileMenu && mobileNav && closeNav) {
        mobileMenu.addEventListener('click', () => {
            const isOpen = mobileMenu.getAttribute('data-state') === 'open';
            mobileMenu.textContent = isOpen ? 'Menu ▾' : 'Menu ▴';
            mobileMenu.setAttribute('data-state', isOpen ? 'closed' : 'open');
            mobileNav.style.display = isOpen ? 'none' : 'block';
        });

        closeNav.addEventListener('click', () => {
            mobileMenu.textContent = 'Menu ▾';
            mobileMenu.setAttribute('data-state', 'closed');
            mobileNav.style.display = 'none';
        });
    }

    document.querySelectorAll('[data-link]').forEach((element) => {
        element.addEventListener('click', () => {
            window.location.href = element.dataset.link;
        });
    });

    document.querySelectorAll('[data-dashboard-denied]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            alert('You do not have permissions to use the Dashboard');
        });
    });
</script>
</body>
</html>
    <?php
    $conn->close();
}
