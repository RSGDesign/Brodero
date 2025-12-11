<?php
/**
 * Procesare Formular Contact - PHPMailer + SMTP Hostinger
 * Trimite email către contact@brodero.online
 * 
 * Features:
 * - PHPMailer cu SMTP Hostinger (NU mail())
 * - Logging complet în logs/mail.log
 * - Fallback: salvează în DB dacă SMTP eșuează
 * - Rate limiting anti-spam
 * - Suport atașamente
 * - Encoding UTF-8 corect
 */

// Include configurare SMTP
require_once __DIR__ . '/../../config/smtp_config.php';

// Include PHPMailer (via Composer)
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Log mesaj în fișierul de logging
 */
function logMail($message, $level = 'INFO') {
    if (!MAIL_LOG_ENABLED) return;
    
    $logDir = dirname(MAIL_LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $logMessage = "[$timestamp] [$level] [$ip] $message" . PHP_EOL;
    
    @file_put_contents(MAIL_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Verifică rate limiting (anti-spam)
 */
function checkRateLimit($email) {
    if (!CONTACT_RATE_LIMIT_ENABLED) {
        return ['allowed' => true];
    }
    
    $db = getDB();
    $now = time();
    $oneHourAgo = $now - 3600;
    $oneDayAgo = $now - 86400;
    
    // Verificare submissions ultima oră
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contact_messages 
                          WHERE email = ? AND created_at > FROM_UNIXTIME(?)");
    $stmt->bind_param("si", $email, $oneHourAgo);
    $stmt->execute();
    $hourCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    if ($hourCount >= CONTACT_MAX_SUBMISSIONS_PER_HOUR) {
        logMail("Rate limit exceeded for $email - $hourCount submissions in last hour", 'WARNING');
        return [
            'allowed' => false,
            'reason' => 'prea_multe_cereri_ora',
            'message' => 'Ai trimis prea multe mesaje recent. Te rugăm să aștepți.'
        ];
    }
    
    // Verificare submissions ultima zi
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM contact_messages 
                          WHERE email = ? AND created_at > FROM_UNIXTIME(?)");
    $stmt->bind_param("si", $email, $oneDayAgo);
    $stmt->execute();
    $dayCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    if ($dayCount >= CONTACT_MAX_SUBMISSIONS_PER_DAY) {
        logMail("Rate limit exceeded for $email - $dayCount submissions in last 24h", 'WARNING');
        return [
            'allowed' => false,
            'reason' => 'prea_multe_cereri_zi',
            'message' => 'Ai atins limita maximă de mesaje pentru astăzi.'
        ];
    }
    
    return ['allowed' => true, 'hourCount' => $hourCount, 'dayCount' => $dayCount];
}

/**
 * Salvează mesajul în baza de date (fallback sau backup)
 */
function saveToDatabase($name, $email, $subject, $message, $attachments, $emailSent = false) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contact_messages 
                             (name, email, subject, message, attachments, status, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        $attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;
        $status = $emailSent ? 'new' : 'pending_email';
        
        $stmt->bind_param("ssssss", $name, $email, $subject, $message, $attachmentsJson, $status);
        $result = $stmt->execute();
        $insertId = $db->insert_id;
        $stmt->close();
        
        if ($result) {
            logMail("Message saved to database (ID: $insertId, email_sent: " . ($emailSent ? 'yes' : 'no') . ")", 'INFO');
            return ['success' => true, 'id' => $insertId];
        }
        
        return ['success' => false, 'error' => $db->error];
    } catch (Exception $e) {
        logMail("Database save failed: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Trimite email folosind PHPMailer + SMTP Hostinger
 * 
 * @param string $name Numele expeditorului
 * @param string $email Emailul expeditorului
 * @param string $subject Subiectul mesajului
 * @param string $message Conținutul mesajului
 * @param array $attachments Array cu numele fișierelor atașate
 * @return array ['success' => bool, 'message' => string, 'method' => 'smtp'|'fallback']
 */
function sendContactEmail($name, $email, $subject, $message, $attachments = []) {
    // Verificare configurare SMTP
    $configCheck = checkSmtpConfig();
    if (!$configCheck['status']) {
        logMail("SMTP config errors: " . implode(', ', $configCheck['errors']), 'ERROR');
        return [
            'success' => false,
            'message' => 'Configurare SMTP incorectă. Contactează administratorul.',
            'errors' => $configCheck['errors']
        ];
    }
    
    // Verificare rate limiting
    $rateLimitCheck = checkRateLimit($email);
    if (!$rateLimitCheck['allowed']) {
        return [
            'success' => false,
            'message' => $rateLimitCheck['message'],
            'reason' => $rateLimitCheck['reason']
        ];
    }
    
    // Sanitizare date
    $name = trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'));
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $subject = trim(htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'));
    $message = trim(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    
    // Informații suplimentare
    $userIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $timestamp = date('d.m.Y H:i:s');
    
    logMail("Starting email send for: $email (Subject: $subject)", 'INFO');
    
    // Inițializare PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // ═══════════════════════════════════════════════════════
        // CONFIGURARE SERVER SMTP HOSTINGER
        // ═══════════════════════════════════════════════════════
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;              // smtp.hostinger.com
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;          // contact@brodero.online
        $mail->Password   = SMTP_PASSWORD;          // Parola emailului
        $mail->SMTPSecure = SMTP_SECURE;            // ssl sau tls
        $mail->Port       = SMTP_PORT;              // 465 (ssl) sau 587 (tls)
        $mail->Timeout    = SMTP_TIMEOUT;
        $mail->CharSet    = SMTP_CHARSET;           // UTF-8
        
        // Debug (doar pentru testare)
        $mail->SMTPDebug = SMTP_DEBUG;
        if (SMTP_DEBUG > 0) {
            $mail->Debugoutput = function($str, $level) {
                logMail("SMTP Debug [$level]: $str", 'DEBUG');
            };
        }
        
        // ═══════════════════════════════════════════════════════
        // CONFIGURARE DESTINATARI
        // ═══════════════════════════════════════════════════════
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress(SITE_EMAIL);              // contact@brodero.online
        $mail->addReplyTo($email, $name);
        
        // ═══════════════════════════════════════════════════════
        // CONFIGURARE CONȚINUT
        // ═══════════════════════════════════════════════════════
        $mail->isHTML(true);
        $mail->Subject = "[Brodero Contact] " . $subject;
        $mail->Body    = buildEmailHTML($name, $email, $subject, $message, $userIP, $userAgent, $timestamp, $attachments);
        $mail->AltBody = buildEmailPlainText($name, $email, $subject, $message, $userIP, $timestamp);
        
        // ═══════════════════════════════════════════════════════
        // ADĂUGARE ATAȘAMENTE
        // ═══════════════════════════════════════════════════════
        if (!empty($attachments)) {
            foreach ($attachments as $fileName) {
                $filePath = UPLOAD_PATH . 'contact/' . $fileName;
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath, $fileName);
                    logMail("Attachment added: $fileName", 'INFO');
                }
            }
        }
        
        // ═══════════════════════════════════════════════════════
        // TRIMITERE EMAIL
        // ═══════════════════════════════════════════════════════
        $sendResult = $mail->send();
        
        if ($sendResult) {
            logMail("Email sent successfully to " . SITE_EMAIL . " from $email", 'SUCCESS');
            
            // Salvează în DB și ca backup
            saveToDatabase($name, $email, $subject, $message, $attachments, true);
            
            return [
                'success' => true,
                'message' => 'Email trimis cu succes!',
                'method' => 'smtp'
            ];
        }
        
    } catch (Exception $e) {
        // Logare eroare SMTP
        logMail("SMTP Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage(), 'ERROR');
        
        // ═══════════════════════════════════════════════════════
        // FALLBACK: SALVEAZĂ ÎN DATABASE
        // ═══════════════════════════════════════════════════════
        if (SMTP_FALLBACK_TO_DB) {
            logMail("Attempting fallback: save to database", 'WARNING');
            
            $dbSave = saveToDatabase($name, $email, $subject, $message, $attachments, false);
            
            if ($dbSave['success']) {
                return [
                    'success' => true,
                    'message' => 'Mesajul a fost salvat. Vom răspunde în cel mai scurt timp.',
                    'method' => 'fallback',
                    'warning' => 'Email nu a putut fi trimis, dar mesajul este salvat.'
                ];
            }
        }
        
        // Totul a eșuat
        return [
            'success' => false,
            'message' => 'Eroare la trimiterea mesajului. Te rugăm să încerci din nou.',
            'error' => $mail->ErrorInfo,
            'exception' => $e->getMessage()
        ];
    }
}

/**
 * Construiește HTML-ul pentru corpul emailului
 */
function buildEmailHTML($name, $email, $subject, $message, $userIP, $userAgent, $timestamp, $attachments) {
    $attachmentsList = '';
    if (!empty($attachments)) {
        $attachmentsList = '<tr>
            <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: #333;">Atașamente:</td>
            <td style="padding: 10px; border-bottom: 1px solid #eee; color: #555;">' . count($attachments) . ' fișier(e): ' . implode(', ', $attachments) . '</td>
        </tr>';
    }
    
    $html = '<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesaj Contact Brodero</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">📧 Mesaj Nou de Contact</h1>
                            <p style="margin: 10px 0 0 0; color: #f0f0f0; font-size: 14px;">Brodero - Design de Broderie</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin: 0 0 20px 0; color: #666; font-size: 14px;">Ai primit un mesaj nou prin formularul de contact:</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #eee; border-radius: 4px;">
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: #333; width: 150px;">Nume:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #555;">' . $name . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: #333;">Email:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #555;"><a href="mailto:' . $email . '" style="color: #667eea; text-decoration: none;">' . $email . '</a></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: #333;">Subiect:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #555;">' . $subject . '</td>
                                </tr>
                                ' . $attachmentsList . '
                                <tr>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: #333;">Data:</td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #555;">' . $timestamp . '</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px; font-weight: bold; color: #333;">IP:</td>
                                    <td style="padding: 10px; color: #555;">' . $userIP . '</td>
                                </tr>
                            </table>
                            <div style="margin-top: 20px; padding: 20px; background-color: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0; font-weight: bold; color: #333;">Mesaj:</p>
                                <p style="margin: 0; color: #555; line-height: 1.6; white-space: pre-wrap;">' . nl2br($message) . '</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; text-align: center; background-color: #f9f9f9; border-radius: 0 0 8px 8px; border-top: 1px solid #eee;">
                            <p style="margin: 0 0 10px 0; color: #999; font-size: 12px;">Pentru a răspunde, click pe adresa de email sau folosește butonul Reply.</p>
                            <a href="mailto:' . $email . '?subject=Re: ' . urlencode($subject) . '" style="display: inline-block; padding: 10px 30px; background-color: #667eea; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 10px;">Răspunde Acum</a>
                            <p style="margin: 15px 0 0 0; color: #999; font-size: 11px;">User Agent: ' . htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8') . '</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    
    return $html;
}

/**
 * Construiește versiunea plain text a emailului (fallback)
 */
function buildEmailPlainText($name, $email, $subject, $message, $userIP, $timestamp) {
    $text = "MESAJ NOU DE CONTACT - Brodero\n";
    $text .= "================================\n\n";
    $text .= "Nume: $name\n";
    $text .= "Email: $email\n";
    $text .= "Subiect: $subject\n";
    $text .= "Data: $timestamp\n";
    $text .= "IP: $userIP\n\n";
    $text .= "MESAJ:\n";
    $text .= "--------\n";
    $text .= "$message\n\n";
    $text .= "================================\n";
    $text .= "Pentru a răspunde, trimite un email la: $email\n";
    
    return $text;
}
