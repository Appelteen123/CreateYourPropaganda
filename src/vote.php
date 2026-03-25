<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: feed.php');
	exit;
}

$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
$user_id = (int) $_SESSION['user_id'];
$type = $_POST['type'] ?? '';
$redirectTo = $_POST['redirect_to'] ?? 'feed.php';

$allowedRedirects = ['feed.php', 'index.php'];
if (!in_array($redirectTo, $allowedRedirects, true)) {
	$redirectTo = 'feed.php';
}

if ($post_id <= 0 || !in_array($type, ['like', 'dislike'], true)) {
	header('Location: ' . $redirectTo);
	exit;
}

ff_set_vote($user_id, $post_id, $type);

header('Location: ' . $redirectTo);
exit;