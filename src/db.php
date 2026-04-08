<?php
session_start();

function ff_trimmed_path($path)
{
	return rtrim((string) $path, "/\\");
}

function ff_prepare_storage_dir($dir, &$failure = null)
{
	$normalized = ff_trimmed_path($dir);
	if ($normalized === '') {
		$failure = 'empty_path';
		return false;
	}

	if (!is_dir($normalized) && !@mkdir($normalized, 0777, true)) {
		$failure = 'mkdir_failed';
		return false;
	}

	if (!is_writable($normalized)) {
		$failure = 'not_writable';
		return false;
	}

	return $normalized;
}

function ff_candidate_storage_dirs()
{
	$candidates = [];

	$configuredDir = getenv('DATA_DIR_ABS');
	if (is_string($configuredDir) && trim($configuredDir) !== '') {
		$candidates[] = $configuredDir;
	}

	$azureHome = getenv('HOME');
	if (is_string($azureHome) && trim($azureHome) !== '') {
		$candidates[] = rtrim($azureHome, '/\\') . '/site/data/fotoforum';
	}

	$candidates[] = __DIR__ . '/data';

	return $candidates;
}

function ff_detect_storage_dir()
{
	$chosen = null;
	$lastFailure = '';

	foreach (ff_candidate_storage_dirs() as $candidate) {
		$failure = null;
		$attempt = ff_prepare_storage_dir($candidate, $failure);
		if ($attempt !== false) {
			$chosen = $attempt;
			break;
		}
		$lastFailure = $failure ?: 'unknown';
		error_log("FotoForum storage candidate rejected ({$lastFailure}): {$candidate}");
	}

	if ($chosen === null) {
		$fallback = __DIR__ . '/data';
		$chosen = ff_prepare_storage_dir($fallback, $lastFailure) ?: $fallback;
	}

	return $chosen;
}

$storageDir = ff_detect_storage_dir();

function ff_storage_healthcheck()
{
	global $storageDir;
	$dir = $storageDir;

	$result = [
		'dir' => $dir,
		'exists' => is_dir($dir),
		'writable' => is_writable($dir),
		'probe' => false,
		'probe_error' => null,
	];

	if ($result['exists'] && $result['writable']) {
		$probeFile = $dir . '/.ff_probe_' . uniqid('', true) . '.tmp';
		$payload = (string) microtime(true);
		$bytes = @file_put_contents($probeFile, $payload);
		if ($bytes === false) {
			$result['probe_error'] = 'write_failed';
		} else {
			$read = @file_get_contents($probeFile);
			$result['probe'] = ($read === $payload);
			if (!$result['probe']) {
				$result['probe_error'] = 'read_mismatch';
			}
			@unlink($probeFile);
		}
	} else {
		$result['probe_error'] = $result['exists'] ? 'not_writable' : 'missing_dir';
	}

	$result['ok'] = $result['exists'] && $result['writable'] && $result['probe'] && !$result['probe_error'];

	return $result;
}

function ff_cached_storage_healthcheck()
{
	static $cache = null;
	if ($cache === null) {
		$cache = ff_storage_healthcheck();
	}
	return $cache;
}

function ff_storage_file($name)
{
	global $storageDir;
	return $storageDir . '/' . $name . '.json';
}

function ff_migrate_legacy_data_if_needed()
{
	global $storageDir;

	$legacyDir = __DIR__ . '/data';
	$normalizedStorage = str_replace('\\', '/', rtrim($storageDir, '/\\'));
	$normalizedLegacy = str_replace('\\', '/', rtrim($legacyDir, '/\\'));

	if ($normalizedStorage === $normalizedLegacy || !is_dir($legacyDir)) {
		return;
	}

	$collections = ['users', 'posts', 'votes'];
	foreach ($collections as $collection) {
		$source = $legacyDir . '/' . $collection . '.json';
		$destination = ff_storage_file($collection);

		if (!is_file($source) || is_file($destination)) {
			continue;
		}

		@copy($source, $destination);
	}
}

