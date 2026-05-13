<?php

class Reservation
{
    private ?int    $idReservation;
    private int     $idPatient;
    private int     $idObjet;
    private ?string $dateReservation;
    private string  $statut;

    public function __construct(
        ?int    $idReservation   = null,
        int     $idPatient       = 0,
        int     $idObjet         = 0,
        ?string $dateReservation = null,
        string  $statut          = 'en_attente'
    ) {
        $this->idReservation   = $idReservation;
        $this->idPatient       = $idPatient;
        $this->idObjet         = $idObjet;
        $this->dateReservation = $dateReservation;
        $this->statut          = $statut;
    }

    public function getIdReservation(): ?int    { return $this->idReservation; }
    public function getIdPatient(): int         { return $this->idPatient; }
    public function getIdObjet(): int           { return $this->idObjet; }
    public function getDateReservation(): ?string { return $this->dateReservation; }
    public function getStatut(): string         { return $this->statut; }

    public function setIdReservation(?int $id): void      { $this->idReservation   = $id; }
    public function setIdPatient(int $id): void           { $this->idPatient       = $id; }
    public function setIdObjet(int $id): void             { $this->idObjet         = $id; }
    public function setDateReservation(?string $d): void  { $this->dateReservation = $d; }
    public function setStatut(string $statut): void       { $this->statut          = $statut; }
}
