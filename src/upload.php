<?php
include 'db.php';

function respondJson($statusCode, array $payload)
{
	http_response_code($statusCode);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: create.php');
	exit;
}

if (!isset($_SESSION['user_id'])) {
	respondJson(401, ['success' => false, 'error' => 'Log eerst in om je ontwerp te posten.']);
}

if (($_POST['from_designer'] ?? '') !== '1') {
	respondJson(403, ['success' => false, 'error' => 'Alleen uploads vanuit de designer zijn toegestaan.']);
}

$description = trim($_POST['description'] ?? '');

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
	respondJson(400, ['success' => false, 'error' => 'Genereren of uploaden van je ontwerp is mislukt.']);
}

$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$imageInfo = @getimagesize($_FILES['image']['tmp_name']);

if ($imageInfo === false || !in_array($imageInfo['mime'], $allowedMimeTypes, true)) {
	respondJson(400, ['success' => false, 'error' => 'Alleen JPG, PNG, GIF of WEBP afbeeldingen zijn toegestaan.']);
}

$uploadDir = getenv('UPLOAD_DIR_ABS') ?: (__DIR__ . '/uploads');
$uploadUrlPrefix = trim((string) (getenv('UPLOAD_URL_PREFIX') ?: 'uploads'), '/');
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
	respondJson(500, ['success' => false, 'error' => 'Uploadmap kon niet worden aangemaakt.']);
}

$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
if ($extension === '') {
	$extension = 'png';
}

$fileName = uniqid('design_', true) . '.' . $extension;
$targetPath = $uploadDir . '/' . $fileName;
$dbPath = $uploadUrlPrefix . '/' . $fileName;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
	respondJson(500, ['success' => false, 'error' => 'Opslaan van het ontwerp is mislukt.']);
}

if (!ff_create_post($_SESSION['user_id'], $dbPath, $description)) {
	@unlink($targetPath);
	respondJson(500, ['success' => false, 'error' => 'Post opslaan is mislukt. Probeer opnieuw.']);
}

respondJson(200, ['success' => true, 'message' => 'Ontwerp succesvol gepost.', 'redirect' => 'feed.php']);
