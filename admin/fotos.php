<?php
require_once 'header.php';

$mod_id = $_GET['id'] ?? null;
if (!$mod_id) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT title FROM mods WHERE id = ?");
$stmt->execute([$mod_id]);
$mod = $stmt->fetch();
if (!$mod) { header("Location: index.php"); exit(); }

// ==========================================
// AJAX HANDLERS FÜR DRAG & DROP UND BADGES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_order' && isset($_POST['order'])) {
        $order = $_POST['order']; // Array von IDs (e.g. order[0]=id)
        foreach ($order as $index => $img_id) {
            $pdo->prepare("UPDATE mod_images SET sort_order = ? WHERE id = ? AND mod_id = ?")
                ->execute([$index, $img_id, $mod_id]);
        }
        echo json_encode(['status' => 'success']);
        exit();
    }
    
    if ($_POST['action'] === 'update_badge' && isset($_POST['img_id']) && isset($_POST['badge'])) {
        $badge = $_POST['badge']; // 'NEW', 'UPDATE', oder ''
        $img_id = (int)$_POST['img_id'];
        $pdo->prepare("UPDATE mod_images SET badge = ? WHERE id = ? AND mod_id = ?")
            ->execute([$badge, $img_id, $mod_id]);
        echo json_encode(['status' => 'success']);
        exit();
    }
}

// Delete logic
if (isset($_GET['delete'])) {
    $img_id = $_GET['delete'];
    $q = $pdo->prepare("SELECT image_url FROM mod_images WHERE id = ? AND mod_id = ?");
    $q->execute([$img_id, $mod_id]);
    $img = $q->fetch();
    
    if ($img) {
        if (strpos($img['image_url'], 'http') === false) {
            $path = __DIR__ . '/../' . ltrim($img['image_url'], '/');
            if (file_exists($path)) { unlink($path); }
        }
        $pdo->prepare("DELETE FROM mod_images WHERE id = ?")->execute([$img_id]);
    }
    header("Location: fotos.php?id=$mod_id");
    exit();
}

// Upload logic
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && !isset($_POST['action'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $valid_exts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $valid_exts)) {
            $filename = uniqid('mod_' . $mod_id . '_') . '.webp';
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $dest_path = $upload_dir . $filename;
            
            $imgResource = null;
            if ($ext === 'jpg' || $ext === 'jpeg') { $imgResource = @imagecreatefromjpeg($file['tmp_name']); }
            elseif ($ext === 'png') {
                $imgResource = @imagecreatefrompng($file['tmp_name']);
                imagepalettetotruecolor($imgResource);
                imagealphablending($imgResource, true);
                imagesavealpha($imgResource, true);
            } elseif ($ext === 'webp') { $imgResource = @imagecreatefromwebp($file['tmp_name']); }

            if ($imgResource) {
                imagewebp($imgResource, $dest_path, 80);
                imagedestroy($imgResource);
                
                // Neue Bilder immer ans Ende hängen (hohe Sort Order)
                $db_url = 'uploads/' . $filename;
                $pdo->prepare("INSERT INTO mod_images (mod_id, image_url, sort_order) VALUES (?, ?, 999)")->execute([$mod_id, $db_url]);
                $message = "<div class='alert alert-success'>Bild erfolgreich hochgeladen und konvertiert!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Fehler beim Verarbeiten.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Ungültiges Format. Erlaubt sind JPG, PNG und WEBP.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Upload-Fehler! (Code: {$file['error']})</div>";
    }
}

// Fetch images sortiert nach sort_order
$imgsQuery = $pdo->prepare("SELECT id, image_url, sort_order, badge FROM mod_images WHERE mod_id = ? ORDER BY sort_order ASC, id ASC");
$imgsQuery->execute([$mod_id]);
$images = $imgsQuery->fetchAll();
?>

