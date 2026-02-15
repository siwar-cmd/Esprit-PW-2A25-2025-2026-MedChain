<?php
class MaterielChirurgical {
    private $conn;
    private $table_name = "MaterielChirurgical";

    public $idMateriel;
    public $nom;
    public $categorie;
    public $disponibilite;
    public $statutSterilisation;
    public $nombreUtilisationsMax;
    public $nombreUtilisationsActuelles;

    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        if(empty($this->nom)) $this->errors['nom'] = "Le nom est obligatoire";
        if(empty($this->categorie)) $this->errors['categorie'] = "La catégorie est obligatoire";
        if(empty($this->nombreUtilisationsMax) || !is_numeric($this->nombreUtilisationsMax)) {
            $this->errors['nombreUtilisationsMax'] = "Nb max d'utilisations invalide";
        }
        return empty($this->errors);
    }

    public function sanitize() {
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->categorie = htmlspecialchars(strip_tags($this->categorie));
        $this->disponibilite = htmlspecialchars(strip_tags($this->disponibilite));
        $this->statutSterilisation = htmlspecialchars(strip_tags($this->statutSterilisation));
        $this->nombreUtilisationsMax = filter_var($this->nombreUtilisationsMax, FILTER_VALIDATE_INT);
        $this->nombreUtilisationsActuelles = filter_var($this->nombreUtilisationsActuelles, FILTER_VALIDATE_INT) ?: 0;
    }

    public function read() {
        // We can fetch data, and simultaneously call the SQL functions for display.
        $query = "SELECT m.*, estSterile(m.idMateriel) as sterile_calc, estUtilisable(m.idMateriel) as utilisable_calc 
                  FROM " . $this->table_name . " m ORDER BY m.idMateriel DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idMateriel = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idMateriel);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->nom = $row['nom'];
            $this->categorie = $row['categorie'];
            $this->disponibilite = $row['disponibilite'];
            $this->statutSterilisation = $row['statutSterilisation'];
            $this->nombreUtilisationsMax = $row['nombreUtilisationsMax'];
            $this->nombreUtilisationsActuelles = $row['nombreUtilisationsActuelles'];
            return true;
        }
        return false;
    }

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET nom=:nom, categorie=:categorie, disponibilite=:disponibilite, 
                      statutSterilisation=:statutSterilisation, nombreUtilisationsMax=:nombreUtilisationsMax, 
                      nombreUtilisationsActuelles=:nombreUtilisationsActuelles";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":categorie", $this->categorie);
        $stmt->bindParam(":disponibilite", $this->disponibilite);
        $stmt->bindParam(":statutSterilisation", $this->statutSterilisation);
        $stmt->bindParam(":nombreUtilisationsMax", $this->nombreUtilisationsMax);
        $stmt->bindParam(":nombreUtilisationsActuelles", $this->nombreUtilisationsActuelles);

        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "UPDATE " . $this->table_name . "
                  SET nom=:nom, categorie=:categorie, disponibilite=:disponibilite, 
                      statutSterilisation=:statutSterilisation, nombreUtilisationsMax=:nombreUtilisationsMax, 
                      nombreUtilisationsActuelles=:nombreUtilisationsActuelles
                  WHERE idMateriel = :idMateriel";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":categorie", $this->categorie);
        $stmt->bindParam(":disponibilite", $this->disponibilite);
        $stmt->bindParam(":statutSterilisation", $this->statutSterilisation);
        $stmt->bindParam(":nombreUtilisationsMax", $this->nombreUtilisationsMax);
        $stmt->bindParam(":nombreUtilisationsActuelles", $this->nombreUtilisationsActuelles);
        $stmt->bindParam(":idMateriel", $this->idMateriel);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idMateriel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idMateriel);
        return $stmt->execute();
    }

    public function getStats() {
        $stats = [];
        
        $query1 = "SELECT disponibilite, COUNT(idMateriel) as total 
                   FROM " . $this->table_name . " 
                   GROUP BY disponibilite";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['disponibilites'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        $query2 = "SELECT statutSterilisation, COUNT(idMateriel) as total 
                   FROM " . $this->table_name . " 
                   GROUP BY statutSterilisation";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $stats['sterilisations'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    public function getErrors() {
        return $this->errors;
    }
}
?>
