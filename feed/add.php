<?php

require __DIR__ . '/common.php';

$authorizedClubs = feedAuthorizedClubs($conn, $email, $isAdmin);

if (count($authorizedClubs) === 0) {
    http_response_code(403);
    exit('You do not have permission to create club posts.');
}

feedRenderPageStart($conn, 'Add Post', $role, $isAdmin);
?>
<div class="contentcontainer">
    <div class="belowtopnavcontainer">
        <main class="sis-main" id="main">
            <div class="content">
                <div class="section-head">
                    <h2>Add Post</h2>
                    <p>Publish an update to the club activity feed.</p>
                </div>

                <section class="panel-section feed-form-panel">
                    <form id="feed-add-form" enctype="multipart/form-data">
                        <div class="form-group see-thru">
                            <div class="form-group-title">Select Club</div>
                            <div class="club-list">
                                <label for="feed-club-id" class="visually-hidden">Club</label>
                                <select
                                    id="feed-club-id"
                                    name="ClubID"
                                    class="club-options"
                                    size="<?= min(10, max(2, count($authorizedClubs))) ?>"
                                    required
                                >
                                    <?php foreach ($authorizedClubs as $club): ?>
                                        <option value="<?= (int) $club['ClubID'] ?>">
                                            <?= feedEscape($club['Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                    required
                                >

                                <label for="feed-description">Post Content</label>
                                <textarea
                                    id="feed-description"
                                    name="Description"
                                    class="form-input"
                                    maxlength="4095"
                                    required
                                ></textarea>

                                <label for="feed-image">Optional Image</label>
                                <input
                                    id="feed-image"
                                    name="Image"
                                    type="file"
                                    class="form-input"
                                    accept="image/png,.png"
                                >
                            </div>
                        </div>

                        <p id="feed-form-status" class="feed-form-status" role="status"></p>

                        <div class="form-btn-group">
                            <button class="form-btn" type="submit">Add Post</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
<script>
    const addForm = document.getElementById('feed-add-form');
    const addStatus = document.getElementById('feed-form-status');
    const addButton = addForm.querySelector('button[type="submit"]');

    addForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        addButton.disabled = true;
        addStatus.textContent = 'Publishing post...';

        const requestData = new FormData(addForm);
        requestData.set('RequestType', 'feed-add');

        try {
            const response = await fetch('../post.php', {
                method: 'POST',
                body: requestData
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'The post could not be published.');
            }

            window.location.href = './?view=all';
        } catch (error) {
            addStatus.textContent = error.message;
            addButton.disabled = false;
        }
    });
</script>
<?php feedRenderPageEnd($conn); ?>
