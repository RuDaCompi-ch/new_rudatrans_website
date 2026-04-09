<?php
require_once 'auth.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // Hole alle verknüpften Bilder, um sie physisch von der Festplatte zu löschen
    $stmt = $pdo->prepare("SELECT image_url FROM mod_images WHERE mod_id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    
    foreach ($images as $img) {
        if (strpos($img['image_url'], 'http') === false) { // Keine Dummy-Placeholders löschen
            $path = __DIR__ . '/../' . ltrim($img['image_url'], '/');
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
    
    // Mod löschen (In der Datenbank ist ON DELETE CASCADE für die mod_images Tabelle eingestellt)
    $pdo->prepare("DELETE FROM mods WHERE id = ?")->execute([$id]);
}

header("Location: index.php");
exit();
?>
