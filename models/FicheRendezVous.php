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
    private $tensionArterielle;
    private $poids;
    private $taille;
    private $temperature;
    private $examenClinique;
    private $diagnostic;
    private $prescription;
    private $examensComplementaires;
    private $observations;
    private $prochainRDV;

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
        $statutPaiement = 'En attente',
        $tensionArterielle = null,
        $poids = null,
        $taille = null,
        $temperature = null,
        $examenClinique = null,
        $diagnostic = null,
        $prescription = null,
        $examensComplementaires = null,
        $observations = null,
        $prochainRDV = null
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
        $this->tensionArterielle = $tensionArterielle;
        $this->poids = $poids;
        $this->taille = $taille;
        $this->temperature = $temperature;
        $this->examenClinique = $examenClinique;
        $this->diagnostic = $diagnostic;
        $this->prescription = $prescription;
        $this->examensComplementaires = $examensComplementaires;
        $this->observations = $observations;
        $this->prochainRDV = $prochainRDV;
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
    public function getTensionArterielle() { return $this->tensionArterielle; }
    public function getPoids() { return $this->poids; }
    public function getTaille() { return $this->taille; }
    public function getTemperature() { return $this->temperature; }
    public function getExamenClinique() { return $this->examenClinique; }
    public function getDiagnostic() { return $this->diagnostic; }
    public function getPrescription() { return $this->prescription; }
    public function getExamensComplementaires() { return $this->examensComplementaires; }
    public function getObservations() { return $this->observations; }
    public function getProchainRDV() { return $this->prochainRDV; }

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
    public function setTensionArterielle($tensionArterielle) { $this->tensionArterielle = $tensionArterielle; return $this; }
    public function setPoids($poids) { $this->poids = $poids; return $this; }
    public function setTaille($taille) { $this->taille = $taille; return $this; }
    public function setTemperature($temperature) { $this->temperature = $temperature; return $this; }
    public function setExamenClinique($examenClinique) { $this->examenClinique = $examenClinique; return $this; }
    public function setDiagnostic($diagnostic) { $this->diagnostic = $diagnostic; return $this; }
    public function setPrescription($prescription) { $this->prescription = $prescription; return $this; }
    public function setExamensComplementaires($examensComplementaires) { $this->examensComplementaires = $examensComplementaires; return $this; }
    public function setObservations($observations) { $this->observations = $observations; return $this; }
    public function setProchainRDV($prochainRDV) { $this->prochainRDV = $prochainRDV; return $this; }

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
            'statutPaiement' => $this->statutPaiement,
            'tensionArterielle' => $this->tensionArterielle,
            'poids' => $this->poids,
            'taille' => $this->taille,
            'temperature' => $this->temperature,
            'examenClinique' => $this->examenClinique,
            'diagnostic' => $this->diagnostic,
            'prescription' => $this->prescription,
            'examensComplementaires' => $this->examensComplementaires,
            'observations' => $this->observations,
            'prochainRDV' => $this->prochainRDV
        ];
    }
}
