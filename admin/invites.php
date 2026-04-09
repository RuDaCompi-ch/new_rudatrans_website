<?php
require_once 'header.php';

// Sicherheit: Nur Owner dürfen diese Seite sehen
if (!isOwner()) {
    echo "<div class='container'><div class='alert alert-danger'>Zugriff verweigert. Nur der Besitzer (Owner) kann neue Administratoren einladen.</div></div>";
    require_once 'footer.php';
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $token = bin2hex(random_bytes(16)); // 32 Zeichen langer Token
    $stmt = $pdo->prepare("INSERT INTO invites (token, created_by) VALUES (?, ?)");
    if ($stmt->execute([$token, $user_id])) {
        $message = "
            <div class='alert alert-success'>
                Neuer Einladungscode generiert: <br><br>
                <strong style='font-size: 1.5rem; letter-spacing: 2px;'>$token</strong><br><br>
                <small>Kopiere diesen Code und sende ihn an den neuen Admin. Er kann ihn bei der Registrierung eingeben.</small>
            </div>
        ";
    }
}

// Lade alle existierenden Invites
$stmt = $pdo->query("SELECT i.*, u.email as creator_email FROM invites i JOIN users u ON i.created_by = u.id ORDER BY i.id DESC");
$invites = $stmt->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>🎟️ Einladungs-Management</h2>
        <a href="index.php" class="btn">Zurück zur Übersicht</a>
    </div>
    
    <?php echo $message; ?>

    <div style="margin-bottom: 20px; background: #eee; padding: 20px; border-radius: 5px;">
        <p>Um einen neuen Administrator hinzuzufügen, musst du für ihn einen sicheren Einladungscode generieren.</p>
        <form method="POST">
            <button type="submit" name="generate" class="btn btn-success" style="font-size: 1.1rem; padding: 10px 20px;">+ Neuen Code generieren</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code (Token)</th>
                <th>Status</th>
                <th>Vergeben am</th>
                <th>Vergeben von</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($invites as $inv): ?>
            <tr>
                <td style="font-family: monospace; font-size: 1.1rem;"><?php echo htmlspecialchars($inv['token']); ?></td>
                <td>
                    <?php if ($inv['used'] == 1): ?>
                        <span style="color: red; font-weight: bold;">Benutzt</span>
                    <?php else: ?>
                        <span style="color: green; font-weight: bold;">Verfügbar</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $inv['created_at']; ?></td>
                <td><?php echo htmlspecialchars($inv['creator_email']); ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($invites)): ?>
            <tr>
                <td colspan="4" style="text-align: center;">Es wurden noch keine Einladungen generiert.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>
