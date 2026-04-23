<?php
class FicheRendezVous {
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
        $idFiche = null, $idRDV = null, $dateGeneration = null, $piecesAApporter = null,
        $consignesAvantConsultation = null, $tarifConsultation = null,
        $modeRemboursement = null, $emailEnvoye = null, $calendrierAjoute = null
    ) {
        $this->idFiche                    = $idFiche;
        $this->idRDV                      = $idRDV;
        $this->dateGeneration             = $dateGeneration;
        $this->piecesAApporter            = $piecesAApporter;
        $this->consignesAvantConsultation = $consignesAvantConsultation;
        $this->tarifConsultation          = $tarifConsultation;
        $this->modeRemboursement          = $modeRemboursement;
        $this->emailEnvoye                = $emailEnvoye;
        $this->calendrierAjoute           = $calendrierAjoute;
    }

    // --- Getters ---
    public function getIdFiche()                    { return $this->idFiche; }
    public function getIdRDV()                      { return $this->idRDV; }
    public function getDateGeneration()             { return $this->dateGeneration; }
    public function getPiecesAApporter()            { return $this->piecesAApporter; }
    public function getConsignesAvantConsultation() { return $this->consignesAvantConsultation; }
    public function getTarifConsultation()          { return $this->tarifConsultation; }
    public function getModeRemboursement()          { return $this->modeRemboursement; }
    public function getEmailEnvoye()                { return $this->emailEnvoye; }
    public function getCalendrierAjoute()           { return $this->calendrierAjoute; }

    // --- Setters ---
    public function setIdFiche($idFiche)                                       { $this->idFiche                    = $idFiche; }
    public function setIdRDV($idRDV)                                           { $this->idRDV                      = $idRDV; }
    public function setDateGeneration($dateGeneration)                         { $this->dateGeneration             = $dateGeneration; }
    public function setPiecesAApporter($piecesAApporter)                       { $this->piecesAApporter            = $piecesAApporter; }
    public function setConsignesAvantConsultation($consignesAvantConsultation) { $this->consignesAvantConsultation = $consignesAvantConsultation; }
    public function setTarifConsultation($tarifConsultation)                   { $this->tarifConsultation          = $tarifConsultation; }
    public function setModeRemboursement($modeRemboursement)                   { $this->modeRemboursement          = $modeRemboursement; }
    public function setEmailEnvoye($emailEnvoye)                               { $this->emailEnvoye                = $emailEnvoye; }
    public function setCalendrierAjoute($calendrierAjoute)                     { $this->calendrierAjoute           = $calendrierAjoute; }
}
?>
