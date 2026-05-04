-- ============================================================
--  MedChain — UNIFIED Database Schema (V3.0)
--  Database: medchain
--  Generated: 2026-05-04
--
--  Modules: utilisateur · rendezvous · ficherendezvous
--           ambulance · mission · categorie_objet
--           objet_loisir · pret
--
--  All cross-module FK references point to utilisateur.id_utilisateur.
--  Legacy nom_patient / string-based user IDs fully removed.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `medchain`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE `medchain`;

-- ============================================================
--  1.  UTILISATEUR  (core — all FKs depend on this)
-- ============================================================
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_utilisateur`        INT(11)       NOT NULL AUTO_INCREMENT,
  `nom`                   VARCHAR(100)  NOT NULL,
  `prenom`                VARCHAR(100)  NOT NULL,
  `email`                 VARCHAR(255)  NOT NULL UNIQUE,
  `mot_de_passe`          VARCHAR(255)  NOT NULL,
  `dateNaissance`         DATE          DEFAULT NULL,
  `adresse`               VARCHAR(255)  DEFAULT NULL,
  `telephone`             VARCHAR(20)   DEFAULT NULL,
  `date_inscription`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role`                  ENUM('admin','patient','medecin')          NOT NULL DEFAULT 'patient',
  `statut`                ENUM('actif','inactif','en_attente')       NOT NULL DEFAULT 'actif',
  `reset_token`           VARCHAR(255)  DEFAULT NULL,
  `reset_token_expires`   DATETIME      DEFAULT NULL,
  `historique_connexions` TEXT          DEFAULT NULL      COMMENT 'JSON array of login timestamps',
  `derniere_connexion`    DATETIME      DEFAULT NULL,
  `photo_profil`          VARCHAR(255)  DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  KEY `idx_utilisateur_email`  (`email`),
  KEY `idx_utilisateur_role`   (`role`),
  KEY `idx_utilisateur_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  2.  RENDEZVOUS
-- ============================================================
CREATE TABLE IF NOT EXISTS `rendezvous` (
  `idRDV`            INT(11)      NOT NULL AUTO_INCREMENT,
  `dateHeureDebut`   DATETIME     DEFAULT NULL,
  `dateHeureFin`     DATETIME     DEFAULT NULL,
  `statut`           ENUM('planifie','confirme','annule','termine')
                                  NOT NULL DEFAULT 'planifie',
  `typeConsultation` VARCHAR(100) DEFAULT NULL,
  `motif`            TEXT         DEFAULT NULL,
  `idClient`         INT(11)      NOT NULL  COMMENT 'FK → utilisateur (patient)',
  `idMedecin`        INT(11)      NOT NULL  COMMENT 'FK → utilisateur (medecin)',
  PRIMARY KEY (`idRDV`),
  KEY `idx_rdv_client`  (`idClient`),
  KEY `idx_rdv_medecin` (`idMedecin`),
  CONSTRAINT `fk_rdv_client`
      FOREIGN KEY (`idClient`)  REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `fk_rdv_medecin`
      FOREIGN KEY (`idMedecin`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  3.  FICHE RENDEZVOUS
-- ============================================================
CREATE TABLE IF NOT EXISTS `ficherendezvous` (
  `idFiche`                    INT(11)      NOT NULL AUTO_INCREMENT,
  `idRDV`                      INT(11)      DEFAULT NULL,
  `dateGeneration`             DATE         DEFAULT NULL,
  `piecesAApporter`            TEXT         DEFAULT NULL,
  `consignesAvantConsultation` TEXT         DEFAULT NULL,
  `tarifConsultation`          DECIMAL(8,2) DEFAULT NULL,
  `modeRemboursement`          VARCHAR(100) DEFAULT NULL,
  `emailEnvoye`                TINYINT(1)   NOT NULL DEFAULT 0,
  `calendrierAjoute`           TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`idFiche`),
  KEY `idx_fiche_rdv` (`idRDV`),
  CONSTRAINT `fk_fiche_rdv`
      FOREIGN KEY (`idRDV`) REFERENCES `rendezvous`(`idRDV`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  4.  AMBULANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS `ambulance` (
  `idAmbulance`    INT(11)      NOT NULL AUTO_INCREMENT,
  `immatriculation` VARCHAR(30) NOT NULL UNIQUE,
  `statut`         VARCHAR(50)  NOT NULL DEFAULT 'En service',
  `modele`         VARCHAR(100) NOT NULL,
  `capacite`       INT(11)      NOT NULL DEFAULT 2,
  `estDisponible`  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`idAmbulance`),
  KEY `idx_ambulance_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  5.  MISSION
