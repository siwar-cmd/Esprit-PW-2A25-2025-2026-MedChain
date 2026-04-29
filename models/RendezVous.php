<?php

class RendezVous
{
    private $idRDV;
    private $dateHeureDebut;
    private $statut; // planifie, termine, annule
    private $typeConsultation;
    private $motif;
    private $idClient;
    private $idMedecin;

    public function __construct(
        $dateHeureDebut = null,
        $statut = 'planifie',
        $typeConsultation = null,
        $motif = null,
        $idClient = null,
        $idMedecin = null,
        $idRDV = null
    ) {
        $this->idRDV = $idRDV;
        $this->dateHeureDebut = $dateHeureDebut;
        $this->statut = $statut;
        $this->typeConsultation = $typeConsultation;
        $this->motif = $motif;
        $this->idClient = $idClient;
        $this->idMedecin = $idMedecin;
    }

    // Getters
    public function getIdRDV() { return $this->idRDV; }
    public function getDateHeureDebut() { return $this->dateHeureDebut; }
    public function getStatut() { return $this->statut; }
    public function getTypeConsultation() { return $this->typeConsultation; }
    public function getMotif() { return $this->motif; }
    public function getIdClient() { return $this->idClient; }
    public function getIdMedecin() { return $this->idMedecin; }

    // Setters
    public function setIdRDV($idRDV) { $this->idRDV = $idRDV; return $this; }
    public function setDateHeureDebut($dateHeureDebut) { $this->dateHeureDebut = $dateHeureDebut; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }
    public function setTypeConsultation($typeConsultation) { $this->typeConsultation = $typeConsultation; return $this; }
    public function setMotif($motif) { $this->motif = $motif; return $this; }
    public function setIdClient($idClient) { $this->idClient = $idClient; return $this; }
    public function setIdMedecin($idMedecin) { $this->idMedecin = $idMedecin; return $this; }

    public function toArray() {
        return [
            'idRDV' => $this->idRDV,
            'dateHeureDebut' => $this->dateHeureDebut,
            'statut' => $this->statut,
            'typeConsultation' => $this->typeConsultation,
            'motif' => $this->motif,
            'idClient' => $this->idClient,
            'idMedecin' => $this->idMedecin
        ];
    }
}
