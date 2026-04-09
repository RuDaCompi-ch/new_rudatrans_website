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
$host = '127.0.0.1'; // oder 'localhost'
$db   = 'mod_downloads';
$user = 'root';
$pass = ''; // Standardmäßig ist das Passwort in XAMPP leer
$charset = 'utf8mb4';

// Data Source Name (DSN) für PDO zusammenbauen
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Optionen für PDO (Datenbank-Bibliothek in PHP)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Wirf Exceptions bei Fehlern
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ergebnisse als assoziative Arrays zurückgeben
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Native Prepared Statements nutzen
];

try {
    // 1. Verbindung zur Datenbank herstellen
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 2. Alle Mods abfragen
    $stmt = $pdo->query("SELECT * FROM mods ORDER BY id ASC");
    $mods = $stmt->fetchAll();

    // 3. Für jeden Mod die entsprechenden Bilder abrufen
    foreach ($mods as $key => $mod) {
        $modId = $mod['id'];
        
        // SQL-Query um alle Bilder für die aktuelle $modId zu holen
        $imgStmt = $pdo->prepare("SELECT image_url FROM mod_images WHERE mod_id = ?");
        $imgStmt->execute([$modId]);
        
        // Bilder-URLs in ein flaches Array pressen
        $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Wenn keine Bilder gefunden wurden, fügen wir ein Platzhalter-Bild hinzu
        if (empty($images)) {
            $images = ['https://via.placeholder.com/800x600?text=Kein+Bild+vorhanden'];
        }

        // Bilder dem Array-Element hinzufügen
        $mods[$key]['images'] = $images;
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
