<?php
<<<<<<< HEAD

class Mission {
    private int $id;
    private string $dateDebut;
    private string $dateFin;
    private string $typeMission;
    private string $lieuDepart;
    private string $lieuArrivee;
    private string $equipe;
    private bool $estTerminee;
    private int $idAmbulance;

    public function __construct(
        int $id = 0,
        string $dateDebut = '',
        string $dateFin = '',
        string $typeMission = '',
        string $lieuDepart = '',
        string $lieuArrivee = '',
        string $equipe = '',
        bool $estTerminee = false,
        int $idAmbulance = 0
    ) {
        $this->id = $id;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->typeMission = $typeMission;
        $this->lieuDepart = $lieuDepart;
        $this->lieuArrivee = $lieuArrivee;
        $this->equipe = $equipe;
        $this->estTerminee = $estTerminee;
        $this->idAmbulance = $idAmbulance;
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getDateDebut(): string { return $this->dateDebut; }
    public function setDateDebut(string $dateDebut): void { $this->dateDebut = $dateDebut; }

    public function getDateFin(): string { return $this->dateFin; }
    public function setDateFin(string $dateFin): void { $this->dateFin = $dateFin; }

    public function getTypeMission(): string { return $this->typeMission; }
    public function setTypeMission(string $typeMission): void { $this->typeMission = $typeMission; }

    public function getLieuDepart(): string { return $this->lieuDepart; }
    public function setLieuDepart(string $lieuDepart): void { $this->lieuDepart = $lieuDepart; }

    public function getLieuArrivee(): string { return $this->lieuArrivee; }
    public function setLieuArrivee(string $lieuArrivee): void { $this->lieuArrivee = $lieuArrivee; }

    public function getEquipe(): string { return $this->equipe; }
    public function setEquipe(string $equipe): void { $this->equipe = $equipe; }

    public function isEstTerminee(): bool { return $this->estTerminee; }
    public function setEstTerminee(bool $estTerminee): void { $this->estTerminee = $estTerminee; }

    public function getIdAmbulance(): int { return $this->idAmbulance; }
    public function setIdAmbulance(int $idAmbulance): void { $this->idAmbulance = $idAmbulance; }

    public function calculerDuree(): ?string {
        if (empty($this->dateDebut) || empty($this->dateFin)) return null;
        try {
            $start = new DateTime($this->dateDebut);
            $end   = new DateTime($this->dateFin);
            $diff  = $start->diff($end);
            $parts = [];
            if ($diff->days > 0) $parts[] = $diff->days . 'j';
            if ($diff->h > 0)    $parts[] = $diff->h . 'h';
            if ($diff->i > 0)    $parts[] = $diff->i . 'min';
            return $parts ? implode(' ', $parts) : '< 1 min';
        } catch (Exception $e) {
            return null;
        }
    }

    public function toArray(): array {
        return [
            'id'          => $this->id,
            'dateDebut'   => $this->dateDebut,
            'dateFin'     => $this->dateFin,
            'typeMission' => $this->typeMission,
            'lieuDepart'  => $this->lieuDepart,
            'lieuArrivee' => $this->lieuArrivee,
            'equipe'      => $this->equipe,
            'estTerminee' => $this->estTerminee,
            'idAmbulance' => $this->idAmbulance,
        ];
    }
}
=======
class Mission {
    private $idMission;
    private $idAmbulance;
    private $dateDebut;
    private $dateFin;
    private $typeMission;
    private $lieuDepart;
    private $lieuArrivee;
    private $equipe;
    private $estTerminee;
    private $immatriculation_ambulance; // Champ de jointure

    public function __construct(
        $idMission = null, $idAmbulance = null, $dateDebut = null, $dateFin = null,
        $typeMission = null, $lieuDepart = null, $lieuArrivee = null, $equipe = null, $estTerminee = null
    ) {
        $this->idMission    = $idMission;
        $this->idAmbulance  = $idAmbulance;
        $this->dateDebut    = $dateDebut;
        $this->dateFin      = $dateFin;
        $this->typeMission  = $typeMission;
        $this->lieuDepart   = $lieuDepart;
        $this->lieuArrivee  = $lieuArrivee;
        $this->equipe       = $equipe;
        $this->estTerminee  = $estTerminee;
    }

    // --- Getters ---
    public function getIdMission()                  { return $this->idMission; }
    public function getIdAmbulance()                { return $this->idAmbulance; }
    public function getDateDebut()                  { return $this->dateDebut; }
    public function getDateFin()                    { return $this->dateFin; }
    public function getTypeMission()                { return $this->typeMission; }
    public function getLieuDepart()                 { return $this->lieuDepart; }
    public function getLieuArrivee()                { return $this->lieuArrivee; }
    public function getEquipe()                     { return $this->equipe; }
    public function getEstTerminee()                { return $this->estTerminee; }
    public function getImmatriculationAmbulance()   { return $this->immatriculation_ambulance; }

    // --- Setters ---
    public function setIdMission($idMission)                       { $this->idMission    = $idMission; }
    public function setIdAmbulance($idAmbulance)                   { $this->idAmbulance  = $idAmbulance; }
    public function setDateDebut($dateDebut)                       { $this->dateDebut    = $dateDebut; }
    public function setDateFin($dateFin)                           { $this->dateFin      = $dateFin; }
    public function setTypeMission($typeMission)                   { $this->typeMission  = $typeMission; }
    public function setLieuDepart($lieuDepart)                     { $this->lieuDepart   = $lieuDepart; }
    public function setLieuArrivee($lieuArrivee)                   { $this->lieuArrivee  = $lieuArrivee; }
    public function setEquipe($equipe)                             { $this->equipe       = $equipe; }
    public function setEstTerminee($estTerminee)                   { $this->estTerminee  = $estTerminee; }
    public function setImmatriculationAmbulance($immatriculation)  { $this->immatriculation_ambulance = $immatriculation; }
}
?>
>>>>>>> 7801b75b753d80646e10602aa1365f908755f051
