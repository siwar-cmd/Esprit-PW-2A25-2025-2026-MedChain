<?php
require_once __DIR__ . '/../config/database.php';

class Pret {
    private $pdo;
    private $objetLoisir;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->objetLoisir = new ObjetLoisir();
    }

    public function creerPret($data) {
        if (!$this->objetLoisir->verifierDisponibilite($data['id_objet'])) {
            return false;
        }

        $sql = "INSERT INTO pret (id_objet, nom_patient, date_pret, date_retour_prevue, statut) 
                VALUES (:id_objet, :nom_patient, :date_pret, :date_retour_prevue, 'en_attente')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $data['id_objet'], PDO::PARAM_INT);
        $stmt->bindParam(':nom_patient', $data['nom_patient']);
        $stmt->bindParam(':date_pret', $data['date_pret']);
        $stmt->bindParam(':date_retour_prevue', $data['date_retour_prevue']);
        
        if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }

    public function confirmerPret($id_pret) {
        $this->pdo->beginTransaction();
        
        try {
            // Update loan status
            $sql = "UPDATE pret SET statut = 'en_cours' WHERE id_pret = :id_pret";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
            $stmt->execute();
            
            // Get object ID
            $sql = "SELECT id_objet FROM pret WHERE id_pret = :id_pret";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                // Check if this was the last available item
                $sql = "SELECT o.quantite, COUNT(p.id_pret) as active_loans
                        FROM objet_loisir o
                        LEFT JOIN pret p ON o.id_objet = p.id_objet AND p.statut IN ('en_cours', 'en_attente')
                        WHERE o.id_objet = :id_objet
                        GROUP BY o.id_objet, o.quantite";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_objet', $result['id_objet'], PDO::PARAM_INT);
                $stmt->execute();
                $count = $stmt->fetch();
                
                if ($count && $count['active_loans'] >= $count['quantite']) {
                    $this->objetLoisir->updateDisponibilite($result['id_objet'], 'indisponible');
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function annulerPret($id_pret) {
        $this->pdo->beginTransaction();
        
        try {
            // Get loan details before cancellation
            $sql = "SELECT id_objet FROM pret WHERE id_pret = :id_pret";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            
            // Update loan status
            $sql = "UPDATE pret SET statut = 'annule' WHERE id_pret = :id_pret";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($result) {
                // Update object availability
                $sql = "SELECT o.quantite, COUNT(p.id_pret) as active_loans
                        FROM objet_loisir o
                        LEFT JOIN pret p ON o.id_objet = p.id_objet AND p.statut IN ('en_cours', 'en_attente')
                        WHERE o.id_objet = :id_objet
                        GROUP BY o.id_objet, o.quantite";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_objet', $result['id_objet'], PDO::PARAM_INT);
                $stmt->execute();
                $count = $stmt->fetch();
                
                if ($count && $count['active_loans'] < $count['quantite']) {
                    $this->objetLoisir->updateDisponibilite($result['id_objet'], 'disponible');
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function retournerPret($id_pret) {
        $this->pdo->beginTransaction();
        
        try {
            // Get loan details before return
            $sql = "SELECT id_objet FROM pret WHERE id_pret = :id_pret";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            
            // Update loan status and return date
            $sql = "UPDATE pret SET statut = 'termine', date_retour_effective = CURDATE() WHERE id_pret = :id_pret";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($result) {
                // Update object availability
                $sql = "SELECT o.quantite, COUNT(p.id_pret) as active_loans
                        FROM objet_loisir o
                        LEFT JOIN pret p ON o.id_objet = p.id_objet AND p.statut IN ('en_cours', 'en_attente')
                        WHERE o.id_objet = :id_objet
                        GROUP BY o.id_objet, o.quantite";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_objet', $result['id_objet'], PDO::PARAM_INT);
                $stmt->execute();
                $count = $stmt->fetch();
                
                if ($count && $count['active_loans'] < $count['quantite']) {
                    $this->objetLoisir->updateDisponibilite($result['id_objet'], 'disponible');
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function afficherPret($id_pret) {
        $sql = "SELECT p.*, o.nom_objet, o.type_objet 
                FROM pret p 
                JOIN objet_loisir o ON p.id_objet = o.id_objet 
                WHERE p.id_pret = :id_pret";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getAllPrets() {
        $sql = "SELECT p.*, o.nom_objet, o.type_objet 
                FROM pret p 
                JOIN objet_loisir o ON p.id_objet = o.id_objet 
                ORDER BY p.date_pret DESC";
        
        $stmt = $this->pdo->query($sql);
        
        return $stmt->fetchAll();
    }

    public function getPretById($id_pret) {
        $sql = "SELECT p.*, o.nom_objet, o.type_objet 
                FROM pret p 
                JOIN objet_loisir o ON p.id_objet = o.id_objet 
                WHERE p.id_pret = :id_pret";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_pret', $id_pret, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getObjetsDisponibles() {
        $sql = "SELECT * FROM objet_loisir WHERE disponibilite = 'disponible' ORDER BY nom_objet";
        
        $stmt = $this->pdo->query($sql);
        
        return $stmt->fetchAll();
    }
}
