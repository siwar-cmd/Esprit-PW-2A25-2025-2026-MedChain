<?php
require_once __DIR__ . '/../config.php';

class Materiel {
    private $idMateriel;
    private $nom;
    private $categorie;
    private $disponibilite;
    private $statutSterilisation;
    private $nombreUtilisationsMax;
    private $nombreUtilisationsActuelles;

    // ==================== CONSTRUCTEUR ====================
    public function __construct($nom = "", $categorie = "", $disponibilite = "", $statutSterilisation = "", $nombreUtilisationsMax = 0, $nombreUtilisationsActuelles = 0) {
        $this->nom = $nom;
        $this->categorie = $categorie;
        $this->disponibilite = $disponibilite;
        $this->statutSterilisation = $statutSterilisation;
        $this->nombreUtilisationsMax = $nombreUtilisationsMax;
        $this->nombreUtilisationsActuelles = $nombreUtilisationsActuelles;
    }

    // ==================== GETTERS ====================
    public function getIdMateriel() { return $this->idMateriel; }
    public function getNom() { return $this->nom; }
    public function getCategorie() { return $this->categorie; }
    public function getDisponibilite() { return $this->disponibilite; }
    public function getStatutSterilisation() { return $this->statutSterilisation; }
    public function getNombreUtilisationsMax() { return $this->nombreUtilisationsMax; }
    public function getNombreUtilisationsActuelles() { return $this->nombreUtilisationsActuelles; }

    // ==================== SETTERS ====================
    public function setIdMateriel($id) { $this->idMateriel = (int)$id; return $this; }
    public function setNom($nom) { $this->nom = htmlspecialchars(trim($nom), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setCategorie($cat) { $this->categorie = htmlspecialchars(trim($cat), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setDisponibilite($dispo) { $this->disponibilite = htmlspecialchars(trim($dispo), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setStatutSterilisation($statut) { $this->statutSterilisation = htmlspecialchars(trim($statut), ENT_QUOTES, 'UTF-8'); return $this; }
    public function setNombreUtilisationsMax($nb) { $this->nombreUtilisationsMax = (int)$nb; return $this; }
    public function setNombreUtilisationsActuelles($nb) { $this->nombreUtilisationsActuelles = (int)$nb; return $this; }

    // ==================== MÉTHODES CRUD ====================

    public function getAllMateriels() {
        $pdo = config::getConnexion();
        $sql = "SELECT * FROM materiel ORDER BY nom ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMaterielById($id) {
        $pdo = config::getConnexion();
        $sql = "SELECT * FROM materiel WHERE idMateriel = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addMateriel($data) {
        $pdo = config::getConnexion();
        $sql = "INSERT INTO materiel (nom, categorie, disponibilite, statutSterilisation, nombreUtilisationsMax, nombreUtilisationsActuelles) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            htmlspecialchars($data['nom']),
            htmlspecialchars($data['categorie'] ?? ''),
            htmlspecialchars($data['disponibilite'] ?? ''),
            htmlspecialchars($data['statutSterilisation'] ?? ''),
            (int)($data['nombreUtilisationsMax'] ?? 0),
            (int)($data['nombreUtilisationsActuelles'] ?? 0)
        ]);
    }

    public function updateMateriel($id, $data) {
        $pdo = config::getConnexion();
        $sql = "UPDATE materiel SET nom = ?, categorie = ?, disponibilite = ?, statutSterilisation = ?, nombreUtilisationsMax = ?, nombreUtilisationsActuelles = ? 
                WHERE idMateriel = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            htmlspecialchars($data['nom']),
            htmlspecialchars($data['categorie'] ?? ''),
            htmlspecialchars($data['disponibilite'] ?? ''),
            htmlspecialchars($data['statutSterilisation'] ?? ''),
            (int)($data['nombreUtilisationsMax'] ?? 0),
            (int)($data['nombreUtilisationsActuelles'] ?? 0),
            (int)$id
        ]);
    }

    public function deleteMateriel($id) {
        $pdo = config::getConnexion();
        $sql = "DELETE FROM materiel WHERE idMateriel = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function searchMateriels($keyword) {
        $pdo = config::getConnexion();
        $sql = "SELECT * FROM materiel WHERE nom LIKE ? OR categorie LIKE ? OR disponibilite LIKE ? ORDER BY nom ASC";
        $stmt = $pdo->prepare($sql);
        $term = '%' . $keyword . '%';
        $stmt->execute([$term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
