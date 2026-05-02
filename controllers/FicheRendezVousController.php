<?php
require_once __DIR__ . '/../models/FicheRendezVous.php';
require_once __DIR__ . '/../config.php';

class FicheRendezVousController {
    private $pdo;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => false,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
        $this->pdo = config::getConnexion();
    }

    public function getFicheByRdvId($idRDV): ?array {
        try {
            $req = $this->pdo->prepare('SELECT * FROM ficherendezvous WHERE idRDV = ?');
            $req->execute([$idRDV]);
            $fiche = $req->fetch(PDO::FETCH_ASSOC);
            return $fiche ?: null;
        } catch (Exception $e) {
            error_log("Erreur getFicheByRdvId: " . $e->getMessage());
            return null;
        }
    }

    public function getFicheById($idFiche): ?array {
        try {
            $sql = 'SELECT f.*, r.dateHeureDebut, r.statut, r.typeConsultation, r.motif, 
                           u1.nom as patient_nom, u1.prenom as patient_prenom, u1.email as patient_email,
                           u2.nom as medecin_nom, u2.prenom as medecin_prenom 
                    FROM ficherendezvous f 
                    JOIN rendezvous r ON f.idRDV = r.idRDV 
                    LEFT JOIN utilisateur u1 ON r.idClient = u1.id_utilisateur 
                    LEFT JOIN utilisateur u2 ON r.idMedecin = u2.id_utilisateur 
                    WHERE f.idFiche = ?';
            $req = $this->pdo->prepare($sql);
            $req->execute([$idFiche]);
            $fiche = $req->fetch(PDO::FETCH_ASSOC);
            return $fiche ?: null;
        } catch (Exception $e) {
            error_log("Erreur getFicheById: " . $e->getMessage());
            return null;
        }
    }

    public function createFiche($data): array {
        try {
            if (empty($data['idRDV'])) {
                return ["success" => false, "message" => "L'ID du rendez-vous est obligatoire"];
            }

            // Check if already exists
            if ($this->getFicheByRdvId($data['idRDV'])) {
                return ["success" => false, "message" => "Une fiche existe déjà pour ce rendez-vous"];
            }

            $sql = 'INSERT INTO ficherendezvous (idRDV, dateGeneration, piecesAApporter, tarifConsultation, modeRemboursement, emailEnvoye, calendrierAjoute, antecedents, allergies, motifPrincipal, modeConsultation, statutPaiement, tensionArterielle, poids, taille, temperature, prescription, examensComplementaires, observations, prochainRDV) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $req = $this->pdo->prepare($sql);
            $success = $req->execute([
                $data['idRDV'],
                $data['dateGeneration'] ?? date('Y-m-d'),
                htmlspecialchars($data['piecesAApporter'] ?? ''),
                $data['tarifConsultation'] ?? 0.0,
                htmlspecialchars($data['modeRemboursement'] ?? ''),
                $data['emailEnvoye'] ?? 0,
                $data['calendrierAjoute'] ?? 0,
                htmlspecialchars($data['antecedents'] ?? ''),
                htmlspecialchars($data['allergies'] ?? ''),
                htmlspecialchars($data['motifPrincipal'] ?? ''),
                htmlspecialchars($data['modeConsultation'] ?? 'Présentiel'),
                htmlspecialchars($data['statutPaiement'] ?? 'En attente'),
                htmlspecialchars($data['tensionArterielle'] ?? ''),
                $data['poids'] ?? null,
                $data['taille'] ?? null,
                $data['temperature'] ?? null,
                htmlspecialchars($data['prescription'] ?? ''),
                htmlspecialchars($data['examensComplementaires'] ?? ''),
                htmlspecialchars($data['observations'] ?? ''),
                $data['prochainRDV'] ?? null
            ]);

            if ($success) {
                return ["success" => true, "message" => "Fiche créée avec succès"];
            }

            return ["success" => false, "message" => "Erreur lors de la création de la fiche"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function updateFiche($idFiche, $data): array {
        try {
            $updates = [];
            $params = [];
            
            $allowedFields = ['piecesAApporter', 'tarifConsultation', 'modeRemboursement', 'emailEnvoye', 'calendrierAjoute', 'antecedents', 'allergies', 'motifPrincipal', 'modeConsultation', 'statutPaiement', 'tensionArterielle', 'poids', 'taille', 'temperature', 'prescription', 'examensComplementaires', 'observations', 'prochainRDV'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = htmlspecialchars($data[$field]);
                }
            }
            
            if (empty($updates)) {
                return ["success" => false, "message" => "Aucune donnée à mettre à jour"];
            }
            
            $params[] = $idFiche;
            $sql = "UPDATE ficherendezvous SET " . implode(', ', $updates) . " WHERE idFiche = ?";
            
            $req = $this->pdo->prepare($sql);
            $success = $req->execute($params);
            
            if ($success) {
                return ["success" => true, "message" => "Fiche mise à jour avec succès"];
            }
            
            return ["success" => false, "message" => "Erreur lors de la mise à jour"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function deleteFiche($idFiche): array {
        try {
            $req = $this->pdo->prepare("DELETE FROM ficherendezvous WHERE idFiche = ?");
            $success = $req->execute([$idFiche]);
            
            if ($success) {
                return ["success" => true, "message" => "Fiche supprimée avec succès"];
            }
            return ["success" => false, "message" => "Erreur lors de la suppression de la fiche"];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getAllFiches($filters = [], $role = 'admin', $userId = null): array {
        try {
            $sql = 'SELECT f.*, r.dateHeureDebut, r.statut, r.typeConsultation, r.motif, 
                           u1.nom as patient_nom, u1.prenom as patient_prenom, 
                           u2.nom as medecin_nom, u2.prenom as medecin_prenom 
                    FROM ficherendezvous f 
                    JOIN rendezvous r ON f.idRDV = r.idRDV 
                    LEFT JOIN utilisateur u1 ON r.idClient = u1.id_utilisateur 
                    LEFT JOIN utilisateur u2 ON r.idMedecin = u2.id_utilisateur 
                    WHERE 1=1';
            $params = [];
            
            if ($role === 'patient' && $userId !== null) {
                $sql .= ' AND r.idClient = ?';
                $params[] = $userId;
            } elseif ($role === 'medecin' && $userId !== null) {
                $sql .= ' AND r.idMedecin = ?';
                $params[] = $userId;
            }
            
            if (!empty($filters['search'])) {
                $sql .= ' AND (u1.nom LIKE ? OR u1.prenom LIKE ? OR u2.nom LIKE ? OR r.motif LIKE ? OR f.consignesAvantConsultation LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= ' ORDER BY f.dateGeneration DESC';
            
            $req = $this->pdo->prepare($sql);
            $req->execute($params);
            
            $fiches = $req->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                "success" => true,
                "fiches" => $fiches,
                "count" => count($fiches)
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Erreur: " . $e->getMessage()];
        }
    }

    public function getStats($role = 'admin', $userId = null): array {
        try {
            $cond = "1=1";
            $params = [];
            if ($role === 'patient' && $userId) {
                $cond = "r.idClient = ?";
                $params[] = $userId;
            } elseif ($role === 'medecin' && $userId) {
                $cond = "r.idMedecin = ?";
                $params[] = $userId;
            }

            // Join with rendezvous required for role-based conditions
            $baseSql = "FROM ficherendezvous f JOIN rendezvous r ON f.idRDV = r.idRDV WHERE $cond";

            // Total Fiches
            $req = $this->pdo->prepare("SELECT COUNT(*) as total $baseSql");
            $req->execute($params);
            $total = $req->fetch(PDO::FETCH_ASSOC)['total'];

            // Emails Envoyés
            $req = $this->pdo->prepare("SELECT COUNT(*) as emails_sent $baseSql AND f.emailEnvoye = 1");
            $req->execute($params);
            $emailsSent = $req->fetch(PDO::FETCH_ASSOC)['emails_sent'];

            // Ce mois
            $sqlCeMois = "SELECT COUNT(*) as ce_mois $baseSql AND MONTH(f.dateGeneration) = MONTH(CURRENT_DATE()) AND YEAR(f.dateGeneration) = YEAR(CURRENT_DATE())";
            $req = $this->pdo->prepare($sqlCeMois);
            $req->execute($params);
            $ceMois = $req->fetch(PDO::FETCH_ASSOC)['ce_mois'];

            return [
                'total' => $total,
                'ce_mois' => $ceMois,
                'emails_sent' => $emailsSent
            ];
        } catch (Exception $e) {
            return ['total' => 0, 'ce_mois' => 0, 'emails_sent' => 0];
        }
    }
}
