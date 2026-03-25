<?php
include 'db.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: feed.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

	$user = ff_find_user_by_username($username);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: feed.php');
        exit;
    }

    $error = 'Oeps! Verkeerde gegevens.';
}

include 'header.php';
?>

<div class="container auth-wrapper">
    <form method="POST" class="auth-form">
        <h2 class="auth-title">Inloggen</h2>
        <p class="auth-subtitle">Welkom terug bij FotoForum</p>

        <?php if ($error): ?>
            <div class="auth-message auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <label for="username">Gebruikersnaam</label>
        <input id="username" type="text" name="username" placeholder="Bijv. fotofan123" required>

        <label for="password">Wachtwoord</label>
        <input id="password" type="password" name="password" placeholder="Vul je wachtwoord in" required>

        <button type="submit">Log in</button>
        <p class="auth-link">Nog geen account? <a href="register.php">Registreer hier</a></p>
    </form>
</div>

</body>
</html>