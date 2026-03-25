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
            $stmt = $pdo->query(
                "SELECT
                    p.id,
                    p.image_url,
                    p.description,
                    p.created_at,
                    u.username,
                    COUNT(v.id) AS like_count
                FROM posts p
                JOIN users u ON u.id = p.user_id
                LEFT JOIN votes v ON v.post_id = p.id AND v.vote_type = 'like'
                GROUP BY p.id, p.image_url, p.description, p.created_at, u.username
                ORDER BY like_count DESC, p.id DESC
                
                LIMIT 20"
            );
            $rank = 1;
            while ($row = $stmt->fetch()): ?>
                <div class="photo-rank">
                    <span class="rank">#<?php echo $rank++; ?></span>
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Top foto" class="photo-rank-thumb">
                    <div class="photo-rank-info">
                        <strong>Door <?php echo htmlspecialchars($row['username']); ?></strong>
                        <span><?php echo (int) $row['like_count']; ?> likes</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="leaderboard-download">
            <a href="download_top20.php" class="btn">Download top 20 als ZIP</a>
        </div>
    </section>
</div>

</body>
</html>