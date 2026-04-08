<?php
include 'db.php';

$pageTitle = 'Live Top 5 Collage';
$extraStyles = ['live-collage.css'];
include 'header.php';

$topPosts = ff_get_top_posts(5);
?>

<div class="live-collage-page">
    <header class="live-collage-header">
        <div>
            <h1>Live Top 5</h1>
            <p>Automatisch vernieuwd voor beamer-weergave.</p>
        </div>
        <div class="live-collage-actions">
            <button type="button" id="fullscreenBtn" class="collage-btn">Volledig scherm</button>
            <a href="feed.php" class="collage-btn secondary">Terug naar feed</a>
        </div>
    </header>

    <?php if (empty($topPosts)): ?>
        <section class="collage-empty">
            <h2>Nog geen posts beschikbaar</h2>
            <p>Zodra er foto's gepost worden, verschijnt hier automatisch de live top 5.</p>
        </section>
    <?php else: ?>
        <section class="collage-grid" aria-label="Live top 5 foto's">
            <?php foreach ($topPosts as $index => $post): ?>
                <article class="collage-card">
                    <div class="collage-rank">#<?php echo $index + 1; ?></div>
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Topfoto van <?php echo htmlspecialchars($post['username']); ?>">
                    <div class="collage-meta">
                        <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                        <p><?php echo (int) $post['like_count']; ?> likes</p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

<script>
(function () {
    const refreshIntervalMs = 30000;
    setTimeout(function () {
        window.location.reload();
    }, refreshIntervalMs);

    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', async function () {
            if (!document.fullscreenElement) {
                try {
                    await document.documentElement.requestFullscreen();
                } catch (e) {
                    console.error(e);
                }
            } else {
                document.exitFullscreen();
            }
        });
    }
})();
</script>

</body>
</html>
