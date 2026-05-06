<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Utilisateur.php';

class MedecinController {
    private $pdo;
    private int $medecinId;

    public function __construct() {
        $this->pdo = config::getConnexion();
        $this->medecinId = $_SESSION['user_id'] ?? 0;
    }

    // =========================================================================
    //  STATISTIQUES DASHBOARD
    // =========================================================================

    public function countPatients(): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT r.id_patient)
                 FROM rendez_vous r
                 WHERE r.id_medecin = ?"
            );
            $stmt->execute([$this->medecinId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('MedecinController::countPatients - ' . $e->getMessage());
            return 0;
        }
    }

    public function countRdvToday(): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE id_medecin = ? AND DATE(date_rdv) = CURDATE()"
            );
            $stmt->execute([$this->medecinId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('MedecinController::countRdvToday - ' . $e->getMessage());
            return 0;
        }
    }

    public function countConsultations(): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM consultation WHERE id_medecin = ?"
            );
            $stmt->execute([$this->medecinId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('MedecinController::countConsultations - ' . $e->getMessage());
            return 0;
        }
    }

    public function countUnreadMessages(): int {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM message
                 WHERE id_destinataire = ? AND lu = 0"
            );
            $stmt->execute([$this->medecinId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('MedecinController::countUnreadMessages - ' . $e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    //  RDV DU JOUR
    // =========================================================================

    public function getRdvToday(): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.*,
                        u.nom   AS patient_nom,
                        u.prenom AS patient_prenom,
                        u.email  AS patient_email
                 FROM rendez_vous r
                 JOIN utilisateur u ON u.id_utilisateur = r.id_patient
                 WHERE r.id_medecin = ? AND DATE(r.date_rdv) = CURDATE()
                 ORDER BY r.heure ASC"
            );
            $stmt->execute([$this->medecinId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getRdvToday - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Tous les RDV (paginés, avec filtre optionnel de statut).
     */
    public function getAllRdv(string $statut = '', int $limit = 50, int $offset = 0): array {
        try {
            $where = "r.id_medecin = ?";
            $params = [$this->medecinId];
            if ($statut !== '') {
                $where .= " AND r.statut = ?";
                $params[] = $statut;
            }
            $stmt = $this->pdo->prepare(
                "SELECT r.*,
                        u.nom    AS patient_nom,
                        u.prenom AS patient_prenom,
                        u.email  AS patient_email
                 FROM rendez_vous r
                 JOIN utilisateur u ON u.id_utilisateur = r.id_patient
                 WHERE $where
                 ORDER BY r.date_rdv DESC, r.heure ASC
                 LIMIT ? OFFSET ?"
            );
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getAllRdv - ' . $e->getMessage());
            return [];
        }
    }

    public function acceptRdv(int $rdvId): array {
        return $this->changeRdvStatut($rdvId, 'confirme');
    }

    public function refuseRdv(int $rdvId): array {
        return $this->changeRdvStatut($rdvId, 'refuse');
    }

    private function changeRdvStatut(int $rdvId, string $statut): array {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE rendez_vous SET statut = ?
                 WHERE id_rdv = ? AND id_medecin = ?"
            );
            $ok = $stmt->execute([$statut, $rdvId, $this->medecinId]);
            if ($ok && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Statut mis à jour'];
            }
            return ['success' => false, 'message' => 'RDV introuvable ou non autorisé'];
        } catch (Exception $e) {
            error_log('MedecinController::changeRdvStatut - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur'];
        }
    }

    // =========================================================================
    //  PATIENTS
    // =========================================================================

    /**
     * Patients ayant eu un RDV avec ce médecin (triés par date dernier RDV desc).
     */
    public function getRecentPatients(int $limit = 10): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.*,
                        MAX(r.date_rdv) AS derniere_visite
                 FROM utilisateur u
                 JOIN rendez_vous r ON r.id_patient = u.id_utilisateur
                 WHERE r.id_medecin = ? AND u.role = 'patient'
                 GROUP BY u.id_utilisateur
                 ORDER BY derniere_visite DESC
                 LIMIT ?"
            );
            $stmt->execute([$this->medecinId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getRecentPatients - ' . $e->getMessage());
            return [];
        }
    }

    public function getAllPatients(string $search = ''): array {
        try {
            $where = "r.id_medecin = ? AND u.role = 'patient'";
            $params = [$this->medecinId];
            if ($search !== '') {
                $where .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
                $s = '%' . $search . '%';
                $params = array_merge($params, [$s, $s, $s]);
            }
            $stmt = $this->pdo->prepare(
                "SELECT u.*,
                        MAX(r.date_rdv) AS derniere_visite,
                        COUNT(r.id_rdv) AS nb_rdv
                 FROM utilisateur u
                 JOIN rendez_vous r ON r.id_patient = u.id_utilisateur
                 WHERE $where
                 GROUP BY u.id_utilisateur
                 ORDER BY u.nom ASC, u.prenom ASC"
            );
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getAllPatients - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Dossier complet d'un patient (vérification que le médecin le suit).
     */
    public function getPatientDossier(int $patientId): ?array {
        try {
            // Vérification accès
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM rendez_vous
                 WHERE id_medecin = ? AND id_patient = ?"
            );
            $stmt->execute([$this->medecinId, $patientId]);
            if ($stmt->fetchColumn() == 0) return null;

            // Infos patient
            $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$patientId]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$patient) return null;

            // RDV
            $stmt = $this->pdo->prepare(
                "SELECT * FROM rendez_vous WHERE id_patient = ? AND id_medecin = ?
                 ORDER BY date_rdv DESC"
            );
            $stmt->execute([$patientId, $this->medecinId]);
            $patient['rdv'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Consultations
            $stmt = $this->pdo->prepare(
                "SELECT * FROM consultation WHERE id_patient = ? AND id_medecin = ?
                 ORDER BY date_consultation DESC"
            );
            $stmt->execute([$patientId, $this->medecinId]);
            $patient['consultations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ordonnances
            $stmt = $this->pdo->prepare(
                "SELECT * FROM ordonnance WHERE id_patient = ? AND id_medecin = ?
                 ORDER BY date_ordonnance DESC"
            );
            $stmt->execute([$patientId, $this->medecinId]);
            $patient['ordonnances'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $patient;
        } catch (Exception $e) {
            error_log('MedecinController::getPatientDossier - ' . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    //  CONSULTATIONS
    // =========================================================================

    public function createConsultation(array $data): array {
        try {
            $required = ['id_patient', 'date_consultation', 'diagnostic'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Champ manquant : $field"];
                }
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO consultation
                    (id_patient, id_medecin, date_consultation, diagnostic, notes, traitement)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $ok = $stmt->execute([
                (int) $data['id_patient'],
                $this->medecinId,
                $data['date_consultation'],
                $data['diagnostic'],
                $data['notes']      ?? null,
                $data['traitement'] ?? null,
            ]);
            return $ok
                ? ['success' => true, 'message' => 'Consultation enregistrée', 'id' => $this->pdo->lastInsertId()]
                : ['success' => false, 'message' => 'Erreur lors de l\'insertion'];
        } catch (Exception $e) {
            error_log('MedecinController::createConsultation - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur'];
        }
    }

    public function getConsultationsByPatient(int $patientId): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*,
                        u.nom    AS patient_nom,
                        u.prenom AS patient_prenom
                 FROM consultation c
                 JOIN utilisateur u ON u.id_utilisateur = c.id_patient
                 WHERE c.id_medecin = ? AND c.id_patient = ?
                 ORDER BY c.date_consultation DESC"
            );
            $stmt->execute([$this->medecinId, $patientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getConsultationsByPatient - ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  ORDONNANCES
    // =========================================================================

    public function createOrdonnance(array $data): array {
        try {
            $required = ['id_patient', 'contenu'];
            foreach ($required as $f) {
                if (empty($data[$f])) return ['success' => false, 'message' => "Champ manquant : $f"];
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO ordonnance (id_patient, id_medecin, titre, contenu, date_ordonnance)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $ok = $stmt->execute([
                (int) $data['id_patient'],
                $this->medecinId,
                $data['titre']   ?? 'Ordonnance',
                $data['contenu'],
            ]);
            return $ok
                ? ['success' => true, 'message' => 'Ordonnance créée', 'id' => $this->pdo->lastInsertId()]
                : ['success' => false, 'message' => 'Erreur lors de l\'insertion'];
        } catch (Exception $e) {
            error_log('MedecinController::createOrdonnance - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur'];
        }
    }

    // =========================================================================
    //  EXAMENS / ANALYSES
    // =========================================================================

    public function createExamen(array $data): array {
        try {
            $required = ['id_patient', 'type_analyse'];
            foreach ($required as $f) {
                if (empty($data[$f])) return ['success' => false, 'message' => "Champ manquant : $f"];
            }
            $stmt = $this->pdo->prepare(
                "INSERT INTO analyse (id_patient, id_medecin, type_analyse, resultat, date_analyse)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $ok = $stmt->execute([
                (int) $data['id_patient'],
                $this->medecinId,
                $data['type_analyse'],
                $data['resultat'] ?? null,
            ]);
            return $ok
                ? ['success' => true, 'message' => 'Examen enregistré', 'id' => $this->pdo->lastInsertId()]
                : ['success' => false, 'message' => 'Erreur lors de l\'insertion'];
        } catch (Exception $e) {
            error_log('MedecinController::createExamen - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur'];
        }
    }

    // =========================================================================
    //  MESSAGES
    // =========================================================================

    public function getMessages(int $limit = 30): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT m.*,
                        u.nom    AS expediteur_nom,
                        u.prenom AS expediteur_prenom
                 FROM message m
                 JOIN utilisateur u ON u.id_utilisateur = m.id_expediteur
                 WHERE m.id_destinataire = ?
                 ORDER BY m.date_envoi DESC
                 LIMIT ?"
            );
            $stmt->execute([$this->medecinId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getMessages - ' . $e->getMessage());
            return [];
        }
    }

    public function sendMessage(int $destinataireId, string $sujet, string $corps): array {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO message (id_expediteur, id_destinataire, sujet, corps, date_envoi, lu)
                 VALUES (?, ?, ?, ?, NOW(), 0)"
            );
            $ok = $stmt->execute([$this->medecinId, $destinataireId, $sujet, $corps]);
            return $ok
                ? ['success' => true, 'message' => 'Message envoyé']
                : ['success' => false, 'message' => 'Erreur lors de l\'envoi'];
        } catch (Exception $e) {
            error_log('MedecinController::sendMessage - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur'];
        }
    }

    // =========================================================================
    //  NOTIFICATIONS
    // =========================================================================

    public function getNotifications(int $limit = 10): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM notification
                 WHERE id_utilisateur = ?
                 ORDER BY created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$this->medecinId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('MedecinController::getNotifications - ' . $e->getMessage());
            return [];
        }
    }
}