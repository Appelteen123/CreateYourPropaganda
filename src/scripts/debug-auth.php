<?php
require __DIR__ . '/../db.php';

echo "FotoForum auth debug\n";

$health = ff_storage_healthcheck();
printf("Storage dir: %s\n", $health['dir'] ?? '(unknown)');
printf("Exists: %s\n", ($health['exists'] ?? false) ? 'yes' : 'no');
printf("Writable: %s\n", ($health['writable'] ?? false) ? 'yes' : 'no');
printf("Probe ok: %s\n", ($health['probe'] ?? false) ? 'yes' : 'no');

if (!($health['ok'] ?? false)) {
	echo "Storage healthcheck failed, aborting.\n";
	exit(1);
}

$username = 'debug_' . substr(sha1(uniqid('', true)), 0, 8);
$password = bin2hex(random_bytes(4));
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$result = ff_create_user($username, $hashedPassword);
if (!($result['ok'] ?? false)) {
	printf("User creation failed (%s).\n", $result['code'] ?? 'unknown');
	exit(1);
}

$user = $result['user'] ?? [];
printf("Created user #%s (%s).\n", $user['id'] ?? '?', $username);
printf("Temporary password: %s\n", $password);

$lookup = ff_find_user_by_username($username);
if (!$lookup) {
	echo "Lookup failed.\n";
	exit(1);
}

if (!password_verify($password, $lookup['password'])) {
	echo "Password verify failed.\n";
	exit(1);
}

echo "Lookup + password verify succeeded.\n";

echo "Note: this user remains in data/users.json. Remove it manually if needed.\n";
