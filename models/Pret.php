<?php

class Pret
{
    private $id_pret;
    private $id_objet;
    private $nom_patient;
    private $date_pret;
    private $date_retour_prevue;
    private $date_retour_effective;
    private $statut;

    public function __construct(
        $id_pret = null,
        $id_objet = null,
        $nom_patient = '',
        $date_pret = null,
        $date_retour_prevue = null,
        $date_retour_effective = null,
        $statut = ''
    ) {
        $this->id_pret = $id_pret;
        $this->id_objet = $id_objet;
        $this->nom_patient = $nom_patient;
        $this->date_pret = $date_pret;
        $this->date_retour_prevue = $date_retour_prevue;
        $this->date_retour_effective = $date_retour_effective;
        $this->statut = $statut;
    }

    public function getId()
    {
        return $this->id_pret;
    }

    public function getIdPret()
    {
        return $this->id_pret;
    }

    public function getIdObjet()
    {
        return $this->id_objet;
    }

    public function getObjetId()
    {
        return $this->id_objet;
    }

    public function getNomPatient()
    {
        return $this->nom_patient;
    }

    public function getDatePret()
    {
        return $this->date_pret;
    }

    public function getDateRetourPrevue()
    {
        return $this->date_retour_prevue;
    }

    public function getDateRetourEffective()
    {
        return $this->date_retour_effective;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setId($id): void
    {
        $this->id_pret = $id;
    }

    public function setIdPret($id_pret): void
    {
        $this->id_pret = $id_pret;
    }

    public function setIdObjet($id_objet): void
    {
        $this->id_objet = $id_objet;
    }

    public function setObjetId($id_objet): void
    {
        $this->id_objet = $id_objet;
    }

    public function setNomPatient($nom_patient): void
    {
        $this->nom_patient = $nom_patient;
    }

    public function setDatePret($date_pret): void
    {
        $this->date_pret = $date_pret;
    }

    public function setDateRetourPrevue($date_retour_prevue): void
    {
        $this->date_retour_prevue = $date_retour_prevue;
    }

    public function setDateRetourEffective($date_retour_effective): void
    {
        $this->date_retour_effective = $date_retour_effective;
    }

    public function setStatut($statut): void
    {
        $this->statut = $statut;
    }
}
