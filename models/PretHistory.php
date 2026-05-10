<?php

class PretHistory
{
    private ?int    $idHistory;
    private int     $idPret;
    private string  $ancienStatut;
    private string  $nouveauStatut;
    private ?int    $changedBy;
    private ?string $dateChange;

    public function __construct(
        ?int    $idHistory     = null,
        int     $idPret        = 0,
        string  $ancienStatut  = '',
        string  $nouveauStatut = '',
        ?int    $changedBy     = null,
        ?string $dateChange    = null
    ) {
        $this->idHistory     = $idHistory;
        $this->idPret        = $idPret;
        $this->ancienStatut  = $ancienStatut;
        $this->nouveauStatut = $nouveauStatut;
        $this->changedBy     = $changedBy;
        $this->dateChange    = $dateChange;
    }

    public function getIdHistory(): ?int      { return $this->idHistory; }
    public function getIdPret(): int          { return $this->idPret; }
    public function getAncienStatut(): string { return $this->ancienStatut; }
    public function getNouveauStatut(): string{ return $this->nouveauStatut; }
    public function getChangedBy(): ?int      { return $this->changedBy; }
    public function getDateChange(): ?string  { return $this->dateChange; }

    public function setIdHistory(?int $id): void         { $this->idHistory     = $id; }
    public function setIdPret(int $id): void             { $this->idPret        = $id; }
    public function setAncienStatut(string $s): void     { $this->ancienStatut  = $s; }
    public function setNouveauStatut(string $s): void    { $this->nouveauStatut = $s; }
    public function setChangedBy(?int $id): void         { $this->changedBy     = $id; }
    public function setDateChange(?string $date): void   { $this->dateChange    = $date; }
}
