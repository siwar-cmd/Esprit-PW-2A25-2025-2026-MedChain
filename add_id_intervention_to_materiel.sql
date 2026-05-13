-- ============================================
-- AJOUTER id_intervention comme clé étrangère dans la table materiel
-- ============================================
-- À exécuter dans phpMyAdmin ou CLI MySQL

ALTER TABLE materiel
ADD COLUMN id_intervention INT NULL AFTER categorie,
ADD INDEX idx_id_intervention (id_intervention),
ADD CONSTRAINT fk_materiel_intervention
    FOREIGN KEY (id_intervention) REFERENCES intervention(id) ON DELETE SET NULL ON UPDATE CASCADE;