function ff_ensure_file($path)
{
	if (!is_file($path)) {
		$dir = dirname($path);
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0777, true)) {
				error_log("Failed to create directory: $dir");
				return false;
			}
		}
		if (!file_put_contents($path, json_encode([], JSON_PRETTY_PRINT))) {
			error_log("Failed to create file: $path");
			return false;
		}
	}
	return true;
}

function ff_load_collection($name)
{
	$path = ff_storage_file($name);
	ff_ensure_file($path);

	$content = file_get_contents($path);
	$data = json_decode($content ?: '[]', true);

	return is_array($data) ? $data : [];
}

function ff_save_collection($name, array $data)
{
	$path = ff_storage_file($name);
	if (!ff_ensure_file($path)) {
		$message = "Could not ensure file exists: $path";
		error_log($message);
		return [
			'ok' => false,
			'code' => 'ensure',
			'message' => $message,
			'path' => $path,
		];
	}

	$json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		$message = "JSON encode failed for collection: $name";
		error_log($message);
		return [
			'ok' => false,
			'code' => 'json',
			'message' => $message,
			'path' => $path,
		];
	}

	$bytes = @file_put_contents($path, $json, LOCK_EX);
	if ($bytes === false) {
		$message = "Failed to write collection to file: $path";
		error_log($message);
		return [
			'ok' => false,
			'code' => 'write',
			'message' => $message,
			'path' => $path,
			'health' => ff_storage_healthcheck(),
		];
	}

	return [
		'ok' => true,
		'path' => $path,
		'count' => count($data),
	];
}

function ff_next_id(array $items)
{
	$maxId = 0;
	foreach ($items as $item) {
		$id = isset($item['id']) ? (int) $item['id'] : 0;
		if ($id > $maxId) {
			$maxId = $id;
		}
	}
	return $maxId + 1;
}

function ff_find_user_by_username($username)
{
	$users = ff_load_collection('users');
	foreach ($users as $user) {
		if (($user['username'] ?? '') === $username) {
			return $user;
		}
	}
	return null;
}

function ff_create_user($username, $passwordHash)
{
	$users = ff_load_collection('users');

	foreach ($users as $user) {
		if (($user['username'] ?? '') === $username) {
			return [
				'ok' => false,
				'code' => 'duplicate',
				'message' => 'Username already exists.',
			];
		}
	}

	$newUser = [
		'id' => ff_next_id($users),
		'username' => $username,
		'password' => $passwordHash,
		'created_at' => date('Y-m-d H:i:s'),
	];
	$users[] = $newUser;

	$result = ff_save_collection('users', $users);
	if (!$result['ok']) {
		error_log('Failed to save user: ' . $username . ' (' . ($result['code'] ?? 'unknown') . ')');
		return [
			'ok' => false,
			'code' => 'storage',
			'message' => 'Failed to persist user.',
			'details' => $result,
		];
	}

	return [
		'ok' => true,
		'user' => $newUser,
	];
}

function ff_create_post($userId, $imageUrl, $description)
{
	$posts = ff_load_collection('posts');
	$posts[] = [
		'id' => ff_next_id($posts),
		'user_id' => (int) $userId,
		'image_url' => $imageUrl,
		'description' => $description,
		'created_at' => date('Y-m-d H:i:s'),
	];
	$result = ff_save_collection('posts', $posts);
	if (!$result['ok']) {
		error_log('Failed to save post for user: ' . $userId . ' (' . ($result['code'] ?? 'unknown') . ')');
		return false;
	}
	return true;
}

