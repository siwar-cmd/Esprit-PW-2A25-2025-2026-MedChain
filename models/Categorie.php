<?php

class Categorie
{
    private ?int   $idCategorie  = null;
    private string $nomCategorie;
    private string $description;
    private string $icone;

    public function __construct(string $nomCategorie, string $description, string $icone)
    {
        $this->nomCategorie = $nomCategorie;
        $this->description  = $description;
        $this->icone        = $icone;
    }

    public function getIdCategorie(): ?int    { return $this->idCategorie; }
    public function getNomCategorie(): string { return $this->nomCategorie; }
    public function getDescription(): string  { return $this->description; }
    public function getIcone(): string        { return $this->icone; }

    public function setIdCategorie(int $id): void          { $this->idCategorie = $id; }
    public function setNomCategorie(string $nom): void     { $this->nomCategorie = $nom; }
    public function setDescription(string $desc): void     { $this->description = $desc; }
    public function setIcone(string $icone): void          { $this->icone = $icone; }
}
