<?php

class Materiel {
    private $idMateriel;
    private $id_materiel;
    private $bloc;
    private $pos_x;
    private $pos_y;
    private $pos_z;
    private $categorie;
    private $id_intervention;
    private $disponibilite;
    private $statutSterilisation;
    private $nombreUtilisationsMax;
    private $nombreUtilisationsActuelles;
    private $date_utilisation_debut;
    private $date_utilisation_fin;
    private $created_at;
    
    private $pdo;

    public function __construct() {
        $this->pdo = config::getConnexion();
    }

    // ==================== GETTERS ====================
    public function getId() { return $this->idMateriel; }
    public function getIdMaterielStr() { return $this->id_materiel; }
    public function getBloc() { return $this->bloc; }
    public function getPosX() { return $this->pos_x; }
    public function getPosY() { return $this->pos_y; }
    public function getPosZ() { return $this->pos_z; }
    public function getCategorie() { return $this->categorie; }
    public function getIdIntervention() { return $this->id_intervention; }
    public function getDisponibilite() { return $this->disponibilite; }
    public function getStatutSterilisation() { return $this->statutSterilisation; }
    public function getNombreUtilisationsMax() { return $this->nombreUtilisationsMax; }
    public function getNombreUtilisationsActuelles() { return $this->nombreUtilisationsActuelles; }
    public function getDateUtilisationDebut() { return $this->date_utilisation_debut; }
    public function getDateUtilisationFin() { return $this->date_utilisation_fin; }
    public function getCreatedAt() { return $this->created_at; }

