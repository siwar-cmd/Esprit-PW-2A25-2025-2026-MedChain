<?php

class Distribution
{
    private $id_distribution;
    private $id_lot;
    private $date_distribution;
    private $quantite_distribuee;
    private $patient;
    private $responsable;
    private $statut;

    public function __construct(
        $id_lot = null,
        $date_distribution = null,
        $quantite_distribuee = null,
        $patient = null,
        $responsable = null,
        $statut = 'En attente',
        $id_distribution = null
    ) {
        $this->id_distribution = $id_distribution;
        $this->id_lot = $id_lot;
        $this->date_distribution = $date_distribution;
        $this->quantite_distribuee = $quantite_distribuee;
        $this->patient = $patient;
        $this->responsable = $responsable;
        $this->statut = $statut;
    }

    // Getters
    public function getIdDistribution() { return $this->id_distribution; }
    public function getIdLot() { return $this->id_lot; }
    public function getDateDistribution() { return $this->date_distribution; }
    public function getQuantiteDistribuee() { return $this->quantite_distribuee; }
    public function getPatient() { return $this->patient; }
    public function getResponsable() { return $this->responsable; }
    public function getStatut() { return $this->statut; }

    // Setters
    public function setIdDistribution($id_distribution) { $this->id_distribution = $id_distribution; return $this; }
    public function setIdLot($id_lot) { $this->id_lot = $id_lot; return $this; }
    public function setDateDistribution($date_distribution) { $this->date_distribution = $date_distribution; return $this; }
    public function setQuantiteDistribuee($quantite_distribuee) { $this->quantite_distribuee = $quantite_distribuee; return $this; }
    public function setPatient($patient) { $this->patient = $patient; return $this; }
    public function setResponsable($responsable) { $this->responsable = $responsable; return $this; }
    public function setStatut($statut) { $this->statut = $statut; return $this; }

    public function toArray() {
        return [
            'id_distribution' => $this->id_distribution,
            'id_lot' => $this->id_lot,
            'date_distribution' => $this->date_distribution,
            'quantite_distribuee' => $this->quantite_distribuee,
            'patient' => $this->patient,
            'responsable' => $this->responsable,
            'statut' => $this->statut
        ];
    }
}
