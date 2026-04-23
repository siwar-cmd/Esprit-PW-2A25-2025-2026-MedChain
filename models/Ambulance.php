<?php
class Ambulance {
    private $idAmbulance;
    private $immatriculation;
    private $statut;
    private $modele;
    private $capacite;
    private $estDisponible;

    public function __construct($idAmbulance = null, $immatriculation = null, $statut = null, $modele = null, $capacite = null, $estDisponible = null) {
        $this->idAmbulance    = $idAmbulance;
        $this->immatriculation = $immatriculation;
        $this->statut         = $statut;
        $this->modele         = $modele;
        $this->capacite       = $capacite;
        $this->estDisponible  = $estDisponible;
    }

    // --- Getters ---
    public function getIdAmbulance()    { return $this->idAmbulance; }
    public function getImmatriculation(){ return $this->immatriculation; }
    public function getStatut()         { return $this->statut; }
    public function getModele()         { return $this->modele; }
    public function getCapacite()       { return $this->capacite; }
    public function getEstDisponible()  { return $this->estDisponible; }

    // --- Setters ---
    public function setIdAmbulance($idAmbulance)       { $this->idAmbulance    = $idAmbulance; }
    public function setImmatriculation($immatriculation){ $this->immatriculation = $immatriculation; }
    public function setStatut($statut)                 { $this->statut         = $statut; }
    public function setModele($modele)                 { $this->modele         = $modele; }
    public function setCapacite($capacite)             { $this->capacite       = $capacite; }
    public function setEstDisponible($estDisponible)   { $this->estDisponible  = $estDisponible; }
}
?>