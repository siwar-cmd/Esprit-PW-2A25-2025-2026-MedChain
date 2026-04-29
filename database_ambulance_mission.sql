-- ============================================================
--  MedChain – Flotte & Missions
--  Base de données : sante
--  Tables : ambulance, mission
-- ============================================================

USE sante;

-- ------------------------------------------------------------
--  Table : ambulance
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ambulance` (
  `idAmbulance`    INT(11)      NOT NULL AUTO_INCREMENT,
  `immatriculation` VARCHAR(30) NOT NULL,
  `statut`         VARCHAR(50)  NOT NULL DEFAULT 'En service'
                   COMMENT 'En service | Hors service | En maintenance',
  `modele`         VARCHAR(100) NOT NULL,
  `capacite`       INT(11)      NOT NULL DEFAULT 2,
  `estDisponible`  TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`idAmbulance`),
  UNIQUE KEY `uq_immatriculation` (`immatriculation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
--  Table : mission
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mission` (
  `idMission`   INT(11)      NOT NULL AUTO_INCREMENT,
  `dateDebut`   DATETIME     DEFAULT NULL,
  `dateFin`     DATETIME     DEFAULT NULL,
  `typeMission` VARCHAR(100) NOT NULL
                COMMENT 'Urgence | Transport | Rapatriement | Autre',
  `lieuDepart`  VARCHAR(255) DEFAULT NULL,
  `lieuArrivee` VARCHAR(255) DEFAULT NULL,
  `equipe`      VARCHAR(255) DEFAULT NULL,
  `estTerminee` TINYINT(1)   NOT NULL DEFAULT 0,
  `idAmbulance` INT(11)      NOT NULL,
  PRIMARY KEY (`idMission`),
  KEY `fk_mission_ambulance` (`idAmbulance`),
  CONSTRAINT `fk_mission_ambulance`
    FOREIGN KEY (`idAmbulance`)
    REFERENCES `ambulance` (`idAmbulance`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
--  Données de démonstration
-- ------------------------------------------------------------

INSERT INTO `ambulance` (`immatriculation`, `statut`, `modele`, `capacite`, `estDisponible`) VALUES
('TU-100-001', 'En service',     'Mercedes Sprinter 316 CDI', 4, 1),
('TU-100-002', 'En service',     'Volkswagen Crafter 35',     3, 1),
('TU-100-003', 'En maintenance', 'Ford Transit Custom',        2, 0),
('TU-100-004', 'Hors service',   'Renault Master L3H2',        4, 0),
('TU-100-005', 'En service',     'Peugeot Boxer 335',          2, 1);

INSERT INTO `mission`
  (`dateDebut`, `dateFin`, `typeMission`, `lieuDepart`, `lieuArrivee`, `equipe`, `estTerminee`, `idAmbulance`)
VALUES
('2026-04-20 08:00:00', '2026-04-20 09:30:00', 'Urgence',        'Ariana',        'Hôpital Charles Nicolle', 'Dr. Ben Ali, Inf. Saidi',    1, 1),
('2026-04-21 10:00:00', '2026-04-21 11:00:00', 'Transport',      'Tunis Centre',  'Clinique El Manar',       'Inf. Trabelsi, Inf. Hamdi',  1, 2),
('2026-04-22 14:00:00', NULL,                  'Rapatriement',   'Aéroport Tunis','Hôpital Mongi Slim',      'Dr. Mansour, Inf. Khalil',   0, 1),
('2026-04-23 09:00:00', '2026-04-23 10:15:00', 'Urgence',        'La Marsa',      'Hôpital Habib Thameur',   'Dr. Ouali, Inf. Jebali',     1, 5),
('2026-04-28 07:30:00', NULL,                  'Transport',      'Sfax',          'Hôpital Farhat Hached',   'Inf. Boudali',               0, 5);
