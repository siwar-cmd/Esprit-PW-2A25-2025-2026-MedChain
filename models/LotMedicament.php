<?php

class LotMedicament
{
    private $id_lot;
    private $nom_medicament;
    private $type_medicament;
    private $date_fabrication;
    private $date_expiration;
    private $quantite_initial;
    private $description;

    public function __construct(
        $nom_medicament = null,
        $type_medicament = null,
        $date_fabrication = null,
        $date_expiration = null,
        $quantite_initial = null,
        $description = null,
        $id_lot = null
    ) {
        $this->id_lot = $id_lot;
        $this->nom_medicament = $nom_medicament;
        $this->type_medicament = $type_medicament;
        $this->date_fabrication = $date_fabrication;
        $this->date_expiration = $date_expiration;
        $this->quantite_initial = $quantite_initial;
        $this->description = $description;
    }

    // Getters
    public function getIdLot() { return $this->id_lot; }
    public function getNomMedicament() { return $this->nom_medicament; }
    public function getTypeMedicament() { return $this->type_medicament; }
    public function getDateFabrication() { return $this->date_fabrication; }
    public function getDateExpiration() { return $this->date_expiration; }
    public function getQuantiteInitial() { return $this->quantite_initial; }
    public function getDescription() { return $this->description; }

    // Setters
    public function setIdLot($id_lot) { $this->id_lot = $id_lot; return $this; }
    public function setNomMedicament($nom_medicament) { $this->nom_medicament = $nom_medicament; return $this; }
    public function setTypeMedicament($type_medicament) { $this->type_medicament = $type_medicament; return $this; }
    public function setDateFabrication($date_fabrication) { $this->date_fabrication = $date_fabrication; return $this; }
    public function setDateExpiration($date_expiration) { $this->date_expiration = $date_expiration; return $this; }
    public function setQuantiteInitial($quantite_initial) { $this->quantite_initial = $quantite_initial; return $this; }
    public function setDescription($description) { $this->description = $description; return $this; }

    public function toArray() {
        return [
            'id_lot' => $this->id_lot,
            'nom_medicament' => $this->nom_medicament,
            'type_medicament' => $this->type_medicament,
            'date_fabrication' => $this->date_fabrication,
            'date_expiration' => $this->date_expiration,
            'quantite_initial' => $this->quantite_initial,
            'description' => $this->description
        ];
    }
}
