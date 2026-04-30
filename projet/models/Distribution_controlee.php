<?php

class Distribution {

    private $id_distribution;
    private $id_lot;
    private $date_distribution;
    private $quantite_distribuee;
    private $patient;
    private $responsable;

    /* ================= CONSTRUCTEUR ================= */
    public function __construct(
        $id_distribution = null,
        $id_lot,
        $date_distribution,
        $quantite_distribuee,
        $patient,
        $responsable
    ) {
        $this->id_distribution = $id_distribution;
        $this->id_lot = $id_lot;
        $this->date_distribution = $date_distribution;
        $this->quantite_distribuee = $quantite_distribuee;
        $this->patient = $patient;
        $this->responsable = $responsable;
    }

    /* ================= GETTERS ================= */

    public function getIdDistribution() {
        return $this->id_distribution;
    }

    public function getIdLot() {
        return $this->id_lot;
    }

    public function getDateDistribution() {
        return $this->date_distribution;
    }

    public function getQuantiteDistribuee() {
        return $this->quantite_distribuee;
    }

    public function getPatient() {
        return $this->patient;
    }

    public function getResponsable() {
        return $this->responsable;
    }

    /* ================= SETTERS (اختياريين) ================= */

    public function setIdLot($id_lot) {
        $this->id_lot = $id_lot;
    }

    public function setDateDistribution($date_distribution) {
        $this->date_distribution = $date_distribution;
    }

    public function setQuantiteDistribuee($quantite_distribuee) {
        $this->quantite_distribuee = $quantite_distribuee;
    }

    public function setPatient($patient) {
        $this->patient = $patient;
    }

    public function setResponsable($responsable) {
        $this->responsable = $responsable;
    }
}