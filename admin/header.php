<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RuDaTrans Backend Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="navbar">
        <div>
            <a href="index.php">🏠 Mods Übersicht</a>
            <?php if (isOwner()): ?>
                <a href="invites.php">🎟️ Einladungen (Owner)</a>
            <?php endif; ?>
        </div>
        <div>
            <span>Angemeldet als: <strong><?php echo htmlspecialchars($user_email); ?></strong></span>
            <a href="logout.php" style="margin-left: 20px; color: #ff6b6b;">Logout</a>
        </div>
    </div>
    <div class="container">
