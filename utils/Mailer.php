<?php



class Mailer
{
    // ── Gmail SMTP configuration ──────────────────────────────────────────────
    // SMTP_USER  = your full Gmail address  (e.g. yourname@gmail.com)
    // SMTP_PASS  = your Gmail App Password  (16-char, spaces are fine)
    //              Generate one at: https://myaccount.google.com/apppasswords
    //              (Requires 2-Step Verification to be enabled on your account)
    private const SMTP_HOST   = 'smtp.gmail.com';
    private const SMTP_PORT   = .;
    private const SMTP_SECURE = 'tls';
    private const SMTP_USER   = '.'; // ← replace with your Gmail
    private const SMTP_PASS   = 'uqey zknh etqe dpcr';          // ← your App Password
    private const FROM_EMAIL  = '.'; // ← must match SMTP_USER for Gmail
    private const FROM_NAME   = 'MedChain — Hôpital';
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Send loan confirmation email with embedded QR code.
     */
    public static function sendLoanConfirmation(
        string $toEmail,
        string $toName,
        int    $idPret,
        string $nomObjet,
        string $datePret,
        string $dateRetour
    ): bool {
        $qrData = urlencode(
            "LoanID:{$idPret}|Patient:{$toName}|Object:{$nomObjet}|Due:{$dateRetour}"
        );
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$qrData}";

        $subject = "✅ Votre prêt a été confirmé — MedChain";
        $body    = self::buildConfirmationHtml(
            $toName, $idPret, $nomObjet, $datePret, $dateRetour, $qrUrl
        );

        return self::send($toEmail, $toName, $subject, $body);
    }

    /**
     * Send overdue reminder email (loan due tomorrow).
     */
    public static function sendDueTomorrowReminder(
        string $toEmail,
        string $toName,
        string $nomObjet,
        string $dateRetour
    ): bool {
        $subject = "⏰ Rappel : votre prêt est dû demain — MedChain";
        $body    = self::buildReminderHtml($toName, $nomObjet, $dateRetour);

        return self::send($toEmail, $toName, $subject, $body);
    }

    // ── Core send dispatcher ──────────────────────────────────────────────────

    private static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        // Try Composer autoload first, then manual path
        $autoload = BASE_PATH . '/vendor/autoload.php';
        $manual   = BASE_PATH . '/vendor/phpmailer/src/PHPMailer.php';

        if (file_exists($autoload)) {
            require_once $autoload;
        } elseif (file_exists($manual)) {
            require_once $manual;
            require_once BASE_PATH . '/vendor/phpmailer/src/SMTP.php';
            require_once BASE_PATH . '/vendor/phpmailer/src/Exception.php';
        }

        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return self::sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody);
        }

        // Fallback: native mail()
        return self::sendViaMail($toEmail, $toName, $subject, $htmlBody);
    }

    private static function sendViaPHPMailer(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = self::SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::SMTP_USER;
            $mail->Password   = self::SMTP_PASS;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            // Fix SSL certificate issues on localhost (XAMPP)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $htmlBody));

            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('[Mailer] PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendViaMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . self::FROM_NAME . ' <' . self::FROM_EMAIL . ">\r\n";

        $result = @mail($toEmail, $subject, $htmlBody, $headers);
        if (!$result) {
            error_log('[Mailer] mail() failed for: ' . $toEmail);
        }
        return (bool) $result;
    }

    // ── HTML email templates ──────────────────────────────────────────────────

    private static function buildConfirmationHtml(
        string $toName,
        int    $idPret,
        string $nomObjet,
        string $datePret,
        string $dateRetour,
        string $qrUrl
    ): string {
        $dp = date('d/m/Y', strtotime($datePret));
        $dr = date('d/m/Y', strtotime($dateRetour));

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f9f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0"
           style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

      <tr><td style="background:linear-gradient(135deg,#1D9E75,#0F6E56);padding:32px 40px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:24px;">✅ Prêt Confirmé</h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,.85);font-size:14px;">MedChain — Objets Loisir</p>
      </td></tr>

      <tr><td style="padding:36px 40px;">
        <p style="font-size:16px;color:#1E3A52;margin:0 0 16px;">Bonjour <strong>{$toName}</strong>,</p>
        <p style="font-size:14px;color:#374151;line-height:1.7;margin:0 0 24px;">
          Votre demande de prêt a été <strong style="color:#1D9E75;">acceptée et confirmée</strong>.
          Vous pouvez récupérer l'objet auprès du service concerné.
        </p>

        <table width="100%" cellpadding="8" cellspacing="0"
               style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:28px;">
          <tr>
            <td style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;width:130px;">N° de prêt</td>
            <td style="font-size:15px;font-weight:700;color:#1E3A52;">#$idPret</td>
          </tr>
          <tr>
            <td style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;">Objet</td>
            <td style="font-size:15px;font-weight:700;color:#1E3A52;">{$nomObjet}</td>
          </tr>
          <tr>
            <td style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;">Date de prêt</td>
            <td style="font-size:14px;color:#374151;">{$dp}</td>
          </tr>
          <tr>
            <td style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;">Retour prévu</td>
            <td style="font-size:14px;font-weight:700;color:#EF4444;">{$dr}</td>
          </tr>
        </table>

        <div style="text-align:center;margin-bottom:28px;">
          <p style="font-size:13px;color:#6B7280;margin-bottom:12px;">
            Présentez ce QR code lors de la récupération :
          </p>
          <img src="{$qrUrl}" alt="QR Code Prêt #{$idPret}"
               style="border:4px solid #E5E7EB;border-radius:12px;padding:8px;" />
        </div>

        <p style="font-size:13px;color:#6B7280;line-height:1.6;margin:0;">
          ⚠️ Pensez à retourner l'objet avant le <strong>{$dr}</strong>.
        </p>
      </td></tr>

      <tr><td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #E5E7EB;">
        <p style="margin:0;font-size:11px;color:#9CA3AF;">
          © MedChain · Message automatique — merci de ne pas répondre.
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private static function buildReminderHtml(
        string $toName,
        string $nomObjet,
        string $dateRetour
    ): string {
        $dr = date('d/m/Y', strtotime($dateRetour));

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f9f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0"
           style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

      <tr><td style="background:linear-gradient(135deg,#F59E0B,#D97706);padding:32px 40px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:24px;">⏰ Rappel de Retour</h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,.85);font-size:14px;">MedChain — Objets Loisir</p>
      </td></tr>

      <tr><td style="padding:36px 40px;">
        <p style="font-size:16px;color:#1E3A52;margin:0 0 16px;">Bonjour <strong>{$toName}</strong>,</p>
        <p style="font-size:14px;color:#374151;line-height:1.7;margin:0 0 24px;">
          Votre prêt de l'objet <strong style="color:#1E3A52;">{$nomObjet}</strong>
          arrive à échéance <strong style="color:#EF4444;">demain, le {$dr}</strong>.
        </p>

        <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;
                    padding:20px;margin-bottom:24px;text-align:center;">
          <p style="margin:0;font-size:18px;font-weight:700;color:#C2410C;">
            📅 Date de retour : {$dr}
          </p>
        </div>

        <p style="font-size:13px;color:#6B7280;line-height:1.6;margin:0;">
          Merci de retourner l'objet avant la date limite.<br>
          Si vous l'avez déjà retourné, ignorez ce message.
        </p>
      </td></tr>

      <tr><td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #E5E7EB;">
        <p style="margin:0;font-size:11px;color:#9CA3AF;">
          © MedChain · Message automatique — merci de ne pas répondre.
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
