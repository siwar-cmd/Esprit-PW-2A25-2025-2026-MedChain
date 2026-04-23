<?php

class ObjetLoisir
{
    private $id_objet;
    private $nom_objet;
    private $type_objet;
    private $quantite;
    private $etat;
    private $disponibilite;
    private $description;

    public function __construct(
        $id_objet = null,
        $nom_objet = null,
        $type_objet = null,
        $quantite = null,
        $etat = null,
        $disponibilite = null,
        $description = null
    ) {
        $this->id_objet = $id_objet;
        $this->nom_objet = $nom_objet;
        $this->type_objet = $type_objet;
        $this->quantite = $quantite;
        $this->etat = $etat;
        $this->disponibilite = $disponibilite;
        $this->description = $description;
    }

    public function getId() { return $this->id_objet; }
    public function getNom() { return $this->nom_objet; }
    public function getType() { return $this->type_objet; }
    public function getQuantite() { return $this->quantite; }
    public function getEtat() { return $this->etat; }
    public function getDisponibilite() { return $this->disponibilite; }
    public function getDescription() { return $this->description; }

    public function setId($id) { $this->id_objet = $id; }
    public function setNom($nom) { $this->nom_objet = $nom; }
    public function setType($type) { $this->type_objet = $type; }
    public function setQuantite($quantite) { $this->quantite = $quantite; }
    public function setEtat($etat) { $this->etat = $etat; }
    public function setDisponibilite($disponibilite) { $this->disponibilite = $disponibilite; }
    public function setDescription($description) { $this->description = $description; }
}
