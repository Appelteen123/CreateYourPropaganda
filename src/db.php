<?php
session_start();

function ff_detect_storage_dir()
{
	$configuredDir = getenv('DATA_DIR_ABS');
	if (is_string($configuredDir) && trim($configuredDir) !== '') {
		return rtrim($configuredDir, '/\\');
	}

	$azureHome = getenv('HOME');
	if (is_string($azureHome) && trim($azureHome) !== '') {
		return rtrim($azureHome, '/\\') . '/site/data/fotoforum';
	}

	return __DIR__ . '/data';
}

$storageDir = ff_detect_storage_dir();

if (!is_dir($storageDir)) {
	mkdir($storageDir, 0777, true);
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
		error_log("Could not ensure file exists: $path");
		return false;
	}
	
	$json = json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		error_log("JSON encode failed for collection: $name");
		return false;
	}
	
	if (!file_put_contents($path, $json, LOCK_EX)) {
		error_log("Failed to write collection to file: $path");
		return false;
	}
	
	return true;
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
			return false;
		}
	}

	$users[] = [
		'id' => ff_next_id($users),
		'username' => $username,
		'password' => $passwordHash,
		'created_at' => date('Y-m-d H:i:s'),
	];

	$result = ff_save_collection('users', $users);
	if (!$result) {
		error_log("Failed to save user: $username");
	}
	return $result;
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
	if (!$result) {
		error_log("Failed to save post for user: $userId");
	}
	return $result;
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
	if (!$result) {
		error_log("Failed to save vote: user=$userId, post=$postId, type=$type");
	}
	return $result;
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

function ff_get_all_stickers()
{
	$stickersDir = __DIR__ . '/stickers';
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
				'path' => 'stickers/' . $file,
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