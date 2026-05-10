<?php

class RecommendationController
{
    private PDO $db;

    private const LIMIT = 5;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Main entry point.
     * Tier 1 — Category-based: suggest from the user's most-borrowed category.
     * Tier 2 — Popularity fallback: most-borrowed platform-wide.
     */
    public function getRecommendations(int $userId): array
    {
        $favouriteCategory = $this->getFavouriteCategory($userId);

        if ($favouriteCategory !== null) {
            $results = $this->getByCategory($userId, $favouriteCategory);
            if (!empty($results)) {
                return $this->tag($results, 'category', $favouriteCategory['nom_categorie']);
            }
        }

        $popular = $this->getMostPopular($userId);
        return $this->tag($popular, 'popular', null);
    }

    // ── Private query methods ─────────────────────────────────────────────────

    private function getFavouriteCategory(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT o.id_categorie, c.nom_categorie, COUNT(*) AS borrow_count
             FROM pret p
             INNER JOIN objet_loisir o ON p.id_objet = o.id_objet
             INNER JOIN categorie_objet c ON o.id_categorie = c.id_categorie
             WHERE p.id_patient = :uid
               AND o.id_categorie IS NOT NULL
             GROUP BY o.id_categorie, c.nom_categorie
             ORDER BY borrow_count DESC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getByCategory(int $userId, array $category): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*,
                    c.nom_categorie,
                    c.icone AS categorie_icone,
                    COUNT(p2.id_pret) AS borrow_count
             FROM objet_loisir o
             LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
             LEFT JOIN pret p2 ON p2.id_objet = o.id_objet
             WHERE o.id_categorie = :cat
               AND o.quantite > 0
               AND o.id_objet NOT IN (
                   SELECT DISTINCT p3.id_objet
                   FROM pret p3
                   WHERE p3.id_patient = :uid
               )
             GROUP BY o.id_objet, c.nom_categorie, c.icone
             ORDER BY borrow_count DESC, o.nom_objet ASC
             LIMIT " . self::LIMIT
        );
        $stmt->execute([':cat' => $category['id_categorie'], ':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMostPopular(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*,
                    c.nom_categorie,
                    c.icone AS categorie_icone,
                    COUNT(p.id_pret) AS borrow_count
             FROM objet_loisir o
             LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
             LEFT JOIN pret p ON p.id_objet = o.id_objet
             WHERE o.quantite > 0
               AND o.id_objet NOT IN (
                   SELECT DISTINCT p2.id_objet
                   FROM pret p2
                   WHERE p2.id_patient = :uid
               )
             GROUP BY o.id_objet, c.nom_categorie, c.icone
             ORDER BY borrow_count DESC, o.nom_objet ASC
             LIMIT " . self::LIMIT
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $stmt2 = $this->db->prepare(
                "SELECT o.*,
                        c.nom_categorie,
                        c.icone AS categorie_icone,
                        COUNT(p.id_pret) AS borrow_count
                 FROM objet_loisir o
                 LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
                 LEFT JOIN pret p ON p.id_objet = o.id_objet
                 WHERE o.quantite > 0
                 GROUP BY o.id_objet, c.nom_categorie, c.icone
                 ORDER BY borrow_count DESC, o.nom_objet ASC
                 LIMIT " . self::LIMIT
            );
            $stmt2->execute();
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        return $rows;
    }

    private function tag(array $rows, string $type, ?string $catName): array
    {
        foreach ($rows as &$row) {
            $row['disponibilite'] = ((int) ($row['quantite'] ?? 0) > 0)
                ? 'disponible'
                : 'indisponible';
            $row['rec_type']     = $type;
            $row['rec_cat_name'] = $catName;
        }
        unset($row);
        return $rows;
    }
}
