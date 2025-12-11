<?php
/**
 * Procesare Formular Contact - Trimitere Email
 * Trimite email către contact@brodero.online cu datele din formular
 */

/**
 * Trimite email de contact
 * 
 * @param string $name Numele expeditorului
 * @param string $email Emailul expeditorului
 * @param string $subject Subiectul mesajului
 * @param string $message Conținutul mesajului
 * @param array $attachments Array cu numele fișierelor atașate
 * @return bool True dacă emailul a fost trimis, false în caz contrar
 */
function sendContactEmail($name, $email, $subject, $message, $attachments = []) {
    // Configurare email destinatar
    $to = SITE_EMAIL; // contact@brodero.online
    $from = 'no-reply@brodero.online';
    $replyTo = $email;
    
    // Sanitizare date
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Informații suplimentare
    $userIP = $_SERVER['REMOTE_ADDR'] ?? 'Necunoscut';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Necunoscut';
    $timestamp = date('d.m.Y H:i:s');
    
    // Verificare dacă există atașamente
    $hasAttachments = !empty($attachments);
    $boundary = md5(time());
    
    // Construire subiect email
    $emailSubject = "[Brodero Contact] " . $subject;
    
    // Headers pentru email
    $headers = [];
    $headers[] = "From: Brodero <$from>";
    $headers[] = "Reply-To: $name <$replyTo>";
    $headers[] = "MIME-Version: 1.0";
    
    if ($hasAttachments) {
        $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
    } else {
        $headers[] = "Content-Type: text/html; charset=UTF-8";
    }
    
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "X-Priority: 3";
    
    // Construire corp email
    if ($hasAttachments) {
        // Email cu atașamente
        $emailBody = "--$boundary\r\n";
        $emailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $emailBody .= buildEmailHTML($name, $email, $subject, $message, $userIP, $userAgent, $timestamp, $attachments);
        $emailBody .= "\r\n\r\n";
        
        // Adăugare atașamente
        foreach ($attachments as $fileName) {
            $filePath = UPLOAD_PATH . 'contact/' . $fileName;
            
            if (file_exists($filePath)) {
                $fileContent = chunk_split(base64_encode(file_get_contents($filePath)));
                $fileType = mime_content_type($filePath);
                
                $emailBody .= "--$boundary\r\n";
                $emailBody .= "Content-Type: $fileType; name=\"$fileName\"\r\n";
                $emailBody .= "Content-Transfer-Encoding: base64\r\n";
                $emailBody .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
                $emailBody .= $fileContent;
                $emailBody .= "\r\n";
            }
        }
        
        $emailBody .= "--$boundary--";
    } else {
        // Email simplu fără atașamente
        $emailBody = buildEmailHTML($name, $email, $subject, $message, $userIP, $userAgent, $timestamp, []);
    }
    
    // Trimitere email
    $result = @mail($to, $emailSubject, $emailBody, implode("\r\n", $headers));
    
    // Logging pentru debugging (opțional)
    if (!$result) {
        error_log("Eroare trimitere email contact de la $email (subiect: $subject)");
    }
    
    return $result;
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
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">📧 Mesaj Nou de Contact</h1>
                            <p style="margin: 10px 0 0 0; color: #f0f0f0; font-size: 14px;">Brodero - Design de Broderie</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
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
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px; text-align: center; background-color: #f9f9f9; border-radius: 0 0 8px 8px; border-top: 1px solid #eee;">
                            <p style="margin: 0 0 10px 0; color: #999; font-size: 12px;">Pentru a răspunde, click pe adresa de email sau folosește butonul Reply.</p>
                            <a href="mailto:' . $email . '?subject=Re: ' . urlencode($subject) . '" style="display: inline-block; padding: 10px 30px; background-color: #667eea; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 10px;">Răspunde Acum</a>
                            <p style="margin: 15px 0 0 0; color: #999; font-size: 11px;">User Agent: ' . htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8') . '</p>
                        </td>
                    </tr>
                </table>
                
                <!-- Bottom text -->
                <table width="600" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; color: #999; font-size: 12px;">
                            <p style="margin: 0;">Acest email a fost generat automat de formularul de contact Brodero.</p>
                            <p style="margin: 5px 0 0 0;"><a href="' . SITE_URL . '" style="color: #667eea; text-decoration: none;">brodero.online</a></p>
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
