<?php
session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/mail_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (!empty($email)) {
        // Prüfen, ob Email existiert
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            // Token ist 1 Stunde gültig
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($update->execute([$reset_token, $expires, $user['id']])) {
                
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/admin/reset.php?token=" . $reset_token;
                
                $mailSubject = "Passwort zurücksetzen - RuDaTrans";
                $mailBody = "
                    <h2>Passwort zurücksetzen</h2>
                    <p>Du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt.</p>
                    <p>Klicke auf den folgenden Link, um ein neues Passwort zu vergeben (Der Link ist 1 Stunde gültig):</p>
                    <p><a href='$resetLink'>Neues Passwort setzen</a></p>
                    <p>Oder kopiere diesen Link in den Browser: <br>$resetLink</p>
                    <p>Falls du das nicht warst, kannst du diese E-Mail einfach ignorieren.</p>
                ";

                if (sendMail($email, $mailSubject, $mailBody)) {
                    $success = "Eine E-Mail mit weiteren Anweisungen wurde an dich gesendet.";
                } else {
                    $error = "E-Mail konnte nicht gesendet werden. Überprüfe die mail_config.php Einstellungen.";
                }
            } else {
                $error = "Datenbankfehler beim Erstellen des Tokens.";
            }
        } else {
            // Aus Sicherheitsgründen die gleiche Meldung anzeigen
            $success = "Eine E-Mail mit weiteren Anweisungen wurde an dich gesendet.";
        }
    } else {
        $error = "Bitte gib eine E-Mail-Adresse ein.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort vergessen - RuDaTrans Backend</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--bg-dark); }
        .auth-container { background-color: var(--box-white); padding: 40px; border-radius: var(--radius-large); box-shadow: 0 4px 15px rgba(0,0,0,0.5); width: 100%; max-width: 400px; text-align: center; }
        .auth-container h2 { color: var(--card-red); margin-bottom: 20px; }
        .auth-container p { color: #555; margin-bottom: 20px; font-size: 0.9rem; }
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
        <h2>Passwort vergessen?</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="links"><a href="login.php">Zurück zum Login</a></div>
        <?php else: ?>
            <p>Trage deine E-Mail-Adresse ein und wir senden dir einen sicheren Link, um dein Passwort neu zu vergeben.</p>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="E-Mail Adresse" required>
                <button type="submit">Link anfordern</button>
            </form>

            <div class="links">
                <a href="login.php">Zurück zum Login</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
