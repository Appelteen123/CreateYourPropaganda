<?php
include 'db.php';

include 'header.php';

$posts = [];
$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

try {
	$stmt = $pdo->prepare(
		"SELECT
			p.id,
			p.user_id,
			p.image_url,
			p.description,
			p.created_at,
			u.username,
			COALESCE(SUM(CASE WHEN v.vote_type = 'like' THEN 1 ELSE 0 END), 0) AS like_count,
			COALESCE(SUM(CASE WHEN v.vote_type = 'dislike' THEN 1 ELSE 0 END), 0) AS dislike_count,
			MAX(CASE WHEN v.user_id = :current_user THEN v.vote_type ELSE NULL END) AS user_vote
		FROM posts p
		JOIN users u ON u.id = p.user_id
		LEFT JOIN votes v ON v.post_id = p.id
		GROUP BY p.id, p.user_id, p.image_url, p.description, p.created_at, u.username
		ORDER BY p.id DESC"
	);
	$stmt->execute(['current_user' => $currentUserId]);
	$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$posts = [];
}
?>

<div class="container">
	<header class="hero page-hero">
		<h1>Foto Feed</h1>
		<p>Bekijk de nieuwste foto's van de community.</p>
	</header>

	<?php if (empty($posts)): ?>
		<section class="page-panel empty-state">
			<h3>Nog geen foto's geplaatst</h3>
			<p>Upload als eerste een foto om de feed te vullen.</p>
			<?php if (isset($_SESSION['user_id'])): ?>
				<a href="upload.php" class="btn">Plaats een foto</a>
			<?php else: ?>
				<a href="login.php" class="btn">Log in om te posten</a>
			<?php endif; ?>
		</section>
	<?php else: ?>
		<?php foreach ($posts as $post): ?>
			<article class="post-card">
				<?php if (!empty($post['image_url'])): ?>
					<img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Foto van <?php echo htmlspecialchars($post['username']); ?>">
				<?php endif; ?>
				<div class="post-content">
					<?php if (!empty($post['description'])): ?>
						<p><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
					<?php else: ?>
						<p><em>Geen beschrijving toegevoegd.</em></p>
					<?php endif; ?>
					<p class="post-meta">Geplaatst door <strong><?php echo htmlspecialchars($post['username']); ?></strong> op <?php echo htmlspecialchars($post['created_at']); ?></p>

					<div class="vote-row">
						<div class="vote-counts">
							<span>👍 <?php echo (int) $post['like_count']; ?></span>
							<span>👎 <?php echo (int) $post['dislike_count']; ?></span>
						</div>

						<?php if (isset($_SESSION['user_id'])): ?>
							<form method="POST" action="vote.php" class="vote-form">
								<input type="hidden" name="post_id" value="<?php echo (int) $post['id']; ?>">
								<input type="hidden" name="type" value="like">
								<input type="hidden" name="redirect_to" value="feed.php">
								<button type="submit" class="vote-btn <?php echo ($post['user_vote'] === 'like') ? 'active-like' : ''; ?>">Like</button>
							</form>

							<form method="POST" action="vote.php" class="vote-form">
								<input type="hidden" name="post_id" value="<?php echo (int) $post['id']; ?>">
								<input type="hidden" name="type" value="dislike">
								<input type="hidden" name="redirect_to" value="feed.php">
								<button type="submit" class="vote-btn <?php echo ($post['user_vote'] === 'dislike') ? 'active-dislike' : ''; ?>">Dislike</button>
							</form>
						<?php else: ?>
							<a href="login.php" class="vote-login-link">Log in om te stemmen</a>
						<?php endif; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

</body>
</html>
