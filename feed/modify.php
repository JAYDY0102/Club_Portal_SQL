<?php

require __DIR__ . '/common.php';

$postID = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);

if ($postID === false || $postID === null || $postID < 1) {
    http_response_code(404);
    exit('The requested post does not exist.');
}

$postStatement = $conn->prepare(
    'SELECT
        feed.PostID,
        feed.Title,
        feed.Description,
        feed.ImageID,
        clubs.ClubID,
        clubs.Name AS ClubName,
        clubs.Executives,
        clubs.Advisors
     FROM feed
     INNER JOIN clubs ON clubs.ClubID = feed.ClubID
     WHERE feed.PostID = ? AND feed.Visible = 1'
);

if (!$postStatement) {
    http_response_code(500);
    exit('The post could not be loaded.');
}

$postStatement->bind_param('i', $postID);
$postStatement->execute();
$post = $postStatement->get_result()->fetch_assoc();
$postStatement->close();

if (!$post) {
    http_response_code(404);
    exit('The requested post does not exist.');
}

if (!feedUserCanManageClub($post, $email, $isAdmin)) {
    http_response_code(403);
    exit('You do not have permission to modify this post.');
}

$imageID = $post['ImageID'] !== null ? (int) $post['ImageID'] : null;
$imageFile = $imageID !== null
    ? __DIR__ . '/../assets/feed/' . $imageID . '.png'
    : null;
$hasCurrentImage = $imageFile !== null && is_file($imageFile);

feedRenderPageStart($conn, 'Modify Post', $role, $isAdmin);
?>
<div class="contentcontainer">
    <div class="belowtopnavcontainer">
        <main class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Modify Post</h2>
                    <p>Update or permanently delete this feed post.</p>
                </div>

                <section class="panel-section feed-form-panel">
                    <form id="feed-modify-form" enctype="multipart/form-data">
                        <div class="form-group see-thru">
                            <div class="form-group-title">Club</div>
                            <div class="club-list">
                                <select class="club-options" size="2" disabled aria-label="Post club">
                                    <option selected><?= feedEscape($post['ClubName']) ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group see-thru">
                            <div class="form-group-title">Post Details</div>
                            <div class="form-grid feed-form-grid">
                                <label for="feed-title">Post Title</label>
                                <input
                                    id="feed-title"
                                    name="Title"
                                    type="text"
                                    class="form-input"
                                    maxlength="255"
                                    value="<?= feedEscape($post['Title']) ?>"
                                    required
                                >

                                <label for="feed-description">Post Content</label>
                                <textarea
                                    id="feed-description"
                                    name="Description"
                                    class="form-input"
                                    maxlength="4095"
                                    required
                                ><?= feedEscape($post['Description']) ?></textarea>

                                <label for="feed-image">Replace Image</label>
                                <input
                                    id="feed-image"
                                    name="Image"
                                    type="file"
                                    class="form-input"
                                    accept="image/png,.png"
                                >
                            </div>

                            <?php if ($hasCurrentImage): ?>
                                <div class="feed-current-image">
                                    <p>Current image</p>
                                    <img
                                        src="../assets/feed/<?= $imageID ?>.png"
                                        alt="Current image for <?= feedEscape($post['Title']) ?>"
                                    >
                                    <label class="feed-remove-image" for="feed-remove-image">
                                        <input id="feed-remove-image" type="checkbox" name="RemoveImage" value="1">
                                        Remove the current image
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p id="feed-form-status" class="feed-form-status" role="status"></p>

                        <div class="form-btn-group">
                            <button class="form-btn" type="submit">Save Post</button>
                            <a class="form-btn" href="add.php">New Post</a>
                            <button class="form-btn" id="delete-post-btn" type="button">Delete Post</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
<script>
    const modifyForm = document.getElementById('feed-modify-form');
    const modifyStatus = document.getElementById('feed-form-status');
    const saveButton = modifyForm.querySelector('button[type="submit"]');
    const deleteButton = document.getElementById('delete-post-btn');
    const imageInput = document.getElementById('feed-image');
    const removeImage = document.getElementById('feed-remove-image');
    const postID = <?= $postID ?>;

    if (removeImage) {
        removeImage.addEventListener('change', () => {
            if (removeImage.checked) {
                imageInput.value = '';
            }
        });

        imageInput.addEventListener('change', () => {
            if (imageInput.files.length > 0) {
                removeImage.checked = false;
            }
        });
    }

    async function sendPostRequest(requestData) {
        const response = await fetch('../post.php', {
            method: 'POST',
            body: requestData
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'The request could not be completed.');
        }

        window.location.href = './?view=all';
    }

    modifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        saveButton.disabled = true;
        deleteButton.disabled = true;
        modifyStatus.textContent = 'Saving post...';

        const requestData = new FormData(modifyForm);
        requestData.set('RequestType', 'feed-update');
        requestData.set('PostID', String(postID));

        if (!removeImage || !removeImage.checked) {
            requestData.set('RemoveImage', '0');
        }

        try {
            await sendPostRequest(requestData);
        } catch (error) {
            modifyStatus.textContent = error.message;
            saveButton.disabled = false;
            deleteButton.disabled = false;
        }
    });

    deleteButton.addEventListener('click', async () => {
        const confirmed = window.confirm(
            'Delete this post permanently? This action cannot be undone.'
        );

        if (!confirmed) {
            return;
        }

        saveButton.disabled = true;
        deleteButton.disabled = true;
        modifyStatus.textContent = 'Deleting post...';

        const requestData = new FormData();
        requestData.set('RequestType', 'feed-delete');
        requestData.set('PostID', String(postID));

        try {
            await sendPostRequest(requestData);
        } catch (error) {
            modifyStatus.textContent = error.message;
            saveButton.disabled = false;
            deleteButton.disabled = false;
        }
    });
</script>
<?php feedRenderPageEnd($conn); ?>
