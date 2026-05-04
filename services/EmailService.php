<?php
declare(strict_types=1);

/**
 * EmailService — Centralized email dispatch using PHPMailer.
 *
 * If PHPMailer is not installed, emails are logged to /logs/email_log.txt
 * so the application never crashes on missing dependency.
 *
 * To install PHPMailer: composer require phpmailer/phpmailer
 * Then update PHPMAILER_PATH below or use the Composer autoloader.
 */
class EmailService
{
    // ─── SMTP Configuration (adjust for your environment) ───
    private const SMTP_HOST     = 'smtp.gmail.com';
    private const SMTP_PORT     = 587;
    private const SMTP_USER     = 'medchain.noreply@gmail.com';
    private const SMTP_PASS     = '';         // App-specific password
    private const SMTP_FROM     = 'medchain.noreply@gmail.com';
    private const SMTP_FROM_NAME = 'MedChain';
    private const SMTP_ENCRYPTION = 'tls';

    // Path to PHPMailer (if not using Composer autoload)
    private const PHPMAILER_PATH = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';

    /**
     * Send an email. Falls back to file logging if PHPMailer unavailable.
     *
     * @param string $to      Recipient email
     * @param string $subject Email subject
     * @param string $body    HTML body
     * @return array{success: bool, message: string}
     */
    public static function send(string $to, string $subject, string $body): array
    {
        // Try PHPMailer first
        if (self::phpMailerAvailable()) {
            return self::sendWithPhpMailer($to, $subject, $body);
        }

        // Fallback: log to file
        return self::logToFile($to, $subject, $body);
    }

    // ─── Notification Templates ─────────────────────────────

    /**
     * Notify patient that their loan was APPROVED.
     */
    public static function notifyLoanApproved(string $email, string $patientName, string $objectName, string $datePret): array
    {
        $subject = '✅ Votre demande de prêt a été approuvée — MedChain';
        $body = self::wrapInTemplate("
            <h2 style='color:#1D9E75;'>Prêt approuvé !</h2>
            <p>Bonjour <strong>" . htmlspecialchars($patientName) . "</strong>,</p>
            <p>Nous avons le plaisir de vous informer que votre demande de prêt a été approuvée :</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                <tr><td style='padding:8px;border:1px solid #e5e7eb;font-weight:600;'>Objet</td><td style='padding:8px;border:1px solid #e5e7eb;'>" . htmlspecialchars($objectName) . "</td></tr>
                <tr><td style='padding:8px;border:1px solid #e5e7eb;font-weight:600;'>Date de prêt</td><td style='padding:8px;border:1px solid #e5e7eb;'>" . htmlspecialchars($datePret) . "</td></tr>
            </table>
            <p>Vous pouvez récupérer l'objet auprès du service Loisirs.</p>
        ");

        return self::send($email, $subject, $body);
    }

    /**
     * Notify patient that their loan was REJECTED.
     */
    public static function notifyLoanRejected(string $email, string $patientName, string $objectName, string $motif): array
    {
        $subject = '❌ Votre demande de prêt a été refusée — MedChain';
        $body = self::wrapInTemplate("
            <h2 style='color:#EF4444;'>Demande refusée</h2>
            <p>Bonjour <strong>" . htmlspecialchars($patientName) . "</strong>,</p>
            <p>Nous sommes désolés, votre demande de prêt pour <strong>" . htmlspecialchars($objectName) . "</strong> a été refusée.</p>
            " . ($motif !== '' ? "<p><strong>Motif :</strong> " . htmlspecialchars($motif) . "</p>" : "") . "
            <p>Vous pouvez effectuer une nouvelle demande depuis votre espace patient.</p>
        ");

        return self::send($email, $subject, $body);
    }

    /**
     * Notify patient that their loan is OVERDUE.
     */
    public static function notifyLoanOverdue(string $email, string $patientName, string $objectName, string $dateRetourPrevue): array
    {
        $subject = '⚠️ Retard de retour — MedChain';
        $body = self::wrapInTemplate("
            <h2 style='color:#F59E0B;'>Rappel : retour en retard</h2>
            <p>Bonjour <strong>" . htmlspecialchars($patientName) . "</strong>,</p>
            <p>L'objet <strong>" . htmlspecialchars($objectName) . "</strong> devait être retourné le <strong>" . htmlspecialchars($dateRetourPrevue) . "</strong>.</p>
            <p>Merci de le rapporter dès que possible au service Loisirs.</p>
        ");

        return self::send($email, $subject, $body);
    }

    // ─── Private Helpers ────────────────────────────────────

    private static function wrapInTemplate(string $content): string
    {
        return "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:linear-gradient(135deg,#1D9E75,#0F6E56);padding:24px;text-align:center;border-radius:12px 12px 0 0;'>
                <h1 style='color:#fff;margin:0;font-size:24px;'>Med<span style=\"color:#A7F3D0;\">Chain</span></h1>
            </div>
            <div style='background:#fff;padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;'>
                {$content}
                <hr style='border:none;border-top:1px solid #e5e7eb;margin:24px 0;'>
                <p style='font-size:12px;color:#6B7280;'>Cet email a été envoyé automatiquement par MedChain. Ne répondez pas à ce message.</p>
            </div>
        </div>";
    }

    private static function phpMailerAvailable(): bool
    {
        // Check Composer autoload first
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }

        // Check manual path
        $file = self::PHPMAILER_PATH . 'PHPMailer.php';
        if (file_exists($file)) {
            require_once $file;
            require_once self::PHPMAILER_PATH . 'SMTP.php';
            require_once self::PHPMAILER_PATH . 'Exception.php';
            return true;
        }

        return false;
    }

    private static function sendWithPhpMailer(string $to, string $subject, string $body): array
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = self::SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::SMTP_USER;
            $mail->Password   = self::SMTP_PASS;
            $mail->SMTPSecure = self::SMTP_ENCRYPTION;
            $mail->Port       = self::SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(self::SMTP_FROM, self::SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return ['success' => true, 'message' => 'Email envoyé.'];
        } catch (\Exception $e) {
            self::logToFile($to, $subject, $body, 'PHPMAILER_ERROR: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur d\'envoi: ' . $e->getMessage()];
        }
    }

    /**
     * Fallback: log the email to a file for dev environments.
     */
    private static function logToFile(string $to, string $subject, string $body, string $extra = ''): array
    {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = str_repeat('=', 60) . "\n"
            . "Date:    " . date('Y-m-d H:i:s') . "\n"
            . "To:      " . $to . "\n"
            . "Subject: " . $subject . "\n"
            . ($extra !== '' ? "Note:    " . $extra . "\n" : '')
            . "Body:\n" . strip_tags($body) . "\n\n";

        @file_put_contents($logDir . '/email_log.txt', $entry, FILE_APPEND | LOCK_EX);

        return ['success' => true, 'message' => 'Email enregistré dans les logs (PHPMailer non installé).'];
    }
}
