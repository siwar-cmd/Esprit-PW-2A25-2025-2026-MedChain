<?php
require_once __DIR__ . '/../config.php';

class NotificationController {
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    /**
     * Envoie des rappels pour les rendez-vous prévus dans 24 heures.
     */
    public function sendAppointmentReminders() {
        try {
            // Trouver les RDV dans ~24h (entre 23h et 25h pour être sûr de ne rater aucun créneau selon l'heure d'exécution)
            // qui n'ont pas encore reçu de rappel
            $sql = "SELECT r.*, u.nom, u.prenom, u.email 
                    FROM rendezvous r
                    JOIN utilisateur u ON r.idClient = u.id_utilisateur
                    WHERE r.rappel_envoye = 0 
                    AND r.statut = 'planifie'
                    AND r.dateHeureDebut BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)";
            
            $stmt = $this->pdo->query($sql);
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            foreach ($rdvs as $rdv) {
                $message = "Bonjour " . $rdv['prenom'] . ",\n\nCeci est un rappel pour votre rendez-vous prévu le " . date('d/m/Y à H:i', strtotime($rdv['dateHeureDebut'])) . ".\n\nCordialement,\nL'équipe MedChain";
                
                if ($this->sendEmail($rdv['email'], "Rappel de votre rendez-vous MedChain", $message)) {
                    // Marquer comme envoyé
                    $update = $this->pdo->prepare("UPDATE rendezvous SET rappel_envoye = 1 WHERE idRDV = ?");
                    $update->execute([$rdv['idRDV']]);
                    $count++;
                }
            }
            return ["success" => true, "sent" => $count];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Envoie des notifications pour les suivis recommandés (Prochain RDV).
     */
    public function sendFollowUpNotifications() {
        try {
            // Trouver les fiches avec une date de prochain RDV aujourd'hui
            // et qui n'ont pas encore été notifiées
            $sql = "SELECT f.*, u.nom, u.prenom, u.email 
                    FROM ficherendezvous f
                    JOIN rendezvous r ON f.idRDV = r.idRDV
                    JOIN utilisateur u ON r.idClient = u.id_utilisateur
                    WHERE f.rappel_prochain_rdv_envoye = 0 
                    AND f.prochainRDV <= CURDATE()";
            
            $stmt = $this->pdo->query($sql);
            $fiches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;
            foreach ($fiches as $fiche) {
                $message = "Bonjour " . $fiche['prenom'] . ",\n\nVotre médecin vous a recommandé un suivi aux alentours de cette date. Nous vous invitons à prendre un nouveau rendez-vous sur la plateforme MedChain.\n\nCordialement,\nL'équipe MedChain";

                if ($this->sendEmail($fiche['email'], "Invitation au suivi médical - MedChain", $message)) {
                    // Marquer comme envoyé
                    $update = $this->pdo->prepare("UPDATE ficherendezvous SET rappel_prochain_rdv_envoye = 1 WHERE idFiche = ?");
                    $update->execute([$fiche['idFiche']]);
                    $count++;
                }
            }
            return ["success" => true, "sent" => $count];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Envoie un email via l'API Brevo (Sendinblue)
     */
    private function sendEmail($to, $subject, $body) {
        // --- CONFIGURATION API ---
        $apiKey = 'VOTRE_CLE_API_BREVO'; // REMPLACEZ PAR VOTRE CLÉ API
        // -------------------------

        // Si la clé n'est pas configurée, on simule seulement
        if ($apiKey === 'VOTRE_CLE_API_BREVO') {
            return $this->simulateEmail($to, $subject, $body);
        }

        $data = [
            "sender" => ["name" => "MedChain", "email" => "noreply@medchain.com"],
            "to" => [["email" => $to]],
            "subject" => $subject,
            "textContent" => $body
        ];

        $ch = curl_init("https://api.brevo.com/v3/smtp/email");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "api-key: $apiKey",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // On garde toujours une trace dans le log local
        $this->simulateEmail($to, $subject, $body);

        return ($httpCode === 201 || $httpCode === 200);
    }

    /**
     * Simule l'envoi d'un email (journalise dans un fichier logs/mail.log)
     */
    private function simulateEmail($to, $subject, $body) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        
        $logEntry = "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject\nBODY: $body\n-----------------------------------\n";
        return file_put_contents($logDir . '/mail.log', $logEntry, FILE_APPEND) !== false;
    }
}
