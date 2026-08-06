<?php

require __DIR__ . '/common.php';

$feedView = $_GET['view'] ?? null;

if (!in_array($feedView, ['all', 'for-you'], true)) {
    $feedDirectory = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $feedDirectory . '/?view=all');
    exit;
}

$authorizedClubs = feedAuthorizedClubs($conn, $email, $isAdmin);
$canAddPost = count($authorizedClubs) > 0;
$membershipFilter = '';

if ($feedView === 'for-you') {
    $membershipFilter = "
        AND EXISTS (
            SELECT 1
            FROM users
            WHERE users.Email = ?
              AND FIND_IN_SET(
                    CAST(feed.ClubID AS CHAR),
                    REPLACE(COALESCE(users.MemberOf, ''), ' ', '')
              ) > 0
        )
    ";
}

$feedSql = "
    SELECT
        feed.PostID,
        feed.ClubID,
        feed.UploadTime,
        feed.Title,
        feed.Description,
        feed.ImageID,
        clubs.Name AS ClubName,
        clubs.DirName,
        clubs.Executives,
        clubs.Advisors
    FROM feed
    INNER JOIN clubs ON clubs.ClubID = feed.ClubID
    WHERE feed.Visible = 1
    $membershipFilter
    ORDER BY feed.UploadTime DESC, feed.PostID DESC
";

$feedStatement = $conn->prepare($feedSql);
$feedResult = false;

if ($feedStatement) {
    if ($feedView === 'for-you') {
        $feedStatement->bind_param('s', $email);
    }

    if ($feedStatement->execute()) {
        $feedResult = $feedStatement->get_result();
    }
}

feedRenderPageStart($conn, 'Club Activity Feed', $role, $isAdmin);
?>
<div class="contentcontainer">
    <div class="belowtopnavcontainer">
        <main class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Club Activity Feed</h2>
                    <p>
                        <?= $feedView === 'for-you'
                            ? 'Updates from clubs you are a member of.'
                            : 'Stay updated with the latest from all SIS clubs.' ?>
                    </p>
                </div>

                <section class="panel-section feed-panel">
                    <div class="feed-toolbar">
                        <nav class="feed-tabs" aria-label="Feed sections">
                            <a
                                class="feed-tab <?= $feedView === 'all' ? 'active' : '' ?>"
                                href="?view=all"
                                <?= $feedView === 'all' ? 'aria-current="page"' : '' ?>
                            >
                                All Posts
                            </a>
                            <a
                                class="feed-tab <?= $feedView === 'for-you' ? 'active' : '' ?>"
                                href="?view=for-you"
                                <?= $feedView === 'for-you' ? 'aria-current="page"' : '' ?>
                            >
                                For You
                            </a>
                        </nav>

                        <?php if ($canAddPost): ?>
                            <a class="form-btn feed-action-btn" href="add.php">
                                Add Post
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="feed-post-list">
                        <?php if (!$feedResult): ?>
                            <div class="feed-empty-state see-thru">
                                <h2>Unable to load posts</h2>
                                <p>Please refresh the page and try again.</p>
                            </div>
                        <?php elseif ($feedResult->num_rows === 0): ?>
                            <div class="feed-empty-state see-thru">
                                <h2>No posts yet</h2>
                                <p>Club announcements will appear here once they are published.</p>
                            </div>
                        <?php else: ?>
                            <?php while ($post = $feedResult->fetch_assoc()): ?>
                                <?php
                                $postID = (int) $post['PostID'];
                                $imageID = $post['ImageID'] !== null
                                    ? (int) $post['ImageID']
                                    : null;
                                $dirName = basename(
                                    (string) ($post['DirName'] ?? '')
                                );
                                $bannerFile = __DIR__
                                    . '/../assets/banners/'
                                    . $dirName
                                    . '.png';
                                $clubImageUrl = $dirName !== ''
                                    && is_file($bannerFile)
                                    ? '../assets/banners/'
                                        . rawurlencode($dirName)
                                        . '.png'
                                    : '../assets/site_images/SIS%20Logo.svg';
                                $postImageFile = $imageID !== null
                                    ? __DIR__
                                        . '/../assets/feed/'
                                        . $imageID
                                        . '.png'
                                    : null;
                                $hasPostImage = $postImageFile !== null
                                    && is_file($postImageFile);
                                $uploadTimestamp = strtotime(
                                    (string) $post['UploadTime']
                                );
                                $displayDate = $uploadTimestamp !== false
                                    ? date('M j, Y', $uploadTimestamp)
                                    : 'Unknown date';
                                $dateTimeValue = $uploadTimestamp !== false
                                    ? date(DATE_ATOM, $uploadTimestamp)
                                    : '';
                                $canManagePost = feedUserCanManageClub(
                                    $post,
                                    $email,
                                    $isAdmin
                                );
                                ?>
                                <article class="feed-post-card see-thru" data-post-id="<?= $postID ?>">
                                    <header class="feed-post-header">
                                        <div class="feed-post-club">
                                            <img
                                                class="feed-club-avatar"
                                                src="<?= feedEscape($clubImageUrl) ?>"
                                                alt="<?= feedEscape($post['ClubName']) ?> logo"
                                                loading="lazy"
                                            >
                                            <div class="feed-post-identity">
                                                <h2><?= feedEscape($post['ClubName']) ?></h2>
                                                <time datetime="<?= feedEscape($dateTimeValue) ?>">
                                                    <?= feedEscape($displayDate) ?>
                                                </time>
                                            </div>
                                        </div>

                                        <?php if ($canManagePost): ?>
                                            <a
                                                class="form-btn feed-modify-btn"
                                                href="modify.php?post_id=<?= $postID ?>"
                                            >
                                                Modify Post
                                            </a>
                                        <?php endif; ?>
                                    </header>

                                    <div class="feed-post-body">
                                        <h3><?= feedEscape($post['Title']) ?></h3>
                                        <p
                                            id="feed-description-<?= $postID ?>"
                                            class="feed-post-description"
                                        ><?= feedEscape($post['Description']) ?></p>
                                        <button
                                            class="feed-more-btn"
                                            type="button"
                                            aria-expanded="false"
                                            aria-controls="feed-description-<?= $postID ?>"
                                            hidden
                                        >
                                            See more
                                        </button>
                                    </div>

                                    <?php if ($hasPostImage): ?>
                                        <div class="feed-post-image-container">
                                            <img
                                                class="feed-post-image"
                                                src="../assets/feed/<?= $imageID ?>.png"
                                                alt="<?= feedEscape($post['Title']) ?>"
                                                loading="lazy"
                                            >
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<script>
    document.querySelectorAll('.feed-more-btn').forEach((button) => {
        const description = document.getElementById(
            button.getAttribute('aria-controls')
        );

        if (!description) {
            return;
        }

        if (description.scrollHeight > description.clientHeight + 1) {
            button.hidden = false;
        }

        button.addEventListener('click', () => {
            const expanded = description.classList.toggle('expanded');
            button.textContent = expanded ? 'See less' : 'See more';
            button.setAttribute('aria-expanded', String(expanded));
        });
    });
</script>
<?php
if ($feedStatement) {
    $feedStatement->close();
}

feedRenderPageEnd($conn);
