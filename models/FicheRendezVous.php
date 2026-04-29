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
    private $antecedents;
    private $allergies;
    private $motifPrincipal;
    private $modeConsultation;
    private $statutPaiement;

    public function __construct(
        $idRDV = null,
        $dateGeneration = null,
        $piecesAApporter = null,
        $consignesAvantConsultation = null,
        $tarifConsultation = null,
        $modeRemboursement = null,
        $emailEnvoye = 0,
        $calendrierAjoute = 0,
        $idFiche = null,
        $antecedents = null,
        $allergies = null,
        $motifPrincipal = null,
        $modeConsultation = 'Présentiel',
        $statutPaiement = 'En attente'
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
        $this->antecedents = $antecedents;
        $this->allergies = $allergies;
        $this->motifPrincipal = $motifPrincipal;
        $this->modeConsultation = $modeConsultation;
        $this->statutPaiement = $statutPaiement;
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
    public function getAntecedents() { return $this->antecedents; }
    public function getAllergies() { return $this->allergies; }
    public function getMotifPrincipal() { return $this->motifPrincipal; }
    public function getModeConsultation() { return $this->modeConsultation; }
    public function getStatutPaiement() { return $this->statutPaiement; }

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
    public function setAntecedents($antecedents) { $this->antecedents = $antecedents; return $this; }
    public function setAllergies($allergies) { $this->allergies = $allergies; return $this; }
    public function setMotifPrincipal($motifPrincipal) { $this->motifPrincipal = $motifPrincipal; return $this; }
    public function setModeConsultation($modeConsultation) { $this->modeConsultation = $modeConsultation; return $this; }
    public function setStatutPaiement($statutPaiement) { $this->statutPaiement = $statutPaiement; return $this; }

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
            'calendrierAjoute' => $this->calendrierAjoute,
            'antecedents' => $this->antecedents,
            'allergies' => $this->allergies,
            'motifPrincipal' => $this->motifPrincipal,
            'modeConsultation' => $this->modeConsultation,
            'statutPaiement' => $this->statutPaiement
        ];
    }
}
