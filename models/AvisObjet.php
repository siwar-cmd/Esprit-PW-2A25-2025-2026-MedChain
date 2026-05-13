<?php

class AvisObjet
{
    private ?int    $idAvis       = null;
    private int     $idPatient;
    private int     $idObjet;
    private int     $note;
    private string  $commentaire;
    private ?string $dateAvis     = null;

    public function __construct(int $idPatient, int $idObjet, int $note, string $commentaire)
    {
        $this->idPatient   = $idPatient;
        $this->idObjet     = $idObjet;
        $this->note        = $note;
        $this->commentaire = $commentaire;
    }

    public function getIdAvis(): ?int       { return $this->idAvis; }
    public function getIdPatient(): int     { return $this->idPatient; }
    public function getIdObjet(): int       { return $this->idObjet; }
    public function getNote(): int          { return $this->note; }
    public function getCommentaire(): string { return $this->commentaire; }
    public function getDateAvis(): ?string  { return $this->dateAvis; }

    public function setIdAvis(int $idAvis): void           { $this->idAvis = $idAvis; }
    public function setIdPatient(int $idPatient): void     { $this->idPatient = $idPatient; }
    public function setIdObjet(int $idObjet): void         { $this->idObjet = $idObjet; }
    public function setNote(int $note): void               { $this->note = $note; }
    public function setCommentaire(string $c): void        { $this->commentaire = $c; }
    public function setDateAvis(string $dateAvis): void    { $this->dateAvis = $dateAvis; }
}
