<?php
class MaterielChirurgical {
    private $idMateriel;
    private $nom;
    private $categorie;
    private $disponibilite;
    private $statutSterilisation;
    private $nombreUtilisationsMax;
    private $nombreUtilisationsActuelles;

    public function __construct(
        $idMateriel = null, $nom = null, $categorie = null, $disponibilite = null,
        $statutSterilisation = null, $nombreUtilisationsMax = null, $nombreUtilisationsActuelles = 0
    ) {
        $this->idMateriel                  = $idMateriel;
        $this->nom                         = $nom;
        $this->categorie                   = $categorie;
        $this->disponibilite               = $disponibilite;
        $this->statutSterilisation         = $statutSterilisation;
        $this->nombreUtilisationsMax       = $nombreUtilisationsMax;
        $this->nombreUtilisationsActuelles = $nombreUtilisationsActuelles;
    }

    // --- Getters ---
    public function getIdMateriel()                  { return $this->idMateriel; }
    public function getNom()                         { return $this->nom; }
    public function getCategorie()                   { return $this->categorie; }
    public function getDisponibilite()               { return $this->disponibilite; }
    public function getStatutSterilisation()         { return $this->statutSterilisation; }
    public function getNombreUtilisationsMax()       { return $this->nombreUtilisationsMax; }
    public function getNombreUtilisationsActuelles() { return $this->nombreUtilisationsActuelles; }

    // --- Setters ---
    public function setIdMateriel($idMateriel)                                   { $this->idMateriel                  = $idMateriel; }
    public function setNom($nom)                                                 { $this->nom                         = $nom; }
    public function setCategorie($categorie)                                     { $this->categorie                   = $categorie; }
    public function setDisponibilite($disponibilite)                             { $this->disponibilite               = $disponibilite; }
    public function setStatutSterilisation($statutSterilisation)                 { $this->statutSterilisation         = $statutSterilisation; }
    public function setNombreUtilisationsMax($nombreUtilisationsMax)             { $this->nombreUtilisationsMax       = $nombreUtilisationsMax; }
    public function setNombreUtilisationsActuelles($nombreUtilisationsActuelles) { $this->nombreUtilisationsActuelles = $nombreUtilisationsActuelles; }
}
?>
