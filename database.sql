-- Erstelle die Datenbank, falls sie nicht existiert
CREATE DATABASE IF NOT EXISTS mod_downloads;
USE mod_downloads;

-- Lösche bestehende Tabellen, um Konflikte bei erneutem Import zu vermeiden
DROP TABLE IF EXISTS mod_images;
DROP TABLE IF EXISTS mods;

-- ==========================================
-- Tabelle: mods
-- Speichert alle Hauptinformationen zu einem Mod
-- ==========================================
CREATE TABLE mods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    version_date DATE NOT NULL,
    ets2_compat VARCHAR(50) NOT NULL,
    mod_type VARCHAR(100) NOT NULL,
    mod_kind VARCHAR(100) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    note VARCHAR(255),
    description TEXT NOT NULL,
    download_url VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT '' -- Kann 'NEW', 'UPDATE' oder leer '' sein
);

-- ==========================================
-- Tabelle: mod_images
-- Wichtig für das Bilderkarussell. Verlinkt auf die mod_id
-- ==========================================
CREATE TABLE mod_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mod_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (mod_id) REFERENCES mods(id) ON DELETE CASCADE
);

-- ==========================================
-- BEISPIELDATEN EINFÜGEN
-- ==========================================
INSERT INTO mods (title, version, version_date, ets2_compat, mod_type, mod_kind, filename, note, description, download_url, status)
VALUES 
(
    'Ruda AI Traffic Pack', 
    '1.6.3', 
    '2026-04-01', 
    '1.58', 
    'Ai Traffic', 
    'Localmod', 
    'ruda_ai_traffic_v1.6.3.scs', 
    'Muss extrahiert werden', 
    '<h3>Über den Mod</h3><p>Dieser Mod fügt realistischen KI-Verkehr hinzu. Neue Fahrzeugmodelle, verbesserte Verhaltensmuster und vieles mehr!</p><ul><li>Bessere Wegfindung</li><li>Mehrere Fahrzeugklassen</li></ul>', 
    '#', 
    'UPDATE'
),
(
    'Realistic Environment', 
    '2.0.0', 
    '2026-04-05', 
    '1.58', 
    'Environment / Weather', 
    'Workshop', 
    'real_env_v2.scs', 
    'Kompatibel mit allen Maps', 
    '<h3>Neue Texturen</h3><p>Enthält extrem hochauflösende 4k Texturen für Straßen und Vegetation. Komplett neu überarbeitet für Version 1.58.</p>', 
    '#', 
    'NEW'
),
(
    'Simple Trailer Pack', 
    '1.1.2', 
    '2025-11-20', 
    '1.57', 
    'Trailers', 
    'Localmod', 
    'sim_trailer_pack_1.1.2.scs', 
    'Nur für Basis-Spiel', 
    '<p>Ein einfaches Trailer-Pack, welches ein paar alte, klassische Auflieger zurückbringt. Nichts Besonderes, aber gut für Nostalgiker.</p>', 
    '#', 
    ''
);

-- Bilder für Mod 1 (Ruda AI Traffic Pack) - 10 Bilder zum Testen
INSERT INTO mod_images (mod_id, image_url) VALUES 
(1, 'https://picsum.photos/id/1015/800/600'),
(1, 'https://picsum.photos/id/1016/800/600'),
(1, 'https://picsum.photos/id/1018/800/600'),
(1, 'https://picsum.photos/id/1019/800/600'),
(1, 'https://picsum.photos/id/1020/800/600'),
(1, 'https://picsum.photos/id/1021/800/600'),
(1, 'https://picsum.photos/id/1022/800/600'),
(1, 'https://picsum.photos/id/1023/800/600'),
(1, 'https://picsum.photos/id/1024/800/600'),
(1, 'https://picsum.photos/id/1025/800/600');

-- Bilder für Mod 2 (Realistic Environment)
INSERT INTO mod_images (mod_id, image_url) VALUES 
(2, 'https://picsum.photos/id/1043/800/600'),
(2, 'https://picsum.photos/id/1044/800/600'),
(2, 'https://picsum.photos/id/1045/800/600');

-- Bilder für Mod 3 (Simple Trailer Pack)
INSERT INTO mod_images (mod_id, image_url) VALUES 
(3, 'https://picsum.photos/id/1070/800/600'),
(3, 'https://picsum.photos/id/1071/800/600');
