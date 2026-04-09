<?php
require_once __DIR__ . '/../db_config.php';

$error = '';
$success = '';
$isValidToken = false;
$user_id = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Prüfen ob Token existiert und noch nicht abgelaufen ist
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $isValidToken = true;
        $user_id = $user['id'];
    } else {
        $error = "Ungültiger oder abgelaufener Link.";
    }
} else {
    $error = "Kein Token angegeben.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!empty($password) && !empty($password_confirm)) {
        if ($password !== $password_confirm) {
            $error = "Die Passwörter stimmen nicht überein.";
        } else {
            // Passwort aktualisieren und Token löschen
            $hashed_pw = password_hash($password, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            
            if ($update->execute([$hashed_pw, $user_id])) {
                $success = "Dein Passwort wurde erfolgreich geändert!";
                $isValidToken = false; // Formular ausblenden
            } else {
                $error = "Fehler beim Speichern des neuen Passworts.";
            }
        }
    } else {
        $error = "Bitte fülle beide Felder aus.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen - RuDaTrans Backend</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--bg-dark); }
        .auth-container { background-color: var(--box-white); padding: 40px; border-radius: var(--radius-large); box-shadow: 0 4px 15px rgba(0,0,0,0.5); width: 100%; max-width: 400px; text-align: center; }
        .auth-container h2 { color: var(--card-red); margin-bottom: 20px; }
        .auth-container input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: var(--radius-small); background-color: #eee; color: #333; box-sizing: border-box; }
        .auth-container button { width: 100%; padding: 10px; background-color: var(--btn-yellow); color: #000; border: none; border-radius: var(--radius-small); font-weight: bold; cursor: pointer; transition: all 0.3s ease; }
        .auth-container button:hover { background-color: #e6b800; transform: translateY(-2px); }
        .auth-container .error { color: red; margin-bottom: 15px; font-size: 0.9rem; }
        .auth-container .success { color: green; margin-bottom: 15px; font-size: 0.9rem; font-weight: bold; }
        .auth-container .links { margin-top: 15px; font-size: 0.85rem; }
        .auth-container .links a { color: #333; text-decoration: none; }
        .auth-container .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="auth-container">
        <h2>Neues Passwort</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="links"><a href="login.php">Jetzt Einloggen</a></div>
        <?php elseif ($isValidToken): ?>
            <form method="POST" action="">
                <input type="password" name="password" placeholder="Neues Passwort" required minlength="6">
                <input type="password" name="password_confirm" placeholder="Passwort bestätigen" required minlength="6">
                <button type="submit">Passwort speichern</button>
            </form>
        <?php else: ?>
            <div class="links"><a href="forgot.php">Neuen Link anfordern</a> | <a href="login.php">Zum Login</a></div>
        <?php endif; ?>
    </div>

</body>
</html>
