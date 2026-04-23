<?php
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
