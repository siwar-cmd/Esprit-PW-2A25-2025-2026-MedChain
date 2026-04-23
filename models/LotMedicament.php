<?php
class LotMedicament {
    private $idLot;
    private $nomMedicament;
    private $numeroLot;
    private $quantite;
    private $datePeremption;

    public function __construct($idLot = null, $nomMedicament = null, $numeroLot = null, $quantite = null, $datePeremption = null) {
        $this->idLot          = $idLot;
        $this->nomMedicament  = $nomMedicament;
        $this->numeroLot      = $numeroLot;
        $this->quantite       = $quantite;
        $this->datePeremption = $datePeremption;
    }

    // --- Getters ---
    public function getIdLot()          { return $this->idLot; }
    public function getNomMedicament()  { return $this->nomMedicament; }
    public function getNumeroLot()      { return $this->numeroLot; }
    public function getQuantite()       { return $this->quantite; }
    public function getDatePeremption() { return $this->datePeremption; }

    // --- Setters ---
    public function setIdLot($idLot)                   { $this->idLot          = $idLot; }
    public function setNomMedicament($nomMedicament)   { $this->nomMedicament  = $nomMedicament; }
    public function setNumeroLot($numeroLot)           { $this->numeroLot      = $numeroLot; }
    public function setQuantite($quantite)             { $this->quantite       = $quantite; }
    public function setDatePeremption($datePeremption) { $this->datePeremption = $datePeremption; }
}
?>
