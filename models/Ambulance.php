<?php
<<<<<<< HEAD

class Ambulance {
    private int $id;
    private string $immatriculation;
    private string $statut;
    private string $modele;
    private int $capacite;
    private bool $estDisponible;

    public function __construct(
        int $id = 0,
        string $immatriculation = '',
        string $statut = 'En service',
        string $modele = '',
        int $capacite = 2,
        bool $estDisponible = true
    ) {
        $this->id = $id;
        $this->immatriculation = $immatriculation;
        $this->statut = $statut;
        $this->modele = $modele;
        $this->capacite = $capacite;
        $this->estDisponible = $estDisponible;
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getImmatriculation(): string { return $this->immatriculation; }
    public function setImmatriculation(string $immatriculation): void { $this->immatriculation = $immatriculation; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): void { $this->statut = $statut; }

    public function getModele(): string { return $this->modele; }
    public function setModele(string $modele): void { $this->modele = $modele; }

    public function getCapacite(): int { return $this->capacite; }
    public function setCapacite(int $capacite): void { $this->capacite = $capacite; }

    public function isEstDisponible(): bool { return $this->estDisponible; }
    public function setEstDisponible(bool $estDisponible): void { $this->estDisponible = $estDisponible; }

    public function changerStatut(string $nouveauStatut): void {
        $this->statut = $nouveauStatut;
    }

    public function toArray(): array {
        return [
            'id'              => $this->id,
            'immatriculation' => $this->immatriculation,
            'statut'          => $this->statut,
            'modele'          => $this->modele,
            'capacite'        => $this->capacite,
            'estDisponible'   => $this->estDisponible,
        ];
    }
}
=======
class Ambulance {
    private $idAmbulance;
    private $immatriculation;
    private $statut;
    private $modele;
    private $capacite;
    private $estDisponible;

    public function __construct($idAmbulance = null, $immatriculation = null, $statut = null, $modele = null, $capacite = null, $estDisponible = null) {
        $this->idAmbulance    = $idAmbulance;
        $this->immatriculation = $immatriculation;
        $this->statut         = $statut;
        $this->modele         = $modele;
        $this->capacite       = $capacite;
        $this->estDisponible  = $estDisponible;
    }

    // --- Getters ---
    public function getIdAmbulance()    { return $this->idAmbulance; }
    public function getImmatriculation(){ return $this->immatriculation; }
    public function getStatut()         { return $this->statut; }
    public function getModele()         { return $this->modele; }
    public function getCapacite()       { return $this->capacite; }
    public function getEstDisponible()  { return $this->estDisponible; }

    // --- Setters ---
    public function setIdAmbulance($idAmbulance)       { $this->idAmbulance    = $idAmbulance; }
    public function setImmatriculation($immatriculation){ $this->immatriculation = $immatriculation; }
    public function setStatut($statut)                 { $this->statut         = $statut; }
    public function setModele($modele)                 { $this->modele         = $modele; }
    public function setCapacite($capacite)             { $this->capacite       = $capacite; }
    public function setEstDisponible($estDisponible)   { $this->estDisponible  = $estDisponible; }
}
?>
>>>>>>> 7801b75b753d80646e10602aa1365f908755f051
