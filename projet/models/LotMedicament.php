<?php

class LotMedicament {

    private $id_lot;
    private $nom_medicament;
    private $type_medicament;
    private $date_fabrication;
    private $date_expiration;
    private $quantite_initial;
    private $quantite_restante;
    private $description;

    public function __construct(
        $id_lot = null,
        $nom_medicament = null,
        $type_medicament = null,
        $date_fabrication = null,
        $date_expiration = null,
        $quantite_initial = null,
        $quantite_restante = null,
        $description = null
    ) {
        $this->id_lot = $id_lot;
        $this->nom_medicament = $nom_medicament;
        $this->type_medicament = $type_medicament;
        $this->date_fabrication = $date_fabrication;
        $this->date_expiration = $date_expiration;
        $this->quantite_initial = $quantite_initial;
        $this->quantite_restante = $quantite_restante;
        $this->description = $description;
    }

    // GETTERS
    public function getIdLot() { return $this->id_lot; }
    public function getNom() { return $this->nom_medicament; }
    public function getType() { return $this->type_medicament; }
    public function getDateFabrication() { return $this->date_fabrication; }
    public function getDateExpiration() { return $this->date_expiration; }
    public function getQuantiteInitial() { return $this->quantite_initial; }
    public function getQuantiteRestante() { return $this->quantite_restante; }
    public function getDescription() { return $this->description; }

    // SETTERS
    public function setNom($v) { $this->nom_medicament = $v; }
    public function setType($v) { $this->type_medicament = $v; }
    public function setDateFabrication($v) { $this->date_fabrication = $v; }
    public function setDateExpiration($v) { $this->date_expiration = $v; }
    public function setQuantiteInitial($v) { $this->quantite_initial = $v; }
    public function setQuantiteRestante($v) { $this->quantite_restante = $v; }
    public function setDescription($v) { $this->description = $v; }

    public function __destruct() {}
}