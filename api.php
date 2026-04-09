<?php
/**
 * api.php
 * Diese Datei stellt die Verbindung zur MySQL-Datenbank her und liefert
 * die Mod-Daten als JSON an das Frontend (download.html/script.js) aus.
 */

// Fehlerberichterstattung für Entwicklungsumgebung aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gebe an, dass wir JSON zurücksenden
header('Content-Type: application/json; charset=utf-8');

// Datenbank-Zugangsdaten (Standard für XAMPP)
require_once 'db_config.php';

try {
    $stmt = $pdo->query("SELECT * FROM mods ORDER BY id ASC");
    $mods = $stmt->fetchAll();

    // 3. Für jeden Mod die entsprechenden Bilder abrufen
    foreach ($mods as $key => $mod) {
        $modId = $mod['id'];
        
        // SQL-Query um alle Bilder für die aktuelle $modId zu holen sortiert nach sort_order
        $imgStmt = $pdo->prepare("SELECT image_url, badge FROM mod_images WHERE mod_id = ? ORDER BY sort_order ASC, id ASC");
        $imgStmt->execute([$modId]);
        
        // Bilder-Daten als assoziatives Array holen
        $imagesData = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Wenn keine Bilder gefunden wurden, fügen wir ein Platzhalter-Bild hinzu
        if (empty($imagesData)) {
            $imagesData = [
                ['image_url' => 'https://via.placeholder.com/800x600?text=Kein+Bild+vorhanden', 'badge' => '']
            ];
        }

        // Bilder dem Array-Element hinzufügen
        $mods[$key]['images'] = $imagesData;
    }

    // 4. Das fertige Array als JSON kodieren und ausgeben
    echo json_encode(['status' => 'success', 'data' => $mods]);

} catch (\PDOException $e) {
    // Falls ein Fehler auftritt (z.B. falsche Zugangsdaten oder Datenbank existiert nicht)
    // Senden wir eine saubere Fehlermeldung als JSON zurück
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error', 
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>
