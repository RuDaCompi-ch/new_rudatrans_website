<?php
session_start();
require_once __DIR__ . '/../db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Suche User
        $stmt = $pdo->prepare("SELECT id, password_hash, role, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_verified'] == 1) {
                // Login erfolgreich
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $email;
                header("Location: index.php");
                exit();
            } else {
                $error = 'Dieses Konto wurde noch nicht per E-Mail verifiziert.';
            }
        } else {
            $error = 'Falsche E-Mail oder Passwort.';
        }
    } else {
        $error = 'Bitte fülle alle Felder aus.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RuDaTrans Backend</title>
    <link rel="stylesheet" href="../style.css"> <!-- Wir laden deine bestehende CSS -->
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: var(--bg-dark); /* Aus style.css übernommen */
        }
        .auth-container {
            background-color: var(--box-white);
            padding: 40px;
            border-radius: var(--radius-large);
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .auth-container h2 {
            color: var(--card-red);
            margin-bottom: 20px;
        }
        .auth-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: var(--radius-small);
            background-color: #eee;
            color: #333;
            box-sizing: border-box;
        }
        .auth-container button {
            width: 100%;
            padding: 10px;
            background-color: var(--btn-yellow);
            color: #000;
            border: none;
            border-radius: var(--radius-small);
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .auth-container button:hover {
            background-color: #e6b800;
            transform: translateY(-2px);
        }
        .auth-container .error {
            color: red;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .auth-container .links {
            margin-top: 15px;
            font-size: 0.85rem;
        }
        .auth-container .links a {
            color: #333;
            text-decoration: none;
        }
        .auth-container .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <h2>🛠️ RuDaTrans Backend</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="email" name="email" placeholder="E-Mail Adresse" required>
            <input type="password" name="password" placeholder="Passwort" required>
            <button type="submit">Einloggen</button>
        </form>

        <div class="links">
            <a href="forgot.php">Passwort vergessen?</a> | 
            <a href="register.php">Registrieren (Mit Einladungscode)</a>
        </div>
    </div>

</body>
</html>
