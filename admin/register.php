<?php
session_start();
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/mail_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invite_code = $_POST['invite_code'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!empty($invite_code) && !empty($email) && !empty($password) && !empty($password_confirm)) {
        if ($password !== $password_confirm) {
            $error = "Die Passwörter stimmen nicht überein.";
        } else {
            // Einladungscode prüfen
            $stmt = $pdo->prepare("SELECT id, used FROM invites WHERE token = ?");
            $stmt->execute([$invite_code]);
            $invite = $stmt->fetch();

            if (!$invite || $invite['used'] == 1) {
                $error = "Dieser Einladungscode ist ungültig oder wurde bereits benutzt.";
            } else {
                // E-Mail prüfen
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Diese E-Mail-Adresse ist bereits registriert.";
                } else {
                    // Alles okay -> Account erstellen
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $verification_token = bin2hex(random_bytes(32)); // Sicherheitstoken für die Mail

                    try {
                        $pdo->beginTransaction();

                        // User anlegen (noch nicht zugelassen)
                        $insertStmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, is_verified, verification_token) VALUES (?, ?, 'admin', 0, ?)");
                        $insertStmt->execute([$email, $hashed_password, $verification_token]);

                        // Einladung auf benutzt setzen
                        $updateInvite = $pdo->prepare("UPDATE invites SET used = 1 WHERE id = ?");
                        $updateInvite->execute([$invite['id']]);

                        $pdo->commit();

                        // Verifizierungs-E-Mail senden
                        $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . "/admin/verify.php?token=" . $verification_token;
                        
                        $mailSubject = "Aktiviere dein RuDaTrans Admin-Konto";
                        $mailBody = "
                            <h2>Willkommen beim RuDaTrans Mod-Portal!</h2>
                            <p>Dein Admin-Konto wurde erfolgreich erstellt. Um dich einzuloggen, musst du zuerst deine E-Mail-Adresse bestätigen.</p>
                            <p><a href='$verifyLink' style='padding: 10px 20px; background-color: #ffcc00; color: #000; text-decoration: none; font-weight: bold;'>Konto jetzt aktivieren</a></p>
                            <p>Oder klicke auf diesen Link: <br>$verifyLink</p>
                        ";

                        if (sendMail($email, $mailSubject, $mailBody)) {
                            $success = "Erfolg! Wir haben dir eine E-Mail gesendet. Bitte klicke auf den Link in der E-Mail, um dein Konto zu aktivieren.";
                        } else {
                            $error = "Account erstellt, aber die E-Mail konnte nicht gesendet werden! Überprüfe die mail_config.php Einstellungen.";
                        }

                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Datenbankfehler bei der Erstellung des Kontos.";
                    }
                }
            }
        }
    } else {
        $error = "Bitte fülle alle Felder aus.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrieren - RuDaTrans Backend</title>
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
        <h2>Administrator werden</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="links"><a href="login.php">Zum Login</a></div>
        <?php else: ?>
            <form method="POST" action="">
                <input type="text" name="invite_code" placeholder="Einladungscode (Token)" required>
                <input type="email" name="email" placeholder="E-Mail Adresse" required>
                <input type="password" name="password" placeholder="Passwort" required minlength="6">
                <input type="password" name="password_confirm" placeholder="Passwort bestätigen" required minlength="6">
                <button type="submit">Konto erstellen</button>
            </form>

            <div class="links">
                Bereits ein Konto? <a href="login.php">Hier einloggen</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
