-- ============================================================
--  MedChain V2.0 — Database Upgrade
--  Run AFTER database_unified.sql
-- ============================================================
USE `user`;

-- ────────────────────────────────────────────────────────────
--  1. CATEGORIE_OBJET — Object categories
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categorie_objet` (
  `id_categorie`  INT(11)      NOT NULL AUTO_INCREMENT,
  `nom_categorie` VARCHAR(100) NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `icone`         VARCHAR(50)  DEFAULT 'bi-tag'
                  COMMENT 'Bootstrap Icon class',
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categorie_objet` (`nom_categorie`, `description`, `icone`) VALUES
('Électronique',      'Tablettes, consoles, casques audio',         'bi-phone'),
('Livres',            'Romans, BD, magazines',                      'bi-book'),
('Jeux de société',   'Plateau, cartes, puzzles',                   'bi-dice-5'),
('Sport & Bien-être', 'Ballons, tapis yoga, haltères',             'bi-heart-pulse'),
('Musique',           'Instruments, enceintes',                     'bi-music-note-beamed'),
('Films & Séries',    'DVD, Blu-ray',                              'bi-film'),
('Matériel Médical',  'Équipement de rééducation, casse-têtes',    'bi-bandaid');

-- ────────────────────────────────────────────────────────────
--  2. ALTER objet_loisir — add category FK
-- ────────────────────────────────────────────────────────────
ALTER TABLE `objet_loisir`
  ADD COLUMN `id_categorie` INT(11) DEFAULT NULL AFTER `description`,
  ADD CONSTRAINT `fk_objet_categorie`
    FOREIGN KEY (`id_categorie`)
    REFERENCES `categorie_objet`(`id_categorie`)
    ON DELETE SET NULL;

-- Back-fill existing rows with best-guess category
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Livres' LIMIT 1)           WHERE type_objet = 'Livre';
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Jeux de société' LIMIT 1)  WHERE type_objet = 'Jeu de societe';
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Sport & Bien-être' LIMIT 1) WHERE type_objet = 'Sport';
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Musique' LIMIT 1)          WHERE type_objet = 'Musique';
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Électronique' LIMIT 1)     WHERE type_objet = 'Electronique';
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Matériel Médical' LIMIT 1) WHERE type_objet = 'Casse-tete';
UPDATE objet_loisir SET id_categorie = (SELECT id_categorie FROM categorie_objet WHERE nom_categorie = 'Films & Séries' LIMIT 1)   WHERE type_objet = 'Film';

-- ────────────────────────────────────────────────────────────
--  3. ALTER pret — add motif_annulation + en_retard status
-- ────────────────────────────────────────────────────────────
ALTER TABLE `pret`
  ADD COLUMN `motif_annulation` TEXT DEFAULT NULL AFTER `motif_emprunt`,
  MODIFY COLUMN `statut` VARCHAR(20) NOT NULL DEFAULT 'en_attente'
    COMMENT 'en_attente | en_cours | termine | annule | en_retard';
