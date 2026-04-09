<?php
require_once 'header.php';

$stmt = $pdo->query("SELECT id, title, version, status, version_date FROM mods ORDER BY id DESC");
$mods = $stmt->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Meine Mods</h2>
        <a href="mod_edit.php" class="btn btn-success">+ Neuen Mod anlegen</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Titel</th>
                <th>Version</th>
                <th>Datum</th>
                <th>Status</th>
                <th>Bilder</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mods as $mod): ?>
            <tr>
                <td><?php echo $mod['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($mod['title']); ?></strong></td>
                <td><?php echo htmlspecialchars($mod['version']); ?></td>
                <td><?php echo htmlspecialchars($mod['version_date']); ?></td>
                <td>
                    <?php if ($mod['status'] === 'NEW'): ?>
                        <span style="color: green; font-weight: bold;">NEU</span>
                    <?php elseif ($mod['status'] === 'UPDATE'): ?>
                        <span style="color: orange; font-weight: bold;">UPDATE</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <a href="fotos.php?id=<?php echo $mod['id']; ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.85rem;">📸 Verwalten</a>
                </td>
                <td>
                    <a href="mod_edit.php?id=<?php echo $mod['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.85rem;">✏️ Bearbeiten</a>
                    <a href="mod_delete.php?id=<?php echo $mod['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;" onclick="return confirm('Möchtest du diesen Mod wirklich löschen? Dies löscht auch alle Bilder aus der Datenbank.');">❌ Löschen</a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($mods)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">Noch keine Mods vorhanden. Lege deinen ersten an!</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>
