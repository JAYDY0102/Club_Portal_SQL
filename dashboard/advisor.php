<?php
session_start();

$secret = require __DIR__ . '/../auth/secret.php';
$SignedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;

$host = $secret['host'];
$username = $secret['username'];
$password = $secret['password'];
$dbname = $secret['dbname'];

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

function e($value): string
{
    return htmlspecialchars(($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($SignedIn) {
    $email = $user['Email'];
    $stmt = $conn->prepare("SELECT Role FROM users WHERE Email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $role = $row['Role'];
    echo "<script>console.log('User role: $role');</script>";
    $stmt = $conn->prepare("SELECT Executives,Advisors FROM clubs");
    $stmt->execute();
    $result = $stmt->get_result();
    $Executives = [];
    $Advisors = [];
    while ($row = $result->fetch_assoc()) {
        $executivesList = array_map('trim', explode(',', $row['Executives']));
        $advisorsList = array_map('trim', explode(',', $row['Advisors']));
        foreach ($executivesList as $executive) {
            $Executives[] .= $executive;
        }
        foreach ($advisorsList as $advisor) {
            $Advisors[] .= $advisor;
        }
    }
    if (($role == 'executive' && !in_array($email, $Executives)) || ($role == 'advisor' && !in_array($email, $Advisors))) {
        $role = 'user';
    } elseif (in_array($email, $Executives) && $role != 'executive') {
        $role = 'executive';
    } elseif (in_array($email, $Advisors) && $role != 'advisor') {
        $role = 'advisor';
    }
    echo "<script>console.log('User role: $role');</script>";
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE Email = ?");
    $stmt->bind_param("ss", $role, $email);
    $stmt->execute();
    if ($role != 'advisor') {
        header('Location: ../index.php');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal - Advisor Dashboard</title>
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
            <a id="inactive" href="../calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
            <a id="active" href="admin.php" class="sis-bar-item sis-padding-16 sis-button">Advisor Dashboard</a>
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
            <div class="tnb-mobile-nav-section" data-section="advisor"
                 onClick="window.location.href='../dashboard/advisor.php'">
                <div class="sis-button">
                    <span class="tnb-title">Advisor Dashboard</span>
                </div>
            </div>
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
    <div class="belowtopnavcontainer">
        <div class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Advisor Dashboard</h2>
                    <p>Manage clubs and executives</p>
                </div>
                <div class="club-panel">
                    <div class="panel-section">
                        <h2>Select Club</h2>
                        <label for="club-search" style="display: none">Search Clubs...</label>
                        <input id="club-search" type="text" class="club-search" placeholder="Search Clubs...">
                        <div class="club-list">
                            <label for="club-options" style="display: none">Clubs</label>
                            <select id="club-options" class="club-options see-thru" size="10">
                                <?php
                                $stmt = $conn->prepare(
                                    "SELECT ClubID, DirName, Name
                                    FROM clubs
                                    WHERE FIND_IN_SET(
                                        ?,
                                        REPLACE(Advisors, ' ', '')
                                    ) > 0
                                    ORDER BY Name ASC"
                                );

                                $stmt->bind_param('s', $email);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                ?>

                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <option
                                        value="<?= e($row['DirName']) ?>"
                                        data-club-id="<?= (int) $row['ClubID'] ?>"
                                    >
                                        <?= e($row['Name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <h2>Banner Preview</h2>
                        <div class="banner-section see-thru">
                            <div>No Image...</div>
                        </div>
                        <h2>Upload Banner</h2>
                        <div class="banner-upload see-thru" style="margin-bottom: 57px">
                            <label for="banner-input" class="upload-label">
                                Upload banners in only png format.
                                <input id="banner-input" type="file" accept="image/png" style="display: none">
                            </label>
                        </div>
                        <h2 style="display: none" id="club-dir-title">Directory Name</h2>
                        <div class="form-group see-thru" id="club-dir-section" style="display: none; margin-bottom: 0">
                            <div class="form-grid">
                                <label for="club-dir-name">Directory Name – CAUTION</label>
                                <input id="club-dir-name" type="text" class="form-input" placeholder="Directory Name">
                            </div>
                        </div>
                    </div>
                    <div class="panel-section">
                        <h2>Modify Club</h2>
                        <div class="form-group see-thru">
                            <div class="form-group-title">Generic Information</div>
                            <div class="form-grid">
                                <label for="club-name">Club Name</label>
                                <input id="club-name" type="text" class="form-input" placeholder="Club Name">
                                <label for="club-type">Club Type</label>
                                <select id="club-type" class="form-input">
                                    <option value="Academic">Academic</option>
                                    <option value="Arts & Culture">Arts & Culture</option>
                                    <option value="Community Service">Community Service</option>
                                    <option value="Journalism">Journalism</option>
                                    <option value="Sports">Sports</option>
                                    <option value="STEM">STEM</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group see-thru">
                            <div class="form-group-title">Club Descriptions</div>
                            <div class="form-grid">
                                <label for="club-summary">Club Summary – Main page card</label>
                                <textarea id="club-summary" class="form-input" placeholder="Club Summary"></textarea>
                                <label for="club-about">Club Description - Detailed description</label>
                                <textarea id="club-about" class="form-input" placeholder="Club Description"></textarea>
                            </div>
                        </div>
                        <div class="form-group see-thru">
                            <div class="form-group-title">Additional Information</div>
                            <div class="form-grid">
                                <label for="club-day">Meeting Day</label>
                                <select id="club-day" class="form-input">
                                    <option value="Monday">Monday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday A">Thursday A</option>
                                    <option value="Thursday B">Thursday B</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Other">Other</option>
                                </select>
                                <label for="club-location">Meeting Location</label>
                                <input id="club-location" type="text" class="form-input" placeholder="Meeting Location">
                                <label for="club-members">Member Count</label>
                                <input id="club-members" type="number" class="form-input" value="0" min="0">
                            </div>
                        </div>
                        <div class="form-group see-thru">
                            <div class="form-group-title">Contact Information</div>
                            <div class="form-grid">
                                <label for="club-advisors">Advisor Email(s) – Separate by comma w/o spaces</label>
                                <input id="club-advisors" type="text" class="form-input">
                                <label for="club-executives">Executive Emails – Separate by comma w/o spaces</label>
                                <input id="club-executives" type="text" class="form-input">
                                <label for="club-instagram">Instagram Handle – Exclude the @ symbol</label>
                                <input id="club-instagram" type="text" class="form-input" placeholder="sis_tigers">
                                <label for="club-youtube">YouTube – Include the full URL</label>
                                <input id="club-youtube" type="text" class="form-input" placeholder="https://www.youtube.com/playlist?list=PLY4AlYc_waYI">
                                <label for="club-website">Website – Include the full URL</label>
                                <input id="club-website" type="text" class="form-input" placeholder="https://tigerclubs.org">
                                <label for="club-social">Extra Socials – Include the full URL</label>
                                <input id="club-social" type="text" class="form-input" placeholder="https://github.com/JAYDY0102/Club_Portal_SQL">
                            </div>
                        </div>
                        <div class="form-btn-group">
                            <div class="form-btn" id="save-btn">Save Changes</div>
                            <div class="form-btn" id="delete-btn">Delete Club</div>
                        </div>
                    </div>
                </div>
                <section class="panel-section feed-compose-panel">
                    <div class="section-head">
                        <h2>Create Feed Post</h2>
                        <p>Select one of your clubs above, then publish a post.</p>
                    </div>

                    <form
                        id="feed-post-form"
                        class="form-group see-thru"
                        enctype="multipart/form-data"
                    >
                        <input
                            id="feed-club-id"
                            name="ClubID"
                            type="hidden"
                            value=""
                        >

                        <div class="form-grid">
                            <label for="feed-post-title">Post Title</label>
                            <input
                                id="feed-post-title"
                                name="Title"
                                type="text"
                                class="form-input"
                                maxlength="255"
                                placeholder="Club Post Title"
                                required
                            >

                            <label for="feed-post-description">
                                Post Description
                            </label>
                            <textarea
                                id="feed-post-description"
                                name="Description"
                                class="form-input"
                                maxlength="4095"
                                placeholder="Write Post Description..."
                                required
                            ></textarea>

                            <label for="feed-post-image">
                                Optional PNG Image
                            </label>
                            <input
                                id="feed-post-image"
                                name="Image"
                                type="file"
                                class="form-input"
                                accept="image/png"
                            >
                        </div>

                        <p
                            id="feed-post-status"
                            role="status"
                            aria-live="polite"
                        ></p>

                        <div class="form-btn-group">
                            <button
                                id="publish-post-btn"
                                class="form-btn"
                                type="submit"
                            >
                                Publish Post
                            </button>
                        </div>
                    </form>
                </section>
                <section class="panel-section feed-manage-panel">
                    <div class="section-head">
                        <h2>Manage Feed Posts</h2>
                        <p>
                            View and archive active posts from your clubs.
                        </p>
                    </div>

                    <p
                        id="feed-manage-status"
                        role="status"
                        aria-live="polite"
                    ></p>

                    <div id="feed-manage-list" class="feed-manage-list">
                        <?php
                        $postListStatement = $conn->prepare(
                            "SELECT
                                feed.PostID,
                                feed.Title,
                                feed.UploadTime,
                                clubs.Name AS ClubName
                             FROM feed
                             INNER JOIN clubs
                                ON clubs.ClubID = feed.ClubID
                             WHERE feed.Visible = 1
                               AND FIND_IN_SET(
                                    ?,
                                    REPLACE(clubs.Advisors, ' ', '')
                               ) > 0
                             ORDER BY
                                feed.UploadTime DESC,
                                feed.PostID DESC"
                        );

                        $postListStatement->bind_param('s', $email);
                        $postListStatement->execute();

                        $postListResult =
                            $postListStatement->get_result();
                        ?>

                        <?php if ($postListResult->num_rows === 0): ?>
                            <div
                                class="feed-manage-empty"
                                data-feed-empty
                            >
                                You do not have any active posts.
                            </div>
                        <?php else: ?>
                            <?php while (
                                $managedPost =
                                    $postListResult->fetch_assoc()
                            ): ?>
                                <?php
                                $managedPostID =
                                    (int) $managedPost['PostID'];

                                $managedTimestamp = strtotime(
                                    (string) $managedPost['UploadTime']
                                );

                                $managedDate =
                                    $managedTimestamp !== false
                                        ? date(
                                            'M j, Y',
                                            $managedTimestamp
                                        )
                                        : 'Unknown date';
                                ?>

                                <article
                                    class="feed-manage-item"
                                    data-post-id="<?= $managedPostID ?>"
                                >
                                    <div class="feed-manage-details">
                                        <h3>
                                            <?= e(
                                                $managedPost['Title']
                                            ) ?>
                                        </h3>

                                        <p>
                                            <?= e(
                                                $managedPost['ClubName']
                                            ) ?>
                                            <span aria-hidden="true">•</span>
                                            <?= e($managedDate) ?>
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="archive-post-btn"
                                        data-post-id="<?= $managedPostID ?>"
                                        data-post-title="<?= e(
                                            $managedPost['Title']
                                        ) ?>"
                                    >
                                        Archive
                                    </button>
                                </article>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php $postListStatement->close(); ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
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
        </div>
    </div>
</div>
</body>
</html>
<script>
    const clubOptions = document.getElementById('club-options');
    const bannerSection = document.querySelector('.banner-section');
    const bannerInput = document.getElementById('banner-input');
    const bannerUpload = document.querySelector('.banner-upload');
    const nameInput = document.getElementById('club-name');
    const typeInput = document.getElementById('club-type');
    const summaryInput = document.getElementById('club-summary');
    const aboutInput = document.getElementById('club-about');
    const dayInput = document.getElementById('club-day');
    const locationInput = document.getElementById('club-location');
    const membersInput = document.getElementById('club-members');
    const advisorsInput = document.getElementById('club-advisors');
    const executivesInput = document.getElementById('club-executives');
    const instagramInput = document.getElementById('club-instagram');
    const youtubeInput = document.getElementById('club-youtube');
    const websiteInput = document.getElementById('club-website');
    const socialInput = document.getElementById('club-social');
    const clubDirTitle = document.getElementById('club-dir-title');
    const clubDirSection = document.getElementById('club-dir-section');
    const clubDirName = document.getElementById('club-dir-name');
    const saveBtn = document.getElementById('save-btn');
    const deleteBtn = document.getElementById('delete-btn');
    const clubSearch = document.getElementById('club-search');
    let tmpBanner = '';
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileNav = document.querySelector('.tnb-mobile-nav');
    const closeNav = document.querySelector('.tnb-close-btn');

    const feedPostForm = document.getElementById('feed-post-form');
    const feedClubID = document.getElementById('feed-club-id');
    const feedPostStatus = document.getElementById('feed-post-status');
    const publishPostBtn = document.getElementById('publish-post-btn');
    const feedManageList = document.getElementById('feed-manage-list');
    const feedManageStatus = document.getElementById('feed-manage-status');

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

    clubOptions.addEventListener('change', async () => {
        const DirName = clubOptions.value;
        updateBannerPreview(DirName, '')
        await fetchClubInformation(DirName)
    })

    saveBtn.addEventListener('click', async () => {
        let DirName = clubOptions.value;
        console.log(DirName)
        try {
            await updateClubInformation(DirName)
        } catch (error) {
            console.error('Error in updateClubInformation:', error);
        }
    })

    deleteBtn.addEventListener('click', async () => {
        let confirmDelete = confirm("Are you sure you want to delete this club?");
        if (confirmDelete) {
            let DirName = clubOptions.value;
            const formData = new FormData();
            formData.append('RequestType', 'club-delete')
            formData.append('DirName', DirName);
            try {
                const response = await fetch('../post.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();
                const status = result.split(';');
                if (status[0] === 'rei') {
                    console.log(status[0], status[1])
                    window.location.reload()
                } else if (status[0] === 'shinji-01') {
                    console.error('kaworu', status[1]);
                }
            } catch (error) {
                console.error('Error deleting club:', error);
            }
        }
    })

    bannerInput.addEventListener('change', async () => {
        const DirName = clubOptions.value;
        const file = bannerInput.files[0];

        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('RequestType', 'Banner')
        formData.append('File', file);
        formData.append('DirName', DirName);

        try {
            const response = await fetch('../post.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.text();
            const status = result.split(';');
            if (status[0] === 'rei') {
                updateBannerPreview(DirName, status[1]);
                console.log(status[0],status[1])
            } else if (status[0] === 'asuka') {
                updateBannerPreview(status[1], '');
                tmpBanner = status[1];
                console.log(status[0],tmpBanner)
            } else if (status[0] === 'shinji-01') {
                console.error('kaworu',status[1]);
            } else if (status[0] === 'shinji-13') {
                console.error('mari',status[1]);
            } else if (status[0] === 'asuka-a'){
                console.error('asuka-a','fail');
            }
        } catch (error) {
            console.error('Error uploading banner:', error);
        }
    })

    clubSearch.addEventListener('input', () => {
        const searchTerm = clubSearch.value.toLowerCase();
        const clubOptions = document.getElementById('club-options');

        Array.from(clubOptions.options).forEach(club => {
            const clubName = club.textContent.toLowerCase();
            if (clubName.includes(searchTerm)) {
                club.style.display = 'block';
            } else {
                club.style.display = 'none';
            }
        })
    })

    function updateBannerPreview(DirName, version) {
        if (version !== '') {
            const versionParam = `?v=${version}`;
            bannerSection.innerHTML = `<img src="../assets/banners/${DirName}.png${versionParam}" alt="Banner Preview">`;
        } else {
            bannerSection.innerHTML = `<img src="../assets/banners/${DirName}.png" alt="Banner Preview">`;
        }
    }

    async function fetchClubInformation(DirName) {
        const formData = new FormData();
        formData.append('RequestType', 'club-fetch')
        formData.append('DirName', DirName);

        try {
            const response = await fetch('../post.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.text();
            const status = result.split(';');
            if (status[0] === 'rei') {
                console.log(status[0])
                nameInput.value = status[1];
                typeInput.value = status[2];
                summaryInput.value = status[3];
                aboutInput.value = status[4];
                dayInput.value = status[5];
                locationInput.value = status[6];
                membersInput.value = status[7];
                advisorsInput.value = status[8];
                executivesInput.value = status[9];
                instagramInput.value = status[10];
                youtubeInput.value = status[11];
                websiteInput.value = status[12];
                socialInput.value = status[13];
                clubDirTitle.style.display = "none";
                clubDirSection.style.display = "none";
                saveBtn.innerHTML = "Save Changes";
                deleteBtn.innerHTML = "Delete Club";
            } else if (status[0] === 'asuka'){
                console.log(status[0])
            } else if (status[0] === 'shinji-01') {
                console.error('kaworu','query failed');
            } else if (status[0] === 'shinji-13') {
                console.error('mari','query failed');
            } else {
                console.error('unknown error');
            }
        } catch (error) {
            console.error('Error updating club information:', error);
        }
    }
    async function updateClubInformation(DirName) {
        const formData = new FormData();
        formData.append('RequestType', 'club-update')
        formData.append('Name', nameInput.value);
        formData.append('Type', typeInput.value);
        formData.append('MemberCount', membersInput.value);
        formData.append('MeetDay', dayInput.value);
        formData.append('Location', locationInput.value);
        formData.append('Summary', summaryInput.value);
        formData.append('About', aboutInput.value);
        formData.append('Instagram', instagramInput.value);
        formData.append('Youtube', youtubeInput.value);
        formData.append('Website', websiteInput.value);
        formData.append('Social', socialInput.value);
        formData.append('Advisors', advisorsInput.value);
        formData.append('Executives', executivesInput.value);
        formData.append('DirName', DirName);
        try {
            const response = await fetch('../post.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.text();
            const status = result.split(';');
            if (status[0] === 'rei') {
                console.log(status[0])
                alert('Club information updated successfully');
                window.location.reload();
            } else if (status[0] === 'shinji-01') {
                console.log('kaworu',status[1])
            }
        } catch (error) {
            console.error('Error updating club information:', error);
        }
    }

    async function refreshFeedManagementList() {
        const response = await fetch(window.location.href, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('The management list could not be refreshed.');
        }

        const pageHTML = await response.text();

        const parsedPage = new DOMParser().parseFromString(
            pageHTML,
            'text/html'
        );

        const refreshedList = parsedPage.getElementById(
            'feed-manage-list'
        );

        if (!refreshedList) {
            throw new Error('The refreshed management list was not found.');
        }

        feedManageList.innerHTML = refreshedList.innerHTML;
    }

    feedPostForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const selectedClub = clubOptions.options[clubOptions.selectedIndex];

        if (!selectedClub) {
            feedPostStatus.textContent = 'Select a club before publishing.';
            return;
        }

        const selectedClubID = selectedClub.dataset.clubId;

        if (!selectedClubID) {
            feedPostStatus.textContent = 'The selected club is invalid.';
            return;
        }

        feedClubID.value = selectedClubID;
        feedPostStatus.textContent = 'Publishing...';
        publishPostBtn.disabled = true;

        const formData = new FormData(feedPostForm);

        try {
            const response = await fetch('../feed/create_post.php', {
                method: 'POST',
                body: formData
            });

            let result;

            try {
                result = await response.json();
            } catch {
                throw new Error('The server returned an invalid response.');
            }

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'The post could not be published.'
                );
            }

            feedPostForm.reset();

            try {
                await refreshFeedManagementList();

                feedPostStatus.textContent =
                    `Post published successfully for ${result.clubName}.`;
            } catch (refreshError) {
                console.error(refreshError);

                feedPostStatus.textContent =
                    `Post published successfully for ${result.clubName}, ` +
                    'but the management list could not refresh. ' +
                    'Refresh the page to see the new post.';
            }
        } catch (error) {
            feedPostStatus.textContent =
                error.message || 'The post could not be published.';
        } finally {
            publishPostBtn.disabled = false;
        }
    });

    feedManageList.addEventListener('click', async (event) => {
        const archiveButton = event.target.closest('.archive-post-btn');

        if (!archiveButton) {
            return;
        }

        const postID = archiveButton.dataset.postId;
        const postTitle = archiveButton.dataset.postTitle;

        const confirmed = confirm(
            `Archive "${postTitle}"?\n\n` +
            'The post will no longer appear in the feed.'
        );

        if (!confirmed) {
            return;
        }

        archiveButton.disabled = true;
        archiveButton.textContent = 'Archiving...';
        feedManageStatus.textContent = '';

        const formData = new FormData();
        formData.append('PostID', postID);

        try {
            const response = await fetch('../feed/archive_post.php', {
                method: 'POST',
                body: formData
            });

            let result;

            try {
                result = await response.json();
            } catch {
                throw new Error('The server returned an invalid response.');
            }

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'The post could not be archived.'
                );
            }

            const postItem = archiveButton.closest('.feed-manage-item');

            if (postItem) {
                postItem.remove();
            }

            feedManageStatus.textContent = result.message;

            const remainingPosts = feedManageList.querySelectorAll(
                '.feed-manage-item'
            );

            if (remainingPosts.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'feed-manage-empty';
                emptyState.dataset.feedEmpty = '';
                emptyState.textContent =
                    'You do not have any active posts.';

                feedManageList.appendChild(emptyState);
            }
        } catch (error) {
            feedManageStatus.textContent =
                error.message || 'The post could not be archived.';

            archiveButton.disabled = false;
            archiveButton.textContent = 'Archive';
        }
    });
</script>