<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/UtilisateurController.php';

class SmsService {
    
    /**
     * Sends a distribution notification to administrators.
     */
    public static function sendDistributionNotification($distributionData) {
        $userController = new UtilisateurController();
        $admins = $userController->getUsersByRole('admin');
        
        $recipients = [];
        foreach ($admins as $admin) {
            if ($admin->getTelephone()) {
                $recipients[] = $admin->getTelephone();
            }
        }
        
        // Fallback if no admin phone found
        if (empty($recipients)) {
            $recipients[] = config::ADMIN_FALLBACK_PHONE;
        }
        
        $medicament = $distributionData['nom_medicament'] ?? 'Médicament inconnu';
        $quantite = $distributionData['quantite_distribuee'] ?? '0';
        $patient = $distributionData['patient'] ?? 'Inconnu';
        $medecin = $distributionData['responsable'] ?? 'Inconnu';
        $dateHeure = date('d/m/Y H:i');

        $message = "🚨 MedChain Alerte : Nouvelle distribution !\n"
                 . "💊 Médicament : $medicament\n"
                 . "📦 Qté : $quantite\n"
                 . "👤 Patient : $patient\n"
                 . "👨‍⚕️ Médecin : $medecin\n"
                 . "⏰ Date/Heure : $dateHeure";

        foreach ($recipients as $to) {
            self::sendSms($to, $message);
        }
    }

    /**
     * Sends an SMS using Twilio REST API.
     */
    public static function sendSms($to, $message) {
        $sid = config::TWILIO_SID;
        $token = config::TWILIO_TOKEN;
        $from = config::TWILIO_FROM_NUMBER;
        
        if ($sid === 'AC_YOUR_ACCOUNT_SID' || empty($sid)) {
            self::logError("SMS non envoyé : Identifiants Twilio non configurés (SID actuel: '$sid').", $to, $message);
            return false;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
        
        $data = [
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ];

        $post = http_build_query($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            self::logError("Échec envoi Twilio (HTTP $httpCode) : $response", $to, $message);
            return false;
        }
    }

    /**
     * Logs errors to a file.
     */
    private static function logError($errorMessage, $to, $messageBody) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/sms_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] TO: $to | ERROR: $errorMessage | MESSAGE: " . str_replace("\n", " ", $messageBody) . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