<!-- Einbinden von SortableJS für Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>📸 Galerie: <?php echo htmlspecialchars($mod['title']); ?></h2>
        <a href="index.php" class="btn">Zurück zur Übersicht</a>
    </div>
    
    <?php echo $message; ?>

    <div style="margin-top: 20px; background: #eee; padding: 20px; border-radius: 5px;">
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
            <input type="file" name="file" accept="image/png, image/jpeg, image/webp" required class="form-control" style="max-width: 300px;">
            <button type="submit" class="btn btn-success">Bild Hochladen (wird .webp)</button>
        </form>
    </div>

    <!-- Info über Drag & Drop -->
    <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; margin-top: 20px;">
        ℹ️ <b>Tipp:</b> Ziehe die Bilder mit der Maus, um ihre Reihenfolge zu ändern. Das erste Bild ist das Hauptbild im Download-Bereich! Alles speichert sich automatisch.
    </div>

    <!-- Image Grid mit Sortable Funktionalität -->
    <div class="image-grid" id="sortable-grid" style="margin-top: 20px; gap: 20px;">
        <?php foreach ($images as $img): ?>
            <div class="image-card" data-id="<?php echo $img['id']; ?>" style="cursor: grab; position: relative;">
                <?php 
                    $imgSrc = (strpos($img['image_url'], 'http') === 0) ? $img['image_url'] : '../' . $img['image_url'];
                ?>
                
                <!-- Badge Select Form -->
                <div style="margin-bottom: 8px;">
                    <select class="form-control badge-selector" style="padding: 4px; font-size: 0.8rem;" data-id="<?php echo $img['id']; ?>">
                        <option value="" <?php if($img['badge']==='') echo 'selected'; ?>>- Kein Banner -</option>
                        <option value="NEW" <?php if($img['badge']==='NEW') echo 'selected'; ?>>🌟 NEW</option>
                        <option value="UPDATE" <?php if($img['badge']==='UPDATE') echo 'selected'; ?>>🔄 UPDATE</option>
                    </select>
                </div>
                
                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Mod Image" style="pointer-events: none;">
                <a href="fotos.php?id=<?php echo $mod_id; ?>&delete=<?php echo $img['id']; ?>" class="btn btn-danger" style="display: block; width: 100%; box-sizing: border-box; text-align: center; font-size: 0.8rem; padding: 5px;" onclick="return confirm('Möchtest du dieses Bild löschen?');">Löschen</a>
            </div>
        <?php endforeach; ?>
        
        <?php if(empty($images)): ?>
            <p style="width: 100%; color: #999;">Noch keine Bilder hochgeladen.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Initialisiere Drag & Drop
    const grid = document.getElementById('sortable-grid');
    if (grid && grid.children.length > 0) {
        new Sortable(grid, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                // Sammle die neue Reihenfolge anhand der IDs
                let order = [];
                grid.querySelectorAll('.image-card').forEach(function(el) {
                    order.push(el.getAttribute('data-id'));
                });

                // Sende AJAX Request
                let formData = new FormData();
                formData.append('action', 'update_order');
                order.forEach((id, index) => {
                    formData.append('order[' + index + ']', id);
                });

                fetch('fotos.php?id=<?php echo $mod_id; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Reihenfolge gespeichert!');
                });
            }
        });
    }

    // 2. Initialisiere Badge Select Änderungen
    document.querySelectorAll('.badge-selector').forEach(select => {
        select.addEventListener('change', function() {
            let imgId = this.getAttribute('data-id');
            let badgeVal = this.value;

            let formData = new FormData();
            formData.append('action', 'update_badge');
            formData.append('img_id', imgId);
            formData.append('badge', badgeVal);

            fetch('fotos.php?id=<?php echo $mod_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Kurzes visuelles Feedback (z.B. grüner Rand)
                this.style.border = "2px solid green";
                setTimeout(() => { this.style.border = "1px solid #ccc"; }, 1000);
            });
        });
    });
});
</script>

<style>
.sortable-ghost { opacity: 0.4; background-color: #f8dbdb; }
</style>

<?php require_once 'footer.php'; ?>
