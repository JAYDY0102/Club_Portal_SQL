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
$role = null;
$admin = null;
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
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiger Clubs Portal - Feed</title>
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
            <a id="active" href="../feed" class="sis-bar-item  sis-padding-16 sis-button">Feed</a>
            <a id="inactive" href="../calendar" class="sis-bar-item sis-padding-16 sis-button">Calendar</a>
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
                 title="Sign out of your account" onClick="window.location.href='../auth/signout.php'">
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
                echo "<a>" . $announcement . "</a>";
            }
        }
        echo "</div>";
        ?>
    </div>
</div>
<div class="background-image"></div>
<div class="contentcontainer">
    <div class="belowtopnavcontainer">
        <main class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Club Activity Feed</h2>
                    <p>
                        Stay updated with the latest from all SIS clubs.
                    </p>
                </div>
                <section class="panel-section feed-panel">
                    <div class="feed-toolbar">
                        <nav class="feed-tabs" aria-label="Feed sections">
                            <a class="feed-tab active" id="all-posts">
                                All Posts
                            </a>
                            <a class="feed-tab" id="for-you">
                                For You
                            </a>
                        </nav>
                        <?php if ($admin == '1' || $role == 'executive' || $role == 'advisor'): ?>
                            <a class="form-btn feed-action-btn" href="add.php">
                                Add Post
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="feed-post-list">
                    </div>
                </section>
            </div>
        </main>
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
            <div class="footertext">
                Tigerclubs.org is made to promote connectivity across all clubs of SIS. It prioritizes accessibility over functionality.
                <br> Select members of Coding Club are constantly working to improve the website, but we cannot warrant that it will be free of bugs.
                <br> Please use the links below to submit any main banner request, club-specific website interest form, or bug reports if you happen to notice any.
                <br> <br> <a href="https://github.com/JAYDY0102/Club_Portal_SQL/blob/master/LICENSE">MIT License</a> of the website's source code.
            </div>
        </div>
    </div>
</div>
</body>
</html>
<script>
    const feedPostList = document.querySelector('.feed-post-list');
    const allPostsBtn = document.getElementById('all-posts');
    const forYouBtn = document.getElementById('for-you');
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

    allPostsBtn.addEventListener('click', () => {
        feedPostList.innerHTML = '';
        allPostsBtn.classList.add('active');
        forYouBtn.classList.remove('active');
        renderPosts('all-posts')
    })
    forYouBtn.addEventListener('click', () => {
        feedPostList.innerHTML = '';
        allPostsBtn.classList.remove('active');
        forYouBtn.classList.add('active');
        renderPosts('for-you')
    })

    async function renderPosts(type){
        const email = "<?php echo $email; ?>";
        const formData = new FormData();
        formData.append('RequestType', 'post-fetch')
        formData.append("Requester", email);
        formData.append("Type", type);
        const response = await fetch('../post.php', {
            method: 'POST',
            body: formData
        });
        const posts = await response.json();
        let int = 0;
        for (const post of posts) {
            console.log(int)
            int++;
            const postCard = document.createElement('article');
            postCard.classList.add('feed-post-card');
            if (post.canManage) {
                postCard.innerHTML = `
                <header class="feed-post-header">
                    <div class="feed-post-club">
                        <div class="feed-post-identity">
                            <h2>${post.clubName}</h2>
                            <time datetime="${post.dateValue}">${post.date}</time>
                        </div>
                    </div>
                    <a class="form-btn feed-modify-btn" href="modify.php?postID=${post.postID}&dirName=${post.dirName}">
                        Modify Post
                    </a>
                </header>
                <div class="feed-image-container" data-postId=${post.postID}>
                </div>
                <div class="feed-post-body">
                    <h3>${post.postTitle}</h3>
                    <p id="feed-description-" class="feed-post-description">${post.postDescription}</p>
                </div>
                `
            } else {
                postCard.innerHTML = `
                <header class="feed-post-header">
                    <div class="feed-post-club">
                        <div class="feed-post-identity">
                            <h2>${post.clubName}</h2>
                            <time datetime="${post.dateValue}">${post.date}</time>
                        </div>
                    </div>
                </header>
                <div class="feed-image-container" data-postId=${post.postID}>
                </div>
                <div class="feed-post-body">
                    <h3>${post.postTitle}</h3>
                    <p id="feed-description-" class="feed-post-description">${post.postDescription}</p>
                </div>
                `
            }
            const imageContainer = postCard.querySelector('.feed-image-container');
            if (post.postImage != null) {
                const images = post.postImage.split(',')
                for (const image of images) {
                    imageContainer.innerHTML += `<img src="../${image}" class="feed-image" alt="${post.postTitle}" loading="lazy">`
                }
                if (images.length > 1) {
                    imageContainer.innerHTML += `<a class="prev" onclick="plusSlides(-1, ${post.postID})">&#10094;</a>`
                    imageContainer.innerHTML += `<a class="next" onclick="plusSlides(1, ${post.postID})">&#10095;</a>`
                    imageContainer.dataset.slide = '1';
                }
            } else {
                imageContainer.classList.remove('feed-image-container');
            }
            feedPostList.appendChild(postCard);
            showSlides(1, post.postID);
        }
    }
    renderPosts('all-posts')
    function plusSlides(n, postId) {const imageContainer = document.querySelector(`.feed-image-container[data-postId="${postId}"]`);
        if (!imageContainer) {
            return;
        }

        const slideIndex = parseInt(imageContainer.dataset.slide || '1', 10);
        showSlides(slideIndex + n, postId);
    }
    function showSlides(n, postId) {
        const imageContainer = document.querySelector(`.feed-image-container[data-postId="${postId}"]`);
        if (!imageContainer) {
            return;
        }
        const slides = imageContainer.getElementsByClassName("feed-image");
        if (slides.length === 0) {
            return;
        }
        if (n > slides.length) {
            n = 1;
        }
        if (n < 1) {
            n = slides.length;
        }
        for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slides[n - 1].style.display = "block";
        imageContainer.dataset.slide = String(n);
    }
</script>
