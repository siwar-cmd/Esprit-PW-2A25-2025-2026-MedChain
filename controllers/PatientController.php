<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Utilisateur.php';

class PatientController {
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // =========================================================================
    //  STATISTIQUES DASHBOARD
    // =========================================================================

    public function countRdvAVenir(int $patientId): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE id_patient = ? AND date_rdv >= CURDATE() AND statut != 'refuse'"
            );
            $stmt->execute([$patientId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('PatientController::countRdvAVenir - ' . $e->getMessage());
            return 0;
        }
    }

    public function countConsultations(int $patientId): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM consultation WHERE id_patient = ?"
            );
            $stmt->execute([$patientId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('PatientController::countConsultations - ' . $e->getMessage());
            return 0;
        }
    }

    public function countOrdonnances(int $patientId): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM ordonnance WHERE id_patient = ?"
            );
            $stmt->execute([$patientId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('PatientController::countOrdonnances - ' . $e->getMessage());
            return 0;
        }
    }

    public function countUnreadMessages(int $patientId): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM message WHERE id_destinataire = ? AND lu = 0"
            );
            $stmt->execute([$patientId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('PatientController::countUnreadMessages - ' . $e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    //  RDV
    // =========================================================================

    public function getRdvAVenir(int $patientId): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM rendez_vous r
                 JOIN utilisateur u ON u.id_utilisateur = r.id_medecin
                 WHERE r.id_patient = ? AND r.date_rdv >= CURDATE() AND r.statut != 'refuse'
                 ORDER BY r.date_rdv ASC, r.heure ASC"
            );
            $stmt->execute([$patientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getRdvAVenir - ' . $e->getMessage());
            return [];
        }
    }

    public function getAllRdv(int $patientId): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM rendez_vous r
                 JOIN utilisateur u ON u.id_utilisateur = r.id_medecin
                 WHERE r.id_patient = ?
                 ORDER BY r.date_rdv DESC, r.heure DESC"
            );
            $stmt->execute([$patientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getAllRdv - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crée un nouveau rendez-vous pour le patient.
     * $data doit contenir : id_medecin, date_rdv, heure, motif.
     */
    public function createRdv(int $patientId, array $data): array {
        try {
            $required = ['id_medecin', 'date_rdv', 'heure'];
            foreach ($required as $f) {
                if (empty($data[$f])) return ['success' => false, 'message' => "Champ manquant : $f"];
            }

            // Vérification disponibilité (créneau déjà pris)
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE id_medecin = ? AND date_rdv = ? AND heure = ? AND statut != 'refuse'"
            );
            $stmt->execute([$data['id_medecin'], $data['date_rdv'], $data['heure']]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Ce créneau est déjà pris. Choisissez un autre horaire.'];
            }

            // Vérification date future
            if (strtotime($data['date_rdv']) < strtotime('today')) {
                return ['success' => false, 'message' => 'La date du rendez-vous doit être dans le futur.'];
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO rendez_vous (id_patient, id_medecin, date_rdv, heure, motif, statut)
                 VALUES (?, ?, ?, ?, ?, 'en_attente')"
            );
            $ok = $stmt->execute([
                $patientId,
                (int) $data['id_medecin'],
                $data['date_rdv'],
                $data['heure'],
                $data['motif'] ?? 'Consultation générale',
            ]);
            return $ok
                ? ['success' => true, 'message' => 'Rendez-vous demandé avec succès.', 'id' => $this->pdo->lastInsertId()]
                : ['success' => false, 'message' => 'Erreur lors de la création du rendez-vous.'];
        } catch (Exception $e) {
            error_log('PatientController::createRdv - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur.'];
        }
    }

    public function cancelRdv(int $patientId, int $rdvId): array {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE rendez_vous SET statut = 'annule'
                 WHERE id_rdv = ? AND id_patient = ? AND date_rdv >= CURDATE()"
            );
            $ok = $stmt->execute([$rdvId, $patientId]);
            if ($ok && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Rendez-vous annulé.'];
            }
            return ['success' => false, 'message' => 'Impossible d\'annuler ce rendez-vous.'];
        } catch (Exception $e) {
            error_log('PatientController::cancelRdv - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur.'];
        }
    }

    // =========================================================================
    //  MÉDECINS (liste pour prendre RDV)
    // =========================================================================

    public function getMedecins(string $search = ''): array {
        try {
            $where = "role = 'medecin' AND statut = 'actif'";
            $params = [];
            if ($search !== '') {
                $where .= " AND (nom LIKE ? OR prenom LIKE ? OR specialite LIKE ?)";
                $s = '%' . $search . '%';
                $params = [$s, $s, $s];
            }
            $stmt = $this->pdo->prepare(
                "SELECT id_utilisateur, nom, prenom, email, photo_profil, specialite
                 FROM utilisateur
                 WHERE $where
                 ORDER BY nom ASC, prenom ASC"
            );
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getMedecins - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Créneaux disponibles d'un médecin pour une date donnée.
     */
    public function getCreneauxDisponibles(int $medecinId, string $date): array {
        // Créneaux théoriques (08h–18h, toutes les 30 min)
        $tous = [];
        for ($h = 8; $h < 18; $h++) {
            $tous[] = sprintf('%02d:00', $h);
            $tous[] = sprintf('%02d:30', $h);
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT heure FROM rendez_vous
                 WHERE id_medecin = ? AND date_rdv = ? AND statut != 'refuse'"
            );
            $stmt->execute([$medecinId, $date]);
            $pris = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return array_values(array_filter($tous, fn($c) => !in_array($c, $pris)));
        } catch (Exception $e) {
            error_log('PatientController::getCreneauxDisponibles - ' . $e->getMessage());
            return $tous;
        }
    }

    // =========================================================================
    //  CONSULTATIONS
    // =========================================================================

    public function getRecentConsultations(int $patientId, int $limit = 10): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM consultation c
                 JOIN utilisateur u ON u.id_utilisateur = c.id_medecin
                 WHERE c.id_patient = ?
                 ORDER BY c.date_consultation DESC
                 LIMIT ?"
            );
            $stmt->execute([$patientId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getRecentConsultations - ' . $e->getMessage());
            return [];
        }
    }

    public function getConsultationById(int $patientId, int $consultationId): ?array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom,
                        u.email  AS medecin_email
                 FROM consultation c
                 JOIN utilisateur u ON u.id_utilisateur = c.id_medecin
                 WHERE c.id_consultation = ? AND c.id_patient = ?"
            );
            $stmt->execute([$consultationId, $patientId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('PatientController::getConsultationById - ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    //  ORDONNANCES
    // =========================================================================

    public function getOrdonnances(int $patientId): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT o.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM ordonnance o
                 JOIN utilisateur u ON u.id_utilisateur = o.id_medecin
                 WHERE o.id_patient = ?
                 ORDER BY o.date_ordonnance DESC"
            );
            $stmt->execute([$patientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getOrdonnances - ' . $e->getMessage());
            return [];
        }
    }

    public function getOrdonnanceById(int $patientId, int $ordonnanceId): ?array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT o.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM ordonnance o
                 JOIN utilisateur u ON u.id_utilisateur = o.id_medecin
                 WHERE o.id_ordonnance = ? AND o.id_patient = ?"
            );
            $stmt->execute([$ordonnanceId, $patientId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('PatientController::getOrdonnanceById - ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    //  ANALYSES
    // =========================================================================

    public function getAnalyses(int $patientId): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM analyse a
                 JOIN utilisateur u ON u.id_utilisateur = a.id_medecin
                 WHERE a.id_patient = ?
                 ORDER BY a.date_analyse DESC"
            );
            $stmt->execute([$patientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getAnalyses - ' . $e->getMessage());
            return [];
        }
    }

    public function getAnalyseById(int $patientId, int $analyseId): ?array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.*,
                        u.nom    AS medecin_nom,
                        u.prenom AS medecin_prenom
                 FROM analyse a
                 JOIN utilisateur u ON u.id_utilisateur = a.id_medecin
                 WHERE a.id_analyse = ? AND a.id_patient = ?"
            );
            $stmt->execute([$analyseId, $patientId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('PatientController::getAnalyseById - ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    //  NOTIFICATIONS
    // =========================================================================

    public function getNotifications(int $patientId, int $limit = 20): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM notification
                 WHERE id_utilisateur = ?
                 ORDER BY created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$patientId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PatientController::getNotifications - ' . $e->getMessage());
            return [];
        }
    }

    public function markNotificationRead(int $patientId, int $notifId): bool {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE notification SET lu = 1
                 WHERE id_notification = ? AND id_utilisateur = ?"
            );
            return $stmt->execute([$notifId, $patientId]);
        } catch (Exception $e) {
            error_log('PatientController::markNotificationRead - ' . $e->getMessage());
            return false;
        }
    }
}