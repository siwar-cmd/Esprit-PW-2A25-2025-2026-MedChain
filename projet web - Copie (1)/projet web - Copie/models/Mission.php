<?php
class Mission {
    private $conn;
    private $table_name = "Mission";

    public $idMission;
    public $idAmbulance;
    public $dateDebut;
    public $dateFin;
    public $typeMission;
    public $lieuDepart;
    public $lieuArrivee;
    public $equipe;
    public $estTerminee;
    
    // Jointure
    public $immatriculation_ambulance;

    public $errors = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validate() {
        $this->errors = [];
        
        // Validation idAmbulance
        if(empty($this->idAmbulance)) {
            $this->errors['idAmbulance'] = "L'ambulance est obligatoire";
        }
        
        // Validation dateDebut
        if(empty($this->dateDebut)) {
            $this->errors['dateDebut'] = "La date de début est obligatoire";
        }
        
        // Validation typeMission
        if(empty($this->typeMission)) {
            $this->errors['typeMission'] = "Le type de mission est obligatoire";
        }
        
        // Validation lieux
        if(empty($this->lieuDepart)) {
            $this->errors['lieuDepart'] = "Le lieu de départ est obligatoire";
        }
        if(empty($this->lieuArrivee)) {
            $this->errors['lieuArrivee'] = "Le lieu d'arrivée est obligatoire";
        }
        
        // Validation de cohérence des dates
        if(!empty($this->dateDebut) && !empty($this->dateFin)) {
            if(strtotime($this->dateFin) < strtotime($this->dateDebut)) {
                $this->errors['dateFin'] = "La date de fin ne peut pas précéder la date de début";
            }
        }
        
        // Vérification de la disponibilité de l'ambulance (pas de mission non terminée en même temps)
        if(empty($this->errors) && empty($this->estTerminee)) {
            if($this->isAmbulanceBusy()) {
                $this->errors['idAmbulance'] = "Cette ambulance est déjà en mission à ces dates";
            }
        }

        return empty($this->errors);
    }
    
    private function isAmbulanceBusy() {
        // En création (idMission vide) ou modification (idMission rempli)
        $query = "SELECT idMission FROM " . $this->table_name . " 
                  WHERE idAmbulance = :idAmbulance 
                  AND estTerminee = 0";
                  
        if(!empty($this->idMission)) {
            $query .= " AND idMission != :idMission";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idAmbulance', $this->idAmbulance);
        if(!empty($this->idMission)) {
            $stmt->bindParam(':idMission', $this->idMission);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function sanitize() {
        $this->idAmbulance = htmlspecialchars(strip_tags($this->idAmbulance));
        $this->dateDebut = htmlspecialchars(strip_tags($this->dateDebut));
        $this->dateFin = htmlspecialchars(strip_tags($this->dateFin));
        $this->typeMission = htmlspecialchars(strip_tags($this->typeMission));
        $this->lieuDepart = htmlspecialchars(strip_tags($this->lieuDepart));
        $this->lieuArrivee = htmlspecialchars(strip_tags($this->lieuArrivee));
        $this->equipe = htmlspecialchars(strip_tags($this->equipe));
        
        if($this->dateFin == "") {
            $this->dateFin = null;
        }
    }

    public function read() {
        $query = "SELECT m.*, a.immatriculation as immatriculation_ambulance 
                  FROM " . $this->table_name . " m
                  LEFT JOIN Ambulance a ON m.idAmbulance = a.idAmbulance 
                  ORDER BY m.idMission DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE idMission = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idMission);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->idAmbulance = $row['idAmbulance'];
            $this->dateDebut = $row['dateDebut'];
            $this->dateFin = $row['dateFin'];
            $this->typeMission = $row['typeMission'];
            $this->lieuDepart = $row['lieuDepart'];
            $this->lieuArrivee = $row['lieuArrivee'];
            $this->equipe = $row['equipe'];
            $this->estTerminee = $row['estTerminee'];
            return true;
        }
        return false;
    }

    public function create() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "INSERT INTO " . $this->table_name . "
                  SET idAmbulance=:idAmbulance, dateDebut=:dateDebut, dateFin=:dateFin, 
                      typeMission=:typeMission, lieuDepart=:lieuDepart, lieuArrivee=:lieuArrivee, 
                      equipe=:equipe, estTerminee=:estTerminee";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":idAmbulance", $this->idAmbulance);
        $stmt->bindParam(":dateDebut", $this->dateDebut);
        $stmt->bindValue(":dateFin", $this->dateFin, empty($this->dateFin) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":typeMission", $this->typeMission);
        $stmt->bindParam(":lieuDepart", $this->lieuDepart);
        $stmt->bindParam(":lieuArrivee", $this->lieuArrivee);
        $stmt->bindParam(":equipe", $this->equipe);
        $stmt->bindParam(":estTerminee", $this->estTerminee);

        return $stmt->execute();
    }

    public function update() {
        $this->sanitize();
        if(!$this->validate()) return false;
        
        $query = "UPDATE " . $this->table_name . "
                  SET idAmbulance=:idAmbulance, dateDebut=:dateDebut, dateFin=:dateFin, 
                      typeMission=:typeMission, lieuDepart=:lieuDepart, lieuArrivee=:lieuArrivee, 
                      equipe=:equipe, estTerminee=:estTerminee
                  WHERE idMission = :idMission";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":idAmbulance", $this->idAmbulance);
        $stmt->bindParam(":dateDebut", $this->dateDebut);
        $stmt->bindValue(":dateFin", $this->dateFin, empty($this->dateFin) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":typeMission", $this->typeMission);
        $stmt->bindParam(":lieuDepart", $this->lieuDepart);
        $stmt->bindParam(":lieuArrivee", $this->lieuArrivee);
        $stmt->bindParam(":equipe", $this->equipe);
        $stmt->bindParam(":estTerminee", $this->estTerminee);
        $stmt->bindParam(":idMission", $this->idMission);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE idMission = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->idMission);
        return $stmt->execute();
    }
    
    // Récupérer des statistiques
    public function getStats() {
        $stats = [];
        // Nombre de missions par ambulance
        $query1 = "SELECT a.immatriculation, COUNT(m.idMission) as total 
                   FROM " . $this->table_name . " m 
                   JOIN Ambulance a ON m.idAmbulance = a.idAmbulance 
                   GROUP BY m.idAmbulance LIMIT 5";
        $stmt1 = $this->conn->prepare($query1);
        $stmt1->execute();
        $stats['top_ambulances'] = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        // Status des missions (Terminées vs En cours)
        $query2 = "SELECT 
                   SUM(CASE WHEN estTerminee = 1 THEN 1 ELSE 0 END) as terminees,
                   SUM(CASE WHEN estTerminee = 0 THEN 1 ELSE 0 END) as en_cours
                   FROM " . $this->table_name;
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->execute();
        $stats['status_missions'] = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    public function getErrors() {
        return $this->errors;
    }
}
?>
