<?php

class Categorie
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        return $this->db
            ->query('SELECT * FROM categorie_objet ORDER BY nom_categorie')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categorie_objet WHERE id_categorie = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(string $nom, string $description, string $icone): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categorie_objet (nom_categorie, description, icone)
             VALUES (:nom, :desc, :icone)'
        );
        return $stmt->execute([':nom' => $nom, ':desc' => $description, ':icone' => $icone]);
    }

    public function update(int $id, string $nom, string $description, string $icone): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categorie_objet
             SET nom_categorie = :nom, description = :desc, icone = :icone
             WHERE id_categorie = :id'
        );
        return $stmt->execute([':nom' => $nom, ':desc' => $description, ':icone' => $icone, ':id' => $id]);
    }

    public function delete(int $id): array
    {
        // Block deletion if objects are linked
        $check = $this->db->prepare('SELECT COUNT(*) FROM objet_loisir WHERE id_categorie = :id');
        $check->execute([':id' => $id]);
        if ((int) $check->fetchColumn() > 0) {
            return ['success' => false, 'error' => 'linked_to_objects'];
        }

        $stmt = $this->db->prepare('DELETE FROM categorie_objet WHERE id_categorie = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() === 1
            ? ['success' => true]
            : ['success' => false, 'error' => 'not_found'];
    }

    public function nameExists(string $nom, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM categorie_objet
             WHERE nom_categorie = :nom AND id_categorie != :exclude'
        );
        $stmt->execute([':nom' => $nom, ':exclude' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
