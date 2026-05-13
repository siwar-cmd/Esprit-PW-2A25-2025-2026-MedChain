<?php

class ObjetLoisir
{
    private $id_objet;
    private $nom_objet;
    private $type_objet;
    private $quantite;
    private $etat;
    private $disponibilite;
    private $description;
    private $id_categorie;

    public function __construct(
        $id_objet = null,
        $nom_objet = null,
        $type_objet = null,
        $quantite = null,
        $etat = null,
        $disponibilite = null,
        $description = null,
        $id_categorie = null
    ) {
        $this->id_objet     = $id_objet;
        $this->nom_objet    = $nom_objet;
        $this->type_objet   = $type_objet;
        $this->quantite     = $quantite;
        $this->etat         = $etat;
        $this->disponibilite = $disponibilite;
        $this->description  = $description;
        $this->id_categorie = $id_categorie;
    }

    public function getId()           { return $this->id_objet; }
    public function getNom()          { return $this->nom_objet; }
    public function getType()         { return $this->type_objet; }
    public function getQuantite()     { return $this->quantite; }
    public function getEtat()         { return $this->etat; }
    public function getDisponibilite(){ return $this->disponibilite; }
    public function getDescription()  { return $this->description; }
    public function getIdCategorie()  { return $this->id_categorie; }

    public function setId($id)                   { $this->id_objet = $id; }
    public function setNom($nom)                 { $this->nom_objet = $nom; }
    public function setType($type)               { $this->type_objet = $type; }
    public function setQuantite($quantite)       { $this->quantite = $quantite; }
    public function setEtat($etat)               { $this->etat = $etat; }
    public function setDisponibilite($dispo)     { $this->disponibilite = $dispo; }
    public function setDescription($description) { $this->description = $description; }
    public function setIdCategorie($id)          { $this->id_categorie = $id; }

    /**
     * Fetch available objects matching a type_objet category.
     * Only returns rows where quantite > 0 and disponibilite = 'disponible'.
     *
     * @param string $category  Maps to type_objet column (e.g. 'Livre', 'Film')
     * @param int    $limit
     * @return array
     */
    public static function getRecommendedObjects(string $category, int $limit = 5): array
    {
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'SELECT id_objet, nom_objet, type_objet, etat, description, image_url
             FROM objet_loisir
             WHERE type_objet = :cat
               AND quantite > 0
               AND disponibilite = \'disponible\'
             ORDER BY quantite DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':cat', $category, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
