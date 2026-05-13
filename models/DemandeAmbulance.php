<?php

class DemandeAmbulance
{
    private $idDemande;
    private $typeMission;
    private $lieuDepart;
    private $lieuArrivee;
    private $dateHeure;
    private $remarques;
    private $statut;
    private $idMedecin;
    private $idMission;
    private $dateCreation;

    public function __construct(
        $typeMission = "",
        $lieuDepart = "",
        $lieuArrivee = "",
        $dateHeure = "",
        $idMedecin = 0,
        $remarques = null,
        $statut = "en attente",
        $idMission = null
    ) {
        $this->typeMission = $typeMission;
        $this->lieuDepart = $lieuDepart;
        $this->lieuArrivee = $lieuArrivee;
        $this->dateHeure = $dateHeure;
        $this->idMedecin = $idMedecin;
        $this->remarques = $remarques;
        $this->statut = $statut;
        $this->idMission = $idMission;
    }

    // Getters
    public function getIdDemande() { return $this->idDemande; }
    public function getTypeMission() { return $this->typeMission; }
    public function getLieuDepart() { return $this->lieuDepart; }
    public function getLieuArrivee() { return $this->lieuArrivee; }
    public function getDateHeure() { return $this->dateHeure; }
    public function getRemarques() { return $this->remarques; }
    public function getStatut() { return $this->statut; }
    public function getIdMedecin() { return $this->idMedecin; }
    public function getIdMission() { return $this->idMission; }
    public function getDateCreation() { return $this->dateCreation; }

    // Setters
    public function setIdDemande($id) { $this->idDemande = $id; return $this; }
    public function setTypeMission($type) { $this->typeMission = $type; return $this; }
    public function setLieuDepart($lieu) { $this->lieuDepart = $lieu; return $this; }
    public function setLieuArrivee($lieu) { $this->lieuArrivee = $lieu; return $this; }
    public function setDateHeure($date) { $this->dateHeure = $date; return $this; }
    public function setRemarques($rem) { $this->remarques = $rem; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setIdMedecin($id) { $this->idMedecin = $id; return $this; }
    public function setIdMission($id) { $this->idMission = $id; return $this; }
    public function setDateCreation($date) { $this->dateCreation = $date; return $this; }
}