    // ==================== SETTERS ====================
    public function setIdMaterielStr($id_materiel) { $this->id_materiel = htmlspecialchars(trim($id_materiel), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setBloc($bloc) { $this->bloc = htmlspecialchars(trim($bloc), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setPosX($x) { $this->pos_x = (float)$x; return $this; }
    public function setPosY($y) { $this->pos_y = (float)$y; return $this; }
    public function setPosZ($z) { $this->pos_z = (float)$z; return $this; }
    public function setCategorie($cat) { $this->categorie = htmlspecialchars(trim($cat), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setIdIntervention($id) { $this->id_intervention = ($id === '' || $id === null) ? null : (int)$id; return $this; }
    public function setDisponibilite($d) { $this->disponibilite = htmlspecialchars(trim($d), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setStatutSterilisation($s) { $this->statutSterilisation = htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setNombreUtilisationsMax($n) { $this->nombreUtilisationsMax = (int)$n; return $this; }
    public function setNombreUtilisationsActuelles($n) { $this->nombreUtilisationsActuelles = (int)$n; return $this; }
    public function setDateUtilisationDebut($d) { $this->date_utilisation_debut = empty($d) ? null : $d; return $this; }
    public function setDateUtilisationFin($d) { $this->date_utilisation_fin = empty($d) ? null : $d; return $this; }

    // ===== CRUD OPERATIONS =====
    
    // CREATE
    public function create(): bool {
        try {
            $sql = "INSERT INTO materiel (id_materiel, bloc, pos_x, pos_y, pos_z, categorie, id_intervention, disponibilite, statutSterilisation, nombreUtilisationsMax, nombreUtilisationsActuelles, date_utilisation_debut, date_utilisation_fin) 
                    VALUES (:id_materiel, :bloc, :pos_x, :pos_y, :pos_z, :categorie, :id_intervention, :disponibilite, :statutSterilisation, :nombreMax, :nombreActuelles, :dateDebut, :dateFin)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id_materiel' => $this->id_materiel,
                ':bloc' => $this->bloc,
                ':pos_x' => $this->pos_x,
                ':pos_y' => $this->pos_y,
                ':pos_z' => $this->pos_z,
                ':categorie' => $this->categorie,
                ':id_intervention' => $this->id_intervention,
                ':disponibilite' => $this->disponibilite,
                ':statutSterilisation' => $this->statutSterilisation,
                ':nombreMax' => $this->nombreUtilisationsMax,
                ':nombreActuelles' => $this->nombreUtilisationsActuelles,
                ':dateDebut' => $this->date_utilisation_debut,
                ':dateFin' => $this->date_utilisation_fin
            ]);
        } catch (Exception $e) {
            error_log("Erreur création matériel: " . $e->getMessage());
            return false;
        }
    }

    // READ ALL
    public function getAll($page = 1, $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT m.*, i.type as intervention_type, i.chirurgien as intervention_chirurgien 
                    FROM materiel m 
                    LEFT JOIN intervention i ON m.id_intervention = i.id 
                    ORDER BY m.idMateriel DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lecture matériels: " . $e->getMessage());
            return [];
        }
    }

    // READ ONE
    public function getById($id): ?array {
        try {
            $sql = "SELECT m.*, i.type as intervention_type, i.chirurgien as intervention_chirurgien 
                    FROM materiel m 
                    LEFT JOIN intervention i ON m.id_intervention = i.id 
                    WHERE m.idMateriel = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erreur lecture matériel: " . $e->getMessage());
            return null;
        }
    }

    // UPDATE
    public function update($id): bool {
        try {
            $sql = "UPDATE materiel SET 
                    id_materiel = :id_materiel, 
                    bloc = :bloc,
                    pos_x = :pos_x,
                    pos_y = :pos_y,
                    pos_z = :pos_z,
                    categorie = :categorie, 
                    id_intervention = :id_intervention,
                    disponibilite = :disponibilite, 
                    statutSterilisation = :statutSterilisation, 
                    nombreUtilisationsMax = :nombreMax, 
                    nombreUtilisationsActuelles = :nombreActuelles,
                    date_utilisation_debut = :dateDebut,
                    date_utilisation_fin = :dateFin
                    WHERE idMateriel = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => (int)$id,
                ':id_materiel' => $this->id_materiel,
                ':bloc' => $this->bloc,
                ':pos_x' => $this->pos_x,
                ':pos_y' => $this->pos_y,
                ':pos_z' => $this->pos_z,
                ':categorie' => $this->categorie,
                ':id_intervention' => $this->id_intervention,
                ':disponibilite' => $this->disponibilite,
                ':statutSterilisation' => $this->statutSterilisation,
                ':nombreMax' => $this->nombreUtilisationsMax,
                ':nombreActuelles' => $this->nombreUtilisationsActuelles,
                ':dateDebut' => $this->date_utilisation_debut,
                ':dateFin' => $this->date_utilisation_fin
            ]);
        } catch (Exception $e) {
            error_log("Erreur mise à jour matériel: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($id): bool {
        try {
            $sql = "DELETE FROM materiel WHERE idMateriel = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => (int)$id]);
        } catch (Exception $e) {
            error_log("Erreur suppression matériel: " . $e->getMessage());
            return false;
        }
    }

    // SEARCH
    public function search($keyword): array {
        try {
            $keyword = htmlspecialchars(trim($keyword), ENT_QUOTES, 'UTF-8');
            $sql = "SELECT * FROM materiel 
                    WHERE id_materiel LIKE :keyword 
                    OR categorie LIKE :keyword 
                    ORDER BY idMateriel DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':keyword' => "%$keyword%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur recherche matériel: " . $e->getMessage());
            return [];
        }
    }

    // COUNT
    public function count(): int {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM materiel");
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            return 0;
        }
    }

    // STATISTICS
    public function getStatistics(): array {
        try {
            $stats = [
                'total' => 0,
                'par_disponibilite' => [],
                'nombre_utilisations_max' => 0,
                'nombre_utilisations_actuelles' => 0
            ];

            // Total
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM materiel");
            $stats['total'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Par statut
            $stmt = $this->pdo->query("SELECT disponibilite, COUNT(*) as count FROM materiel GROUP BY disponibilite");
            $stats['par_disponibilite'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->pdo->query("SELECT SUM(nombreUtilisationsMax) as totalMax, SUM(nombreUtilisationsActuelles) as totalActuelles FROM materiel");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['nombre_utilisations_max'] = (int)($result['totalMax'] ?? 0);
            $stats['nombre_utilisations_actuelles'] = (int)($result['totalActuelles'] ?? 0);

            return $stats;
        } catch (Exception $e) {
            error_log("Erreur statistiques matériel: " . $e->getMessage());
            return [];
        }
    }

    // LISTE DES MATÉRIELS POUR LES SELECTS
    public function getAllOptions(): array {
        try {
            $sql = "SELECT idMateriel, id_materiel, categorie, bloc FROM materiel ORDER BY id_materiel ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur liste matériel: " . $e->getMessage());
            return [];
        }
    }

    public function existsByIdMaterielStr(string $id_materiel): bool {
        try {
            $sql = "SELECT COUNT(*) as total FROM materiel WHERE id_materiel = :id_materiel";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_materiel' => trim($id_materiel)]);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
        } catch (Exception $e) {
            error_log("Erreur vérification matériel: " . $e->getMessage());
            return false;
        }
    }

    public function getAllForExport(): array {
        try {
            $sql = "SELECT m.*, i.type as intervention_type, i.chirurgien as intervention_chirurgien 
                    FROM materiel m 
                    LEFT JOIN intervention i ON m.id_intervention = i.id 
                    ORDER BY m.idMateriel DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur export matériel: " . $e->getMessage());
            return [];
        }
    }

    // GET BY MEDECIN (pour n'afficher que les matériels des interventions du médecin)
    public function getByMedecin(string $prenomNom, string $nomPrenom, int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT m.*, i.type as intervention_type, i.chirurgien as intervention_chirurgien 
                    FROM materiel m 
                    LEFT JOIN intervention i ON m.id_intervention = i.id 
                    ORDER BY m.idMateriel DESC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lecture matériels par médecin: " . $e->getMessage());
            return [];
        }
    }

    // COUNT BY MEDECIN
    public function countByMedecin(string $prenomNom, string $nomPrenom): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM materiel";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            return 0;
        }
    }
}
