<?php 
include 'db.php'; 
include 'header.php'; 
?>

<div class="container">
    <header class="hero">
        <h1>Welkom bij FotoForum</h1>
        <p>De plek om je mooiste foto's te delen en te beoordelen.</p>
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="cta-buttons">
                <a href="register.php" class="btn">Maak nu een account aan</a>
            </div>
        <?php endif; ?>
    </header>

    <section class="leaderboard">
        <h3>🏆 Top 20 Meest Gelikete Foto's</h3>
        <div class="top-posters">
            <?php
            $topPosts = ff_get_top_posts(20);
            $rank = 1;
            foreach ($topPosts as $row): ?>
                <div class="photo-rank">
                    <span class="rank">#<?php echo $rank++; ?></span>
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Top foto" class="photo-rank-thumb">
                    <div class="photo-rank-info">
                        <strong>Door <?php echo htmlspecialchars($row['username']); ?></strong>
                        <span><?php echo (int) $row['like_count']; ?> likes</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="leaderboard-download">
            <a href="live_collage.php" class="btn">Open Live Top 5 Collage</a>
            <a href="download_top20.php" class="btn">Download top 20 als ZIP</a>
        </div>
    </section>
</div>

</body>
</html>