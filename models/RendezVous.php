<?php
<<<<<<< HEAD

class RendezVous
{
    private $idRDV;
    private $dateHeureDebut;
    private $dateHeureFin;
    private $statut; // planifie, termine, annule
    private $typeConsultation;
    private $motif;
    private $idClient;
    private $idMedecin;

    public function __construct(
        $dateHeureDebut = null,
        $dateHeureFin = null,
        $statut = 'planifie',
        $typeConsultation = null,
        $motif = null,
        $idClient = null,
        $idMedecin = null,
        $idRDV = null
    ) {
        $this->idRDV = $idRDV;
        $this->dateHeureDebut = $dateHeureDebut;
        $this->dateHeureFin = $dateHeureFin;
        $this->statut = $statut;
        $this->typeConsultation = $typeConsultation;
        $this->motif = $motif;
        $this->idClient = $idClient;
        $this->idMedecin = $idMedecin;
    }

    // Getters
    public function getIdRDV() { return $this->idRDV; }
    public function getDateHeureDebut() { return $this->dateHeureDebut; }
    public function getDateHeureFin() { return $this->dateHeureFin; }
    public function getStatut() { return $this->statut; }
    public function getTypeConsultation() { return $this->typeConsultation; }
    public function getMotif() { return $this->motif; }
    public function getIdClient() { return $this->idClient; }
    public function getIdMedecin() { return $this->idMedecin; }

    // Setters
    public function setIdRDV($idRDV) { $this->idRDV = $idRDV; return $this; }
    public function setDateHeureDebut($dateHeureDebut) { $this->dateHeureDebut = $dateHeureDebut; return $this; }
    public function setDateHeureFin($dateHeureFin) { $this->dateHeureFin = $dateHeureFin; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setTypeConsultation($typeConsultation) { $this->typeConsultation = $typeConsultation; return $this; }
    public function setMotif($motif) { $this->motif = $motif; return $this; }
    public function setIdClient($idClient) { $this->idClient = $idClient; return $this; }
    public function setIdMedecin($idMedecin) { $this->idMedecin = $idMedecin; return $this; }

    public function toArray() {
        return [
            'idRDV' => $this->idRDV,
            'dateHeureDebut' => $this->dateHeureDebut,
            'dateHeureFin' => $this->dateHeureFin,
            'statut' => $this->statut,
            'typeConsultation' => $this->typeConsultation,
            'motif' => $this->motif,
            'idClient' => $this->idClient,
            'idMedecin' => $this->idMedecin
        ];
    }
}
=======
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
>>>>>>> 7801b75b753d80646e10602aa1365f908755f051