-- ============================================================
CREATE TABLE IF NOT EXISTS `mission` (
  `idMission`   INT(11)      NOT NULL AUTO_INCREMENT,
  `dateDebut`   DATETIME     DEFAULT NULL,
  `dateFin`     DATETIME     DEFAULT NULL,
  `typeMission` VARCHAR(100) NOT NULL,
  `lieuDepart`  VARCHAR(255) DEFAULT NULL,
  `lieuArrivee` VARCHAR(255) DEFAULT NULL,
  `equipe`      VARCHAR(255) DEFAULT NULL,
  `estTerminee` TINYINT(1)   NOT NULL DEFAULT 0,
  `idAmbulance` INT(11)      NOT NULL,
  PRIMARY KEY (`idMission`),
  KEY `idx_mission_ambulance` (`idAmbulance`),
  CONSTRAINT `fk_mission_ambulance`
      FOREIGN KEY (`idAmbulance`) REFERENCES `ambulance`(`idAmbulance`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  6.  CATEGORIE_OBJET
-- ============================================================
CREATE TABLE IF NOT EXISTS `categorie_objet` (
  `id_categorie`  INT(11)      NOT NULL AUTO_INCREMENT,
  `nom_categorie` VARCHAR(100) NOT NULL,
  `description`   TEXT         DEFAULT NULL,
  `icone`         VARCHAR(50)  NOT NULL DEFAULT 'bi-tag'
                  COMMENT 'Bootstrap Icon class name',
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  7.  OBJET_LOISIR
-- ============================================================
CREATE TABLE IF NOT EXISTS `objet_loisir` (
  `id_objet`      INT(11)      NOT NULL AUTO_INCREMENT,
  `nom_objet`     VARCHAR(100) NOT NULL,
  `type_objet`    VARCHAR(50)  NOT NULL
                  COMMENT 'Livre | Jeu de societe | Sport | Musique | Electronique | Casse-tete | Film',
  `quantite`      INT(11)      NOT NULL DEFAULT 0,
  `etat`          ENUM('neuf','bon','acceptable','moyen','use')
                               NOT NULL DEFAULT 'neuf',
  `disponibilite` ENUM('disponible','indisponible')
                               NOT NULL DEFAULT 'indisponible',
  `description`   TEXT         DEFAULT NULL,
  `image`         VARCHAR(255) DEFAULT NULL,
  `id_categorie`  INT(11)      DEFAULT NULL,
  PRIMARY KEY (`id_objet`),
  KEY `idx_objet_categorie`    (`id_categorie`),
  KEY `idx_objet_disponibilite`(`disponibilite`),
  CONSTRAINT `fk_objet_categorie`
      FOREIGN KEY (`id_categorie`) REFERENCES `categorie_objet`(`id_categorie`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  8.  PRET  (key integration point: id_patient + id_medecin → utilisateur)
--  NOTE: Legacy `nom_patient` VARCHAR column is REMOVED.
--        Patient identity comes exclusively from id_patient FK.
-- ============================================================
CREATE TABLE IF NOT EXISTS `pret` (
  `id_pret`               INT(11)      NOT NULL AUTO_INCREMENT,
  `id_objet`              INT(11)      NOT NULL,
  `id_patient`            INT(11)      NOT NULL  COMMENT 'FK → utilisateur (patient)',
  `id_medecin`            INT(11)      DEFAULT NULL COMMENT 'FK → utilisateur (medecin prescripteur)',
  `motif_emprunt`         TEXT         DEFAULT NULL,
  `motif_annulation`      TEXT         DEFAULT NULL,
  `date_pret`             DATE         NOT NULL,
  `date_retour_prevue`    DATE         DEFAULT NULL,
  `date_retour_effective` DATETIME     DEFAULT NULL,
  `statut`                ENUM('en_attente','en_cours','termine','annule','en_retard')
                                       NOT NULL DEFAULT 'en_attente',
  PRIMARY KEY (`id_pret`),
  KEY `idx_pret_patient`  (`id_patient`),
  KEY `idx_pret_objet`    (`id_objet`),
  KEY `idx_pret_statut`   (`statut`),
  CONSTRAINT `fk_pret_objet`
      FOREIGN KEY (`id_objet`)   REFERENCES `objet_loisir`(`id_objet`)      ON DELETE CASCADE,
  CONSTRAINT `fk_pret_patient`
      FOREIGN KEY (`id_patient`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `fk_pret_medecin`
      FOREIGN KEY (`id_medecin`) REFERENCES `utilisateur`(`id_utilisateur`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  SEED: Default admin account
--  Password: Admin@2025  (bcrypt cost-12)
--  CHANGE THIS PASSWORD immediately after first login!
-- ============================================================
INSERT IGNORE INTO `utilisateur`
  (nom, prenom, email, mot_de_passe, role, statut, date_inscription)
VALUES
  ('Admin', 'MedChain', 'admin@medchain.tn',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin', 'actif', NOW());

-- ============================================================
--  SEED: Object Categories
-- ============================================================
INSERT IGNORE INTO `categorie_objet` (`nom_categorie`, `description`, `icone`) VALUES
('Électronique',     'Tablettes, consoles, casques audio',       'bi-phone'),
('Livres',           'Romans, BD, magazines',                    'bi-book'),
('Jeux de société',  'Plateau, cartes, puzzles',                 'bi-dice-5'),
('Sport & Bien-être','Ballons, tapis yoga, haltères',            'bi-heart-pulse'),
('Musique',          'Instruments, enceintes',                   'bi-music-note-beamed'),
('Films & Séries',   'DVD, Blu-ray',                             'bi-film'),
('Matériel Médical', 'Équipement de rééducation, casse-têtes',   'bi-bandaid');

-- ============================================================
--  SEED: Leisure Objects
-- ============================================================
INSERT IGNORE INTO `objet_loisir`
  (`nom_objet`, `type_objet`, `quantite`, `etat`, `disponibilite`, `description`, `id_categorie`)
VALUES
  ('Le Petit Prince',        'Livre',          3, 'bon',        'disponible', 'Classique de Saint-Exupéry',   2),
  ('Monopoly',               'Jeu de societe', 2, 'bon',        'disponible', 'Jeu de plateau familial',      3),
  ('Ballon de Football',     'Sport',          5, 'neuf',       'disponible', 'Taille 5 officielle',          4),
  ('Guitare Acoustique',     'Musique',        1, 'acceptable', 'disponible', 'Guitare pour débutants',       5),
  ('Tablette Samsung',       'Electronique',   2, 'neuf',       'disponible', 'Galaxy Tab A9 – usage loisir', 1),
  ('Rubik''s Cube 3x3',     'Casse-tete',     4, 'neuf',       'disponible', 'Cube classique',               7),
  ('Les Intouchables (DVD)', 'Film',           3, 'bon',        'disponible', 'Comédie dramatique française', 6);

-- ============================================================
--  SEED: Ambulances
-- ============================================================
INSERT IGNORE INTO `ambulance`
  (`immatriculation`, `statut`, `modele`, `capacite`, `estDisponible`)
VALUES
  ('TU-100-001', 'En service',     'Mercedes Sprinter 316 CDI', 4, 1),
  ('TU-100-002', 'En service',     'Volkswagen Crafter 35',     3, 1),
  ('TU-100-003', 'En maintenance', 'Ford Transit Custom',       2, 0),
  ('TU-100-004', 'Hors service',   'Renault Master L3H2',       4, 0),
  ('TU-100-005', 'En service',     'Peugeot Boxer 335',         2, 1);
