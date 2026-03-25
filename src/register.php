<?php
include 'db.php';

$success = '';
$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: feed.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vul alle velden in.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

		if (ff_create_user($username, $hashedPassword)) {
            $success = 'Account aangemaakt! Je kunt nu inloggen.';
        } else {
			$error = 'Gebruikersnaam bestaat al of registreren is mislukt.';
        }
    }
}

include 'header.php';
?>

<div class="container auth-wrapper">
    <form method="POST" class="auth-form">
        <h2 class="auth-title">Registreren</h2>
        <p class="auth-subtitle">Maak je account aan en deel je foto's</p>

        <?php if ($success): ?>
            <div class="auth-message auth-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Log hier in</a>.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="auth-message auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <label for="username">Gebruikersnaam</label>
        <input id="username" type="text" name="username" placeholder="Kies een gebruikersnaam" required>

        <label for="password">Wachtwoord</label>
        <input id="password" type="password" name="password" placeholder="Kies een sterk wachtwoord" required>

        <button type="submit">Maak account</button>
        <p class="auth-link">Heb je al een account? <a href="login.php">Log hier in</a></p>
    </form>
</div>

</body>
</html>