<?php
session_start();

$storageDir = getenv('DATA_DIR_ABS') ?: (__DIR__ . '/data');

if (!is_dir($storageDir)) {
	mkdir($storageDir, 0777, true);
}

function ff_storage_file($name)
{
	global $storageDir;
	return $storageDir . '/' . $name . '.json';
}

function ff_ensure_file($path)
{
	if (!is_file($path)) {
		file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
	}
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
	ff_ensure_file($path);
	file_put_contents($path, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
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

	ff_save_collection('users', $users);
	return true;
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
	ff_save_collection('posts', $posts);
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

	ff_save_collection('votes', $filtered);
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

ff_ensure_file(ff_storage_file('users'));
ff_ensure_file(ff_storage_file('posts'));
ff_ensure_file(ff_storage_file('votes'));
?>