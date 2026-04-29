<?php
class Ambulance {
    private $conn;
    private $table_name = "Ambulance";

    public $idAmbulance;
    public $immatriculation;
    public $statut;
    public $modele;
    public $capacite;
    public $estDisponible;
    
    // Tableau pour stocker les erreurs de validation
    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    // Méthode de validation complète
    public function validate() {
        $this->errors = [];
        
        // Validation de l'immatriculation
        if(empty($this->immatriculation)) {
            $this->errors['immatriculation'] = "L'immatriculation est obligatoire";
        } elseif(strlen($this->immatriculation) < 5) {
            $this->errors['immatriculation'] = "L'immatriculation doit contenir au moins 5 caractères";
        } elseif(strlen($this->immatriculation) > 20) {
            $this->errors['immatriculation'] = "L'immatriculation ne doit pas dépasser 20 caractères";
        } elseif(!preg_match('/^[A-Z0-9-]+$/i', $this->immatriculation)) {
            $this->errors['immatriculation'] = "L'immatriculation ne doit contenir que des lettres, chiffres et tirets";
        }
        
        // Validation du modèle
        if(empty($this->modele)) {
            $this->errors['modele'] = "Le modèle est obligatoire";
        } elseif(strlen($this->modele) < 2) {
            $this->errors['modele'] = "Le modèle doit contenir au moins 2 caractères";
        } elseif(strlen($this->modele) > 50) {
            $this->errors['modele'] = "Le modèle ne doit pas dépasser 50 caractères";
        } elseif(!preg_match('/^[a-zA-Z0-9\s\-]+$/', $this->modele)) {
            $this->errors['modele'] = "Le modèle ne doit contenir que des lettres, chiffres, espaces et tirets";
        }
        
        // Validation du statut
        $validStatus = ['En service', 'En maintenance', 'Hors service'];
        if(empty($this->statut)) {
            $this->errors['statut'] = "Le statut est obligatoire";
        } elseif(!in_array($this->statut, $validStatus)) {
            $this->errors['statut'] = "Le statut doit être : " . implode(', ', $validStatus);
        }
        
        // Validation de la capacité
        if(empty($this->capacite)) {
            $this->errors['capacite'] = "La capacité est obligatoire";
        } elseif(!is_numeric($this->capacite)) {
            $this->errors['capacite'] = "La capacité doit être un nombre";
        } elseif($this->capacite < 1) {
            $this->errors['capacite'] = "La capacité doit être au moins 1 place";
        } elseif($this->capacite > 20) {
            $this->errors['capacite'] = "La capacité ne doit pas dépasser 20 places";
        } elseif(!ctype_digit((string)$this->capacite)) {
            $this->errors['capacite'] = "La capacité doit être un nombre entier";
        }
        
        // Validation de la disponibilité
        if(!isset($this->estDisponible) || ($this->estDisponible != 0 && $this->estDisponible != 1)) {
            $this->estDisponible = 0;
        }
        
        // Vérifier si l'immatriculation existe déjà (pour la création)
        if(empty($this->idAmbulance)) { // En création seulement
            if($this->immatriculationExists()) {
                $this->errors['immatriculation'] = "Cette immatriculation existe déjà dans la base de données";
            }
        } else { // En modification, vérifier si l'immatriculation n'appartient pas à une autre ambulance
            if($this->immatriculationExistsForOther()) {
                $this->errors['immatriculation'] = "Cette immatriculation est déjà utilisée par une autre ambulance";
            }
        }
        
        return empty($this->errors);
    }
    
