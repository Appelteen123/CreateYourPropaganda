<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$description = trim($_POST['description'] ?? '');

	if (empty($_FILES['image']['name'])) {
		$error = 'Kies een foto om te posten.';
	} elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
		$error = 'Uploaden van de foto is mislukt. Probeer opnieuw.';
	} else {
		$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$imageInfo = @getimagesize($_FILES['image']['tmp_name']);

		if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimeTypes, true)) {
			$error = 'Alleen JPG, PNG, GIF of WEBP afbeeldingen zijn toegestaan.';
		} else {
			$uploadDir = __DIR__ . '/uploads';
			if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
				$error = 'Uploadmap kon niet worden aangemaakt.';
			} else {
				$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
				$fileName = uniqid('foto_', true) . '.' . $extension;
				$targetPath = $uploadDir . '/' . $fileName;
				$dbPath = 'uploads/' . $fileName;

				if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
					$error = 'Opslaan van de foto is mislukt.';
				} else {
					$stmt = $pdo->prepare("INSERT INTO posts (user_id, image_url, description) VALUES (?, ?, ?)");
					if ($stmt->execute([$_SESSION['user_id'], $dbPath, $description])) {
						$success = 'Foto succesvol gepost!';
					} else {
						@unlink($targetPath);
						$error = 'Post opslaan is mislukt. Probeer opnieuw.';
					}
				}
			}
		}
	}
}

include 'header.php';
?>

<div class="container">
	<header class="hero page-hero">
		<h1>Foto Uploaden</h1>
		<p>Kies je foto, voeg een titel toe en plaats hem direct op de feed.</p>
	</header>

	<section class="page-panel">
		<form method="POST" enctype="multipart/form-data" class="upload-form">
			<h3>Nieuwe post</h3>

			<?php if ($error): ?>
				<div class="auth-message auth-error"><?php echo htmlspecialchars($error); ?></div>
			<?php endif; ?>

			<?php if ($success): ?>
				<div class="auth-message auth-success"><?php echo htmlspecialchars($success); ?> <a href="feed.php">Bekijk op de feed</a>.</div>
			<?php endif; ?>

			<label for="description">Beschrijving (optioneel)</label>
			<textarea id="description" name="description" rows="4" placeholder="Vertel iets over je foto..."></textarea>

			<label for="image">Foto</label>
			<input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>

			<button type="submit">Post foto</button>
		</form>
	</section>
</div>

</body>
</html>
