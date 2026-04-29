<?php
class Intervention {
    private $idIntervention;
    private $dateHeureDebut;
    private $dateHeureFinPrevu;
    private $typeIntervention;
    private $niveauUrgence;

    public function __construct(
        $idIntervention = null, $dateHeureDebut = null, $dateHeureFinPrevu = null,
        $typeIntervention = null, $niveauUrgence = null
    ) {
        $this->idIntervention    = $idIntervention;
        $this->dateHeureDebut    = $dateHeureDebut;
        $this->dateHeureFinPrevu = $dateHeureFinPrevu;
        $this->typeIntervention  = $typeIntervention;
        $this->niveauUrgence     = $niveauUrgence;
    }

    // --- Getters ---
    public function getIdIntervention()    { return $this->idIntervention; }
    public function getDateHeureDebut()    { return $this->dateHeureDebut; }
    public function getDateHeureFinPrevu() { return $this->dateHeureFinPrevu; }
    public function getTypeIntervention()  { return $this->typeIntervention; }
    public function getNiveauUrgence()     { return $this->niveauUrgence; }

    // --- Setters ---
    public function setIdIntervention($idIntervention)       { $this->idIntervention    = $idIntervention; }
    public function setDateHeureDebut($dateHeureDebut)       { $this->dateHeureDebut    = $dateHeureDebut; }
    public function setDateHeureFinPrevu($dateHeureFinPrevu) { $this->dateHeureFinPrevu = $dateHeureFinPrevu; }
    public function setTypeIntervention($typeIntervention)   { $this->typeIntervention  = $typeIntervention; }
    public function setNiveauUrgence($niveauUrgence)         { $this->niveauUrgence     = $niveauUrgence; }
}
?>
