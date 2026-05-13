<?php

class Notification
{
    private ?int    $idNotification;
    private int     $idUtilisateur;
    private string  $message;
    private bool    $isRead;
    private ?string $dateCreation;

    public function __construct(
        ?int    $idNotification = null,
        int     $idUtilisateur  = 0,
        string  $message        = '',
        bool    $isRead         = false,
        ?string $dateCreation   = null
    ) {
        $this->idNotification = $idNotification;
        $this->idUtilisateur  = $idUtilisateur;
        $this->message        = $message;
        $this->isRead         = $isRead;
        $this->dateCreation   = $dateCreation;
    }

    public function getIdNotification(): ?int    { return $this->idNotification; }
    public function getIdUtilisateur(): int      { return $this->idUtilisateur; }
    public function getMessage(): string         { return $this->message; }
    public function isRead(): bool               { return $this->isRead; }
    public function getDateCreation(): ?string   { return $this->dateCreation; }

    public function setIdNotification(?int $id): void    { $this->idNotification = $id; }
    public function setIdUtilisateur(int $id): void      { $this->idUtilisateur  = $id; }
    public function setMessage(string $message): void    { $this->message        = $message; }
    public function setIsRead(bool $isRead): void        { $this->isRead         = $isRead; }
    public function setDateCreation(?string $date): void { $this->dateCreation   = $date; }
}
