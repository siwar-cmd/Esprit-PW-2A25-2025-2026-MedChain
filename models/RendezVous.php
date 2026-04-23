<?php
class RendezVous {
    private $idRDV;
    private $dateHeureDebut;
    private $dateHeureFin;
    private $statut;
    private $typeConsultation;
    private $motif;

    public function __construct(
        $idRDV = null, $dateHeureDebut = null, $dateHeureFin = null,
        $statut = null, $typeConsultation = null, $motif = null
    ) {
        $this->idRDV            = $idRDV;
        $this->dateHeureDebut   = $dateHeureDebut;
        $this->dateHeureFin     = $dateHeureFin;
        $this->statut           = $statut;
        $this->typeConsultation = $typeConsultation;
        $this->motif            = $motif;
    }

    // --- Getters ---
    public function getIdRDV()            { return $this->idRDV; }
    public function getDateHeureDebut()   { return $this->dateHeureDebut; }
    public function getDateHeureFin()     { return $this->dateHeureFin; }
    public function getStatut()           { return $this->statut; }
    public function getTypeConsultation() { return $this->typeConsultation; }
    public function getMotif()            { return $this->motif; }

    // --- Setters ---
    public function setIdRDV($idRDV)                       { $this->idRDV            = $idRDV; }
    public function setDateHeureDebut($dateHeureDebut)     { $this->dateHeureDebut   = $dateHeureDebut; }
    public function setDateHeureFin($dateHeureFin)         { $this->dateHeureFin     = $dateHeureFin; }
    public function setStatut($statut)                     { $this->statut           = $statut; }
    public function setTypeConsultation($typeConsultation) { $this->typeConsultation = $typeConsultation; }
    public function setMotif($motif)                       { $this->motif            = $motif; }
}
?>
