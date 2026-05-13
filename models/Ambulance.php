<?php
class Ambulance {
    private $idAmbulance;
    private $immatriculation;
    private $statut;
    private $modele;
    private $capacite;
    private $estDisponible;
    private $lat;
    private $lng;

    public function __construct($idAmbulance = null, $immatriculation = null, $statut = null, $modele = null, $capacite = null, $estDisponible = null, $lat = null, $lng = null) {
        $this->idAmbulance    = $idAmbulance;
        $this->immatriculation = $immatriculation;
        $this->statut         = $statut;
        $this->modele         = $modele;
        $this->capacite       = $capacite;
        $this->estDisponible  = $estDisponible;
        $this->lat            = $lat;
        $this->lng            = $lng;
    }

    // --- Getters ---
    public function getIdAmbulance()    { return $this->idAmbulance; }
    public function getImmatriculation(){ return $this->immatriculation; }
    public function getStatut()         { return $this->statut; }
    public function getModele()         { return $this->modele; }
    public function getCapacite()       { return $this->capacite; }
    public function getEstDisponible()  { return $this->estDisponible; }
    public function getLat()            { return $this->lat; }
    public function getLng()            { return $this->lng; }

    // --- Setters ---
    public function setIdAmbulance($idAmbulance)       { $this->idAmbulance    = $idAmbulance; }
    public function setImmatriculation($immatriculation){ $this->immatriculation = $immatriculation; }
    public function setStatut($statut)                 { $this->statut         = $statut; }
    public function setModele($modele)                 { $this->modele         = $modele; }
    public function setCapacite($capacite)             { $this->capacite       = $capacite; }
    public function setEstDisponible($estDisponible)   { $this->estDisponible  = $estDisponible; }
    public function setLat($lat)                       { $this->lat            = $lat; }
    public function setLng($lng)                       { $this->lng            = $lng; }
}
?>
