<?php

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Insert a new notification for a user */
    public function create(int $idUtilisateur, string $message): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (id_utilisateur, message, is_read, date_creation)
             VALUES (:uid, :msg, 0, NOW())'
        );
        return $stmt->execute([':uid' => $idUtilisateur, ':msg' => $message]);
    }

    /** Count unread notifications for a user */
    public function countUnread(int $idUtilisateur): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE id_utilisateur = :uid AND is_read = 0'
        );
        $stmt->execute([':uid' => $idUtilisateur]);
        return (int) $stmt->fetchColumn();
    }

    /** Get the N most recent notifications for a user */
    public function getRecent(int $idUtilisateur, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE id_utilisateur = :uid
             ORDER BY date_creation DESC
             LIMIT ' . max(1, $limit)
        );
        $stmt->execute([':uid' => $idUtilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Mark all notifications as read for a user */
    public function markAllRead(int $idUtilisateur): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id_utilisateur = :uid AND is_read = 0'
        );
        $stmt->execute([':uid' => $idUtilisateur]);
    }

    /** Mark a single notification as read */
    public function markRead(int $idNotification): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id_notification = :id'
        );
        $stmt->execute([':id' => $idNotification]);
    }

    /** Get all notifications for a user (for a full page) */
    public function getAllForUser(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE id_utilisateur = :uid
             ORDER BY date_creation DESC'
        );
        $stmt->execute([':uid' => $idUtilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
