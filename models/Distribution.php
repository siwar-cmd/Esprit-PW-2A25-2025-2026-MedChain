<?php
class Distribution {
    private $idDistribution;
    private $idLot;
    private $quantite;
    private $dateDistribution;
    private $destinataire;

    public function __construct($idDistribution = null, $idLot = null, $quantite = null, $dateDistribution = null, $destinataire = null) {
        $this->idDistribution   = $idDistribution;
        $this->idLot            = $idLot;
        $this->quantite         = $quantite;
        $this->dateDistribution = $dateDistribution;
        $this->destinataire     = $destinataire;
    }

    // --- Getters ---
    public function getIdDistribution()   { return $this->idDistribution; }
    public function getIdLot()            { return $this->idLot; }
    public function getQuantite()         { return $this->quantite; }
    public function getDateDistribution() { return $this->dateDistribution; }
    public function getDestinataire()     { return $this->destinataire; }

    // --- Setters ---
    public function setIdDistribution($idDistribution)     { $this->idDistribution   = $idDistribution; }
    public function setIdLot($idLot)                       { $this->idLot            = $idLot; }
    public function setQuantite($quantite)                 { $this->quantite         = $quantite; }
    public function setDateDistribution($dateDistribution) { $this->dateDistribution = $dateDistribution; }
    public function setDestinataire($destinataire)         { $this->destinataire     = $destinataire; }
}
?>
