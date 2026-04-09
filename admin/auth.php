<?php
session_start();

// Datenbank einbinden für alle geschützten Admin-Seiten
require_once __DIR__ . '/../db_config.php';

// Prüfen ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Benutzerdaten für den schnellen Zugriff bereitstellen
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'admin';
$user_email = $_SESSION['user_email'] ?? '';

// Helper-Funktion um Owner-Berechtigungen (z.B. für Invites) zu prüfen
function isOwner() {
    global $user_role;
    return $user_role === 'owner';
}
?>
