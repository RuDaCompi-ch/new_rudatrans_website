<?php
require_once 'header.php';

$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Standardwerte
$mod = [
    'title' => '', 'version' => '', 'version_date' => date('Y-m-d'),
    'ets2_compat' => '', 'mod_type' => '', 'mod_kind' => '',
    'filename' => '', 'note' => '', 'description' => '',
    'download_url' => '', 'status' => ''
];

// Laden, falls ID existiert
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM mods WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) $mod = $existing;
    else $error = "Mod nicht gefunden.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $version = trim($_POST['version']);
    $version_date = trim($_POST['version_date']);
    $ets2_compat = trim($_POST['ets2_compat']);
    $mod_type = trim($_POST['mod_type']);
    $mod_kind = trim($_POST['mod_kind']);
    $filename = trim($_POST['filename']);
    $note = trim($_POST['note']);
    $_description = trim($_POST['description']);
    $download_url = trim($_POST['download_url']);
    $status = trim($_POST['status']);

    if(empty($title) || empty($version)) {
        $error = "Titel und Version sind Pflichtfelder.";
    } else {
        if ($id) {
            $sql = "UPDATE mods SET 
                title=?, version=?, version_date=?, ets2_compat=?, mod_type=?, 
                mod_kind=?, filename=?, note=?, description=?, download_url=?, status=? 
                WHERE id=?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$title, $version, $version_date, $ets2_compat, $mod_type, $mod_kind, $filename, $note, $_description, $download_url, $status, $id])) {
                $success = "Mod erfolgreich aktualisiert!";
                $mod = array_merge($mod, $_POST); // Update form variables
            } else {
                $error = "Fehler beim Aktualisieren.";
            }
        } else {
            $sql = "INSERT INTO mods 
                (title, version, version_date, ets2_compat, mod_type, mod_kind, filename, note, description, download_url, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$title, $version, $version_date, $ets2_compat, $mod_type, $mod_kind, $filename, $note, $_description, $download_url, $status])) {
                $id = $pdo->lastInsertId();
                $success = "Neuer Mod erfolgreich angelegt!";
                $mod = array_merge($mod, $_POST);
            } else {
                $error = "Fehler beim Anlegen.";
            }
        }
    }
}
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><?php echo $id ? '✏️ Mod Bearbeiten' : '➕ Neuen Mod anlegen'; ?></h2>
        <a href="index.php" class="btn">Zurück</a>
    </div>

    <?php if($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <form method="POST">
        <div style="display: flex; gap: 20px;">
            <div class="form-group" style="flex: 2;">
                <label>Titel</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($mod['title']); ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Status (Label)</label>
                <select name="status" class="form-control">
                    <option value="" <?php if($mod['status']=='') echo 'selected'; ?>>- Keiner -</option>
                    <option value="NEW" <?php if($mod['status']=='NEW') echo 'selected'; ?>>NEU</option>
                    <option value="UPDATE" <?php if($mod['status']=='UPDATE') echo 'selected'; ?>>UPDATE</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="form-group" style="flex: 1;">
                <label>Mod Version</label>
                <input type="text" name="version" class="form-control" value="<?php echo htmlspecialchars($mod['version']); ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Versions-Datum</label>
                <input type="date" name="version_date" class="form-control" value="<?php echo htmlspecialchars($mod['version_date']); ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>ETS2 Kompatibilität</label>
                <input type="text" name="ets2_compat" class="form-control" value="<?php echo htmlspecialchars($mod['ets2_compat']); ?>">
            </div>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="form-group" style="flex: 1;">
                <label>Mod-Kategorie/Typ</label>
                <input type="text" name="mod_type" class="form-control" value="<?php echo htmlspecialchars($mod['mod_type']); ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Mod-Art (z.B. Localmod/Workshop)</label>
                <input type="text" name="mod_kind" class="form-control" value="<?php echo htmlspecialchars($mod['mod_kind']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Dateiname</label>
            <input type="text" name="filename" class="form-control" value="<?php echo htmlspecialchars($mod['filename']); ?>">
        </div>

        <div class="form-group">
            <label>Download URL</label>
            <input type="text" name="download_url" class="form-control" value="<?php echo htmlspecialchars($mod['download_url']); ?>">
        </div>

        <div class="form-group">
            <label>Kurzer Hinweis</label>
            <input type="text" name="note" class="form-control" value="<?php echo htmlspecialchars($mod['note']); ?>">
        </div>

        <div class="form-group">
            <label>HTML-Beschreibung</label>
            <textarea name="description" class="form-control" rows="8"><?php echo htmlspecialchars($mod['description']); ?></textarea>
            <small style="color: #666;">Du kannst hier ganz normal HTML-Tags wie &lt;h3&gt;, &lt;p&gt; oder &lt;ul&gt; &lt;li&gt; verwenden.</small>
        </div>

        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
            <button type="submit" class="btn btn-success" style="width: 100%; font-size: 1.1rem; padding: 12px;">💾 Speichern</button>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