    // Vérifier si l'immatriculation existe déjà
    private function immatriculationExists() {
        $query = "SELECT idAmbulance FROM " . $this->table_name . " WHERE immatriculation = :immatriculation";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':immatriculation', $this->immatriculation);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    // Vérifier si l'immatriculation existe pour une autre ambulance
    private function immatriculationExistsForOther() {
        $query = "SELECT idAmbulance FROM " . $this->table_name . " 
                  WHERE immatriculation = :immatriculation AND idAmbulance != :idAmbulance";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':immatriculation', $this->immatriculation);
        $stmt->bindParam(':idAmbulance', $this->idAmbulance);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    // Nettoyer les données
    public function sanitize() {
        $this->immatriculation = trim(htmlspecialchars(strip_tags($this->immatriculation)));
        $this->modele = trim(htmlspecialchars(strip_tags($this->modele)));
        $this->statut = trim(htmlspecialchars(strip_tags($this->statut)));
        $this->capacite = filter_var($this->capacite, FILTER_VALIDATE_INT);
        
        // Mettre en majuscules l'immatriculation
        $this->immatriculation = strtoupper($this->immatriculation);
        
        // Capitaliser la première lettre du modèle
        $this->modele = ucwords($this->modele);
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY idAmbulance DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idAmbulance = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idAmbulance);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->immatriculation = $row['immatriculation'];
            $this->statut = $row['statut'];
            $this->modele = $row['modele'];
            $this->capacite = $row['capacite'];
            $this->estDisponible = $row['estDisponible'];
            return true;
        }
        return false;
    }

    public function create() {
        // Nettoyer et valider
        $this->sanitize();
        
        if(!$this->validate()) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET immatriculation=:immatriculation, 
                      statut=:statut, 
                      modele=:modele, 
                      capacite=:capacite, 
                      estDisponible=:estDisponible";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":immatriculation", $this->immatriculation);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":modele", $this->modele);
        $stmt->bindParam(":capacite", $this->capacite);
        $stmt->bindParam(":estDisponible", $this->estDisponible);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function update() {
        // Nettoyer et valider
        $this->sanitize();
        
        if(!$this->validate()) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . "
                  SET immatriculation = :immatriculation,
                      statut = :statut,
                      modele = :modele,
                      capacite = :capacite,
                      estDisponible = :estDisponible
                  WHERE idAmbulance = :idAmbulance";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":immatriculation", $this->immatriculation);
        $stmt->bindParam(":statut", $this->statut);
        $stmt->bindParam(":modele", $this->modele);
        $stmt->bindParam(":capacite", $this->capacite);
        $stmt->bindParam(":estDisponible", $this->estDisponible);
        $stmt->bindParam(":idAmbulance", $this->idAmbulance);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        // Vérifier si l'ambulance a des missions avant de supprimer
        if($this->hasMissions()) {
            $this->errors['general'] = "Impossible de supprimer cette ambulance car elle a des missions associées";
            return false;
        }
        
        $query = "DELETE FROM " . $this->table_name . " WHERE idAmbulance = ?";
        $stmt = $this->conn->prepare($query);
        $this->idAmbulance = htmlspecialchars(strip_tags($this->idAmbulance));
        $stmt->bindParam(1, $this->idAmbulance);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    // Vérifier si l'ambulance a des missions
    private function hasMissions() {
        $query = "SELECT idMission FROM Mission WHERE idAmbulance = :idAmbulance LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idAmbulance', $this->idAmbulance);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE immatriculation LIKE ? OR modele LIKE ? OR statut LIKE ?
                  ORDER BY idAmbulance DESC";

        $stmt = $this->conn->prepare($query);
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        $stmt->execute();
        return $stmt;
    }
    
    // Récupérer les erreurs
    public function getErrors() {
        return $this->errors;
    }
    
    // Statistiques
    public function getStats() {
        $stats = [];
        // Par statut
        $query1 = "SELECT statut, COUNT(idAmbulance) as total FROM " . $this->table_name . " GROUP BY statut";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['status_count'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        // Par disponibilité
        $query2 = "SELECT estDisponible, COUNT(idAmbulance) as total FROM " . $this->table_name . " GROUP BY estDisponible";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        
        // Formater proprement
        $stats['dispo_count'] = ['dispo' => 0, 'indispo' => 0];
        while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            if($row['estDisponible'] == 1) $stats['dispo_count']['dispo'] = $row['total'];
            else $stats['dispo_count']['indispo'] = $row['total'];
        }
        
        return $stats;
    }
}
?>