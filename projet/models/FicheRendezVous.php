<?php

class FicheRendezVous
{
    private $idFiche;
    private $idRDV;
    private $dateGeneration;
    private $piecesAApporter;
    private $consignesAvantConsultation;
    private $tarifConsultation;
    private $modeRemboursement;
    private $emailEnvoye;
    private $calendrierAjoute;

    public function __construct(
        $idRDV = null,
        $dateGeneration = null,
        $piecesAApporter = null,
        $consignesAvantConsultation = null,
        $tarifConsultation = null,
        $modeRemboursement = null,
        $emailEnvoye = 0,
        $calendrierAjoute = 0,
        $idFiche = null
    ) {
        $this->idFiche = $idFiche;
        $this->idRDV = $idRDV;
        $this->dateGeneration = $dateGeneration;
        $this->piecesAApporter = $piecesAApporter;
        $this->consignesAvantConsultation = $consignesAvantConsultation;
        $this->tarifConsultation = $tarifConsultation;
        $this->modeRemboursement = $modeRemboursement;
        $this->emailEnvoye = $emailEnvoye;
        $this->calendrierAjoute = $calendrierAjoute;
    }

    // Getters
    public function getIdFiche() { return $this->idFiche; }
    public function getIdRDV() { return $this->idRDV; }
    public function getDateGeneration() { return $this->dateGeneration; }
    public function getPiecesAApporter() { return $this->piecesAApporter; }
    public function getConsignesAvantConsultation() { return $this->consignesAvantConsultation; }
    public function getTarifConsultation() { return $this->tarifConsultation; }
    public function getModeRemboursement() { return $this->modeRemboursement; }
    public function getEmailEnvoye() { return $this->emailEnvoye; }
    public function getCalendrierAjoute() { return $this->calendrierAjoute; }

    // Setters
    public function setIdFiche($idFiche) { $this->idFiche = $idFiche; return $this; }
    public function setIdRDV($idRDV) { $this->idRDV = $idRDV; return $this; }
    public function setDateGeneration($dateGeneration) { $this->dateGeneration = $dateGeneration; return $this; }
    public function setPiecesAApporter($piecesAApporter) { $this->piecesAApporter = $piecesAApporter; return $this; }
    public function setConsignesAvantConsultation($consignesAvantConsultation) { $this->consignesAvantConsultation = $consignesAvantConsultation; return $this; }
    public function setTarifConsultation($tarifConsultation) { $this->tarifConsultation = $tarifConsultation; return $this; }
    public function setModeRemboursement($modeRemboursement) { $this->modeRemboursement = $modeRemboursement; return $this; }
    public function setEmailEnvoye($emailEnvoye) { $this->emailEnvoye = $emailEnvoye; return $this; }
    public function setCalendrierAjoute($calendrierAjoute) { $this->calendrierAjoute = $calendrierAjoute; return $this; }

    public function toArray() {
        return [
            'idFiche' => $this->idFiche,
            'idRDV' => $this->idRDV,
            'dateGeneration' => $this->dateGeneration,
            'piecesAApporter' => $this->piecesAApporter,
            'consignesAvantConsultation' => $this->consignesAvantConsultation,
            'tarifConsultation' => $this->tarifConsultation,
            'modeRemboursement' => $this->modeRemboursement,
            'emailEnvoye' => $this->emailEnvoye,
            'calendrierAjoute' => $this->calendrierAjoute
        ];
    }
}
