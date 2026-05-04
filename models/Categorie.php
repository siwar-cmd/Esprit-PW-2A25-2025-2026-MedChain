<?php
declare(strict_types=1);

/**
 * Categorie — Repository for object categories.
 */
class Categorie
{
    private static function db(): PDO
    {
        return config::getConnexion();
    }

    /** @return array<int, array<string, mixed>> */
    public static function findAll(): array
    {
        return self::db()
            ->query('SELECT * FROM categorie_objet ORDER BY nom_categorie')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM categorie_objet WHERE id_categorie = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Count objects per category for dashboard stats.
     * @return array<int, array<string, mixed>>
     */
    public static function countObjectsPerCategory(): array
    {
        return self::db()->query(
            "SELECT c.id_categorie, c.nom_categorie, c.icone, COUNT(o.id_objet) AS total
             FROM categorie_objet c
             LEFT JOIN objet_loisir o ON o.id_categorie = c.id_categorie
             GROUP BY c.id_categorie
             ORDER BY total DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