function ff_set_vote($userId, $postId, $type)
{
	$votes = ff_load_collection('votes');
	$filtered = [];

	foreach ($votes as $vote) {
		if ((int) ($vote['user_id'] ?? 0) === (int) $userId && (int) ($vote['post_id'] ?? 0) === (int) $postId) {
			continue;
		}
		$filtered[] = $vote;
	}

	$filtered[] = [
		'id' => ff_next_id($filtered),
		'user_id' => (int) $userId,
		'post_id' => (int) $postId,
		'vote_type' => $type,
		'created_at' => date('Y-m-d H:i:s'),
	];

	$result = ff_save_collection('votes', $filtered);
	if (!$result['ok']) {
		error_log('Failed to save vote: user=' . $userId . ', post=' . $postId . ', type=' . $type . ' (' . ($result['code'] ?? 'unknown') . ')');
		return false;
	}

	return true;
}

function ff_get_posts_with_stats($currentUserId = 0)
{
	$users = ff_load_collection('users');
	$posts = ff_load_collection('posts');
	$votes = ff_load_collection('votes');

	$userMap = [];
	foreach ($users as $user) {
		$userMap[(int) $user['id']] = $user;
	}

	$result = [];
	foreach ($posts as $post) {
		$postId = (int) ($post['id'] ?? 0);
		$likeCount = 0;
		$dislikeCount = 0;
		$userVote = null;

		foreach ($votes as $vote) {
			if ((int) ($vote['post_id'] ?? 0) !== $postId) {
				continue;
			}

			if (($vote['vote_type'] ?? '') === 'like') {
				$likeCount++;
			} elseif (($vote['vote_type'] ?? '') === 'dislike') {
				$dislikeCount++;
			}

			if ((int) ($vote['user_id'] ?? 0) === (int) $currentUserId) {
				$userVote = $vote['vote_type'] ?? null;
			}
		}

		$owner = $userMap[(int) ($post['user_id'] ?? 0)] ?? ['username' => 'onbekend'];

		$result[] = [
			'id' => $postId,
			'user_id' => (int) ($post['user_id'] ?? 0),
			'image_url' => $post['image_url'] ?? '',
			'description' => $post['description'] ?? '',
			'created_at' => $post['created_at'] ?? '',
			'username' => $owner['username'] ?? 'onbekend',
			'like_count' => $likeCount,
			'dislike_count' => $dislikeCount,
			'user_vote' => $userVote,
		];
	}

	usort($result, function ($a, $b) {
		return (int) $b['id'] <=> (int) $a['id'];
	});

	return $result;
}

function ff_get_top_posts($limit = 20)
{
	$posts = ff_get_posts_with_stats(0);

	usort($posts, function ($a, $b) {
		$likeCompare = ((int) $b['like_count']) <=> ((int) $a['like_count']);
		if ($likeCompare !== 0) {
			return $likeCompare;
		}
		return ((int) $b['id']) <=> ((int) $a['id']);
	});

	return array_slice($posts, 0, (int) $limit);
}

ff_migrate_legacy_data_if_needed();
ff_ensure_file(ff_storage_file('users'));
ff_ensure_file(ff_storage_file('posts'));
ff_ensure_file(ff_storage_file('votes'));

function ff_get_all_stickers($folder = 'sitckers2')
{
	$safeFolder = trim((string) $folder, "/\\ \t\n\r\0\x0B");
	if ($safeFolder === '' || strpos($safeFolder, '..') !== false) {
		$safeFolder = 'sitckers2';
	}

	$stickersDir = __DIR__ . '/' . $safeFolder;
	$stickers = [];

	if (!is_dir($stickersDir)) {
		return $stickers;
	}

	$files = array_diff(scandir($stickersDir) ?: [], ['.', '..']);
	foreach ($files as $file) {
		$fullPath = $stickersDir . '/' . $file;
		if (is_file($fullPath) && preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $file)) {
			$stickers[] = [
				'name' => pathinfo($file, PATHINFO_FILENAME),
				'path' => $safeFolder . '/' . $file,
				'file' => $file,
			];
		}
	}

	usort($stickers, function ($a, $b) {
		return strcasecmp($a['file'], $b['file']);
	});

	return $stickers;
}
?>