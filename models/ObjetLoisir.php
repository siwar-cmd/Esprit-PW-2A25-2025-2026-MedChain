<?php
declare(strict_types=1);

/**
 * ObjetLoisir — Entity model for leisure objects.
 *
 * Uses Project B's `config::getConnexion()` for all database access.
 * All SQL queries are encapsulated here (strict MVC).
 */
class ObjetLoisir
{
    // ─── Properties ────────────────────────────────────────────
    private ?int    $id_objet      = null;
    private string  $nom_objet     = '';
    private string  $type_objet    = '';
    private int     $quantite      = 0;
    private string  $etat          = 'neuf';
    private string  $disponibilite = 'indisponible';
    private ?string $description   = null;

    // ─── Constructor ───────────────────────────────────────────
    public function __construct(
        ?int    $id_objet      = null,
        string  $nom_objet     = '',
        string  $type_objet    = '',
        int     $quantite      = 0,
        string  $etat          = 'neuf',
        string  $disponibilite = 'indisponible',
        ?string $description   = null
    ) {
        $this->id_objet      = $id_objet;
        $this->nom_objet     = $nom_objet;
        $this->type_objet    = $type_objet;
        $this->quantite      = $quantite;
        $this->etat          = $etat;
        $this->disponibilite = $disponibilite;
        $this->description   = $description;
    }

    // ─── Getters ───────────────────────────────────────────────
    public function getId(): ?int          { return $this->id_objet; }
    public function getNom(): string       { return $this->nom_objet; }
    public function getType(): string      { return $this->type_objet; }
    public function getQuantite(): int     { return $this->quantite; }
    public function getEtat(): string      { return $this->etat; }
    public function getDisponibilite(): string { return $this->disponibilite; }
    public function getDescription(): ?string  { return $this->description; }

    // ─── Setters ───────────────────────────────────────────────
    public function setId(?int $id): void              { $this->id_objet = $id; }
    public function setNom(string $nom): void          { $this->nom_objet = $nom; }
    public function setType(string $type): void        { $this->type_objet = $type; }
    public function setQuantite(int $quantite): void   { $this->quantite = $quantite; }
    public function setEtat(string $etat): void        { $this->etat = $etat; }
    public function setDisponibilite(string $d): void  { $this->disponibilite = $d; }
    public function setDescription(?string $d): void   { $this->description = $d; }

    // ═══════════════════════════════════════════════════════════
    //  REPOSITORY METHODS (static) — all DB access goes here
    // ═══════════════════════════════════════════════════════════

    /**
     * Obtain the shared PDO instance via Project B's config class.
     */
    private static function db(): PDO
    {
        return config::getConnexion();
    }

    /**
     * Fetch every leisure object, ordered by name.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findAll(): array
    {
        $stmt = self::db()->query(
            'SELECT o.*, c.nom_categorie, c.icone AS cat_icone
             FROM objet_loisir o
             LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
             ORDER BY o.nom_objet'
        );
        return array_map([self::class, 'hydrateAvailability'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Paginated listing with optional category filter.
     *
     * @return array{rows: array, total: int, pages: int}
     */
    public static function findPaginated(int $page = 1, int $perPage = 10, ?int $categoryId = null, string $search = ''): array
    {
        $db = self::db();
        $where = '1=1';
        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $where .= ' AND o.id_categorie = :cat';
            $params[':cat'] = $categoryId;
        }
        if ($search !== '') {
            $where .= ' AND o.nom_objet LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM objet_loisir o WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        // Data
        $sql = "SELECT o.*, c.nom_categorie, c.icone AS cat_icone
                FROM objet_loisir o
                LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
                WHERE {$where}
                ORDER BY o.nom_objet
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = array_map([self::class, 'hydrateAvailability'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /**
     * Find a single object by its primary key.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM objet_loisir WHERE id_objet = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::hydrateAvailability($row) : null;
    }

    /**
     * Search objects whose name contains the given string.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function searchByName(string $search): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM objet_loisir WHERE nom_objet LIKE :search ORDER BY nom_objet'
        );
        $stmt->execute([':search' => '%' . $search . '%']);

        return array_map([self::class, 'hydrateAvailability'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Count every leisure object.
     */
    public static function countAll(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM objet_loisir')->fetchColumn();
    }

    /**
     * Insert a new leisure object.
     *
     * @param array<string, mixed> $data Sanitised input data.
     */
    public static function create(array $data): bool
    {
        $payload = self::normalizeData($data);

        $stmt = self::db()->prepare(
            'INSERT INTO objet_loisir (nom_objet, type_objet, quantite, etat, disponibilite, description, id_categorie)
             VALUES (:nom_objet, :type_objet, :quantite, :etat, :disponibilite, :description, :id_categorie)'
        );

        return $stmt->execute($payload);
    }

    /**
     * Update an existing leisure object.
     *
     * @param array<string, mixed> $data Sanitised input data.
     */
    public static function updateById(int $id, array $data): bool
    {
        $payload = self::normalizeData($data);
        $payload['id_objet'] = $id;

        $stmt = self::db()->prepare(
            'UPDATE objet_loisir
             SET nom_objet     = :nom_objet,
                 type_objet    = :type_objet,
                 quantite      = :quantite,
                 etat          = :etat,
                 disponibilite = :disponibilite,
                 description   = :description,
                 id_categorie  = :id_categorie
             WHERE id_objet = :id_objet'
        );

        return $stmt->execute($payload);
    }

    /**
     * Delete a leisure object if it has no linked loans.
     *
     * @return array{success: bool, error?: string}
     */
    public static function deleteById(int $id): array
    {
        $db = self::db();

        $check = $db->prepare('SELECT COUNT(*) FROM pret WHERE id_objet = :id');
        $check->execute([':id' => $id]);

        if ((int) $check->fetchColumn() > 0) {
            return ['success' => false, 'error' => 'linked_to_loans'];
        }

        $stmt = $db->prepare('DELETE FROM objet_loisir WHERE id_objet = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1
            ? ['success' => true]
            : ['success' => false, 'error' => 'not_found'];
    }

    // ─── Helpers ───────────────────────────────────────────────

    /**
     * Normalise form data into the shape expected by INSERT/UPDATE.
     *
     * @return array<string, mixed>
     */
    private static function normalizeData(array $data): array
    {
        $quantity = max(0, (int) ($data['quantite'] ?? 0));
        $catId    = isset($data['id_categorie']) && $data['id_categorie'] !== '' ? (int)$data['id_categorie'] : null;

        return [
            'nom_objet'     => trim($data['nom_objet'] ?? ''),
            'type_objet'    => trim($data['type_objet'] ?? ''),
            'quantite'      => $quantity,
            'etat'          => trim($data['etat'] ?? ''),
            'disponibilite' => $quantity > 0 ? 'disponible' : 'indisponible',
            'description'   => trim($data['description'] ?? ''),
            'id_categorie'  => $catId,
        ];
    }

    /**
     * Recalculate availability from the current quantity.
     *
     * @param  array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrateAvailability(array $row): array
    {
        $row['disponibilite'] = ((int) ($row['quantite'] ?? 0) > 0)
            ? 'disponible'
            : 'indisponible';

        return $row;
    }
}
