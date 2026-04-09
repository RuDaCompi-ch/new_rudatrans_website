<?php
// Lade die PHPMailer-Klassen (Manuell ohne Composer, da nicht installiert)
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sendet eine E-Mail über einen echten SMTP-Server.
 * @param string $to Empfänger-E-Mail-Adresse
 * @param string $subject Betreff der E-Mail
 * @param string $htmlBody HTML-Inhalt der E-Mail
 * @return bool True bei Erfolg, False bei Fehler
 */
function sendMail($to, $subject, $htmlBody) {
    $mail = new PHPMailer(true);
    try {
        // ========================================================
        // WICHTIG: TRAGE HIER DEINE ECHTEN SMTP ZUGANGSDATEN EIN!
        // ========================================================
        $mail->isSMTP();
        $mail->Host       = 'mail.deinserver.com'; // z.B. smtp.strato.de oder smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@rudatrans.ch'; // Deine SMTP E-Mail Adresse
        $mail->Password   = 'DEIN_ECHTES_PASSWORT'; // Dein SMTP Passwort
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Oder ENCRYPTION_SMTPS für SSL
        $mail->Port       = 587; // Meistens 587 (TLS) oder 465 (SSL)
        
        $mail->CharSet    = 'UTF-8';

        // Absender und Empfänger
        $mail->setFrom('noreply@rudatrans.ch', 'RuDaTrans Mod-Portal'); // Absender der angezeigt wird
        $mail->addAddress($to);

        // Mail-Inhalt verpacken
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        // Alternative Plain-Text Version (für ältere Mail-Clients)
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("E-Mail konnte nicht gesendet werden. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
