<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'FotoForum';
$favicon = $favicon ?? '';
$extraStyles = $extraStyles ?? [];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($favicon); ?>" type="image/png">
    <?php endif; ?>
    <link rel="stylesheet" href="style.css">
    <?php foreach ($extraStyles as $stylesheet): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($stylesheet); ?>">
    <?php endforeach; ?>
</head>
<body>

<nav>
    <div class="nav-container">
        <a href="index.php" class="logo">📸Create Your Own Propaganda</a>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="feed.php">Feed</a>
            <a href="create.php">Create</a>

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn-auth">Uitloggen</a>
            <?php else: ?>
                <a href="login.php">Inloggen</a>
                <a href="register.php" class="btn-auth">Registreren</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
