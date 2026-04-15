<?php
require_once __DIR__ . '/../config/database.php';

class ObjetLoisir {
    private $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }

    public function ajouterObjet($data) {
        // Auto-set availability based on quantity
        $disponibilite = ($data['quantite'] > 0) ? 'disponible' : 'indisponible';
        
        $sql = "INSERT INTO objet_loisir (nom_objet, type_objet, quantite, etat, disponibilite, description) 
                VALUES (:nom_objet, :type_objet, :quantite, :etat, :disponibilite, :description)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':nom_objet', $data['nom_objet']);
        $stmt->bindParam(':type_objet', $data['type_objet']);
        $stmt->bindParam(':quantite', $data['quantite'], PDO::PARAM_INT);
        $stmt->bindParam(':etat', $data['etat']);
        $stmt->bindParam(':disponibilite', $disponibilite);
        $stmt->bindParam(':description', $data['description']);
        
        return $stmt->execute();
    }

    public function modifierObjet($id_objet, $data) {
        // Auto-set availability based on quantity
        $disponibilite = ($data['quantite'] > 0) ? 'disponible' : 'indisponible';
        
        $sql = "UPDATE objet_loisir 
                SET nom_objet = :nom_objet, 
                    type_objet = :type_objet, 
                    quantite = :quantite, 
                    etat = :etat, 
                    disponibilite = :disponibilite, 
                    description = :description 
                WHERE id_objet = :id_objet";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $id_objet, PDO::PARAM_INT);
        $stmt->bindParam(':nom_objet', $data['nom_objet']);
        $stmt->bindParam(':type_objet', $data['type_objet']);
        $stmt->bindParam(':quantite', $data['quantite'], PDO::PARAM_INT);
        $stmt->bindParam(':etat', $data['etat']);
        $stmt->bindParam(':disponibilite', $disponibilite);
        $stmt->bindParam(':description', $data['description']);
        
        return $stmt->execute();
    }

    public function supprimerObjet($id_objet) {
        $sql = "DELETE FROM objet_loisir WHERE id_objet = :id_objet";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $id_objet, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function afficherObjet($id_objet) {
        $sql = "SELECT * FROM objet_loisir WHERE id_objet = :id_objet";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $id_objet, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getAllObjets() {
        $sql = "SELECT * FROM objet_loisir ORDER BY id_objet DESC";
        
        $stmt = $this->pdo->query($sql);
        
        return $stmt->fetchAll();
    }

    public function getObjetById($id_objet) {
        $sql = "SELECT * FROM objet_loisir WHERE id_objet = :id_objet";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $id_objet, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function verifierDisponibilite($id_objet) {
        $sql = "SELECT disponibilite FROM objet_loisir WHERE id_objet = :id_objet";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $id_objet, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result && $result['disponibilite'] === 'disponible';
    }

    public function updateDisponibilite($id_objet, $disponibilite) {
        $sql = "UPDATE objet_loisir SET disponibilite = :disponibilite WHERE id_objet = :id_objet";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_objet', $id_objet, PDO::PARAM_INT);
        $stmt->bindParam(':disponibilite', $disponibilite);
        
        return $stmt->execute();
    }

    public function getObjetsDisponibles() {
        $sql = "SELECT * FROM objet_loisir WHERE disponibilite = 'disponible' ORDER BY nom_objet";
        
        $stmt = $this->pdo->query($sql);
        
        return $stmt->fetchAll();
    }
}
