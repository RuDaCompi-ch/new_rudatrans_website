<?php
require_once __DIR__ . '/../db_config.php';

$message = '';
$isError = true;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_verified'] == 1) {
            $message = "Dein Konto ist bereits verifiziert!";
            $isError = false;
        } else {
            // Konto verifizieren
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            if ($update->execute([$user['id']])) {
                $message = "Erfolg! Dein Konto wurde erfolgreich verifiziert. Du kannst dich jetzt einloggen.";
                $isError = false;
            } else {
                $message = "Fehler beim Aktualisieren des Kontos.";
            }
        }
    } else {
        $message = "Ungültiger oder abgelaufener Verifizierungslink.";
    }
} else {
    $message = "Kein Verifizierungscode angegeben.";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail Verifizierung - RuDaTrans Backend</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--bg-dark); }
        .auth-container { background-color: var(--box-white); padding: 40px; border-radius: var(--radius-large); box-shadow: 0 4px 15px rgba(0,0,0,0.5); width: 100%; max-width: 400px; text-align: center; }
        .auth-container h2 { color: var(--card-red); margin-bottom: 20px; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .error { background-color: #f88; color: #fff; }
        .success { background-color: #8f8; color: #000; }
        .auth-container a { display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: var(--btn-yellow); color: #000; text-decoration: none; border-radius: var(--radius-small); font-weight: bold; }
        .auth-container a:hover { background-color: #e6b800; }
    </style>
</head>
<body>

    <div class="auth-container">
        <h2>E-Mail Verifizierung</h2>
        
        <div class="message <?php echo $isError ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <?php if (!$isError): ?>
            <a href="login.php">Jetzt Einloggen</a>
        <?php endif; ?>
    </div>

</body>
</html>
