<?php
/**
 * Mailer.php
 * Chemin : projet/models/Mailer.php
 * 
 * Modèle responsable de l'envoi des emails via PHPMailer (SMTP Gmail).
 * Utilisé par les contrôleurs (PasswordController, etc.)
 */

require_once __DIR__ . '/../config/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    // =========================================================================
    //  ⚙️  CONFIGURATION — MODIFIEZ CES VALEURS
    // =========================================================================

    /** Adresse Gmail expéditeur */

    /** Nom affiché dans la boîte de réception */

    /**
     * Mot de passe d'application Gmail (16 caractères)
     * Générez-le sur : https://myaccount.google.com/apppasswords
     */

    /** Configuration SMTP Gmail */
    private ?PHPMailer $mailer = null;
    private ?string $fromAddress;
    private string $fromName;
    private ?string $password;
    private string $smtpHost;
    private int $smtpPort;

    public function __construct() {
        $this->fromAddress = Env::get('MAIL_FROM_ADDRESS');
        $this->fromName = Env::get('MAIL_FROM_NAME', 'MedChain');
        $this->password = Env::get('MAIL_PASSWORD');
        $this->smtpHost = Env::get('SMTP_HOST', 'smtp.gmail.com');
        $this->smtpPort = (int) Env::get('SMTP_PORT', '587');
    }

    /**
     * Configure PHPMailer avec les paramètres SMTP Gmail
     */
    private function configure(): void {
        if ($this->mailer !== null) {
            return;
        }

        if (!$this->fromAddress || !$this->password) {
            throw new RuntimeException('SMTP is not configured. Set MAIL_FROM_ADDRESS and MAIL_PASSWORD in .env.');
        }

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $this->smtpHost;
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $this->fromAddress;
        $this->mailer->Password   = $this->password;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port       = $this->smtpPort;
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->setFrom($this->fromAddress, $this->fromName);
    }

    // =========================================================================
    //  MÉTHODES PUBLIQUES
    // =========================================================================

    /**
     * Envoie un email de réinitialisation de mot de passe
     *
     * @param string $toEmail     Email du destinataire
     * @param string $toName      Nom complet du destinataire
     * @param string $resetLink   Lien de réinitialisation
     * @return bool
     */
    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): bool {
        try {
            $this->configure();
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Réinitialisation de votre mot de passe MedChain';
            $this->mailer->Body    = $this->templatePasswordReset($toName, $resetLink);
            $this->mailer->AltBody = $this->templatePasswordResetText($toName, $resetLink);

            $this->mailer->send();
            error_log("[Mailer] Email reset envoyé à : $toEmail");
            return true;

        } catch (Throwable $e) {
            $errorInfo = $this->mailer ? $this->mailer->ErrorInfo : $e->getMessage();
            error_log("[Mailer] Erreur sendPasswordReset : " . $errorInfo);
            return false;
        }
    }

    /**
     * Envoie un email de bienvenue après inscription
     *
     * @param string $toEmail
     * @param string $toName
     * @return bool
     */
    public function sendWelcome(string $toEmail, string $toName): bool {
        try {
            $this->configure();
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Bienvenue sur MedChain !';
            $this->mailer->Body    = $this->templateWelcome($toName);
            $this->mailer->AltBody = "Bonjour $toName,\n\nBienvenue sur MedChain ! Votre compte a été créé avec succès.\n\nCordialement,\nL'équipe MedChain";

            $this->mailer->send();
            error_log("[Mailer] Email bienvenue envoyé à : $toEmail");
            return true;

        } catch (Throwable $e) {
            $errorInfo = $this->mailer ? $this->mailer->ErrorInfo : $e->getMessage();
            error_log("[Mailer] Erreur sendWelcome : " . $errorInfo);
            return false;
        }
    }

    // =========================================================================
    //  TEMPLATES HTML
    // =========================================================================

    private function templatePasswordReset(string $prenom, string $resetLink): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f0faf6;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0faf6;padding:40px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1D9E75,#0F6E56);padding:32px;text-align:center;">
            <span style="font-size:28px;font-weight:800;color:#fff;letter-spacing:-0.5px;">
              Med<span style="color:#a7f3d0;">Chain</span>
            </span>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">

            <div style="text-align:center;margin-bottom:28px;">
              <div style="width:64px;height:64px;background:#E8F7F2;border-radius:50%;
                          display:inline-block;line-height:64px;font-size:28px;margin-bottom:16px;">🔐</div>
              <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1E3A52;">
                Réinitialisation du mot de passe
              </h1>
              <p style="margin:0;color:#6B7280;font-size:14px;">
                Bonjour <strong style="color:#1E3A52;">{$prenom}</strong>,
              </p>
            </div>

            <p style="color:#374151;font-size:15px;line-height:1.7;margin-bottom:20px;">
              Nous avons reçu une demande de réinitialisation du mot de passe de votre compte MedChain.
              Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.
            </p>

            <!-- Bouton -->
            <div style="text-align:center;margin:32px 0;">
              <a href="{$resetLink}"
                 style="display:inline-block;background:linear-gradient(135deg,#1D9E75,#0F6E56);
                        color:#fff;text-decoration:none;font-size:16px;font-weight:600;
                        padding:14px 36px;border-radius:10px;
                        box-shadow:0 4px 14px rgba(29,158,117,.35);">
                🔑 Réinitialiser mon mot de passe
              </a>
            </div>

            <!-- Avertissement -->
            <div style="background:#FFF7ED;border-left:4px solid #F97316;
                        border-radius:8px;padding:14px 18px;margin-bottom:24px;">
              <p style="margin:0;color:#92400E;font-size:13px;line-height:1.6;">
                ⚠️ Ce lien est valable pendant <strong>1 heure</strong>.
                Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
              </p>
            </div>

            <!-- Lien texte -->
            <p style="color:#6B7280;font-size:12px;line-height:1.6;">
              Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
              <a href="{$resetLink}" style="color:#1D9E75;word-break:break-all;">{$resetLink}</a>
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#F9FAFB;border-top:1px solid #E5E7EB;
                     padding:20px 40px;text-align:center;">
            <p style="margin:0;color:#9CA3AF;font-size:12px;">
              © 2025 MedChain — Ne pas répondre à cet email.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private function templatePasswordResetText(string $prenom, string $resetLink): string {
        return <<<TEXT
Bonjour {$prenom},

Vous avez demandé la réinitialisation de votre mot de passe MedChain.

Cliquez sur le lien suivant (valable 1 heure) :
{$resetLink}

Si vous n'avez pas effectué cette demande, ignorez cet email.

Cordialement,
L'équipe MedChain
TEXT;
    }

    private function templateWelcome(string $prenom): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0faf6;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0faf6;padding:40px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

        <tr>
          <td style="background:linear-gradient(135deg,#1D9E75,#0F6E56);padding:32px;text-align:center;">
            <span style="font-size:28px;font-weight:800;color:#fff;">
              Med<span style="color:#a7f3d0;">Chain</span>
            </span>
          </td>
        </tr>

        <tr>
          <td style="padding:40px;text-align:center;">
            <div style="font-size:48px;margin-bottom:16px;">🎉</div>
            <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#1E3A52;">
              Bienvenue, {$prenom} !
            </h1>
            <p style="color:#374151;font-size:15px;line-height:1.7;">
              Votre compte MedChain a été créé avec succès.<br>
              Vous pouvez maintenant accéder à tous nos services.
            </p>
          </td>
        </tr>

        <tr>
          <td style="background:#F9FAFB;border-top:1px solid #E5E7EB;padding:20px;text-align:center;">
            <p style="margin:0;color:#9CA3AF;font-size:12px;">© 2025 MedChain</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
