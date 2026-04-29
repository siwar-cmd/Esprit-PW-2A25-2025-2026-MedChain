-- Table rendezvous
CREATE TABLE IF NOT EXISTS `rendezvous` (
  `idRDV` int(11) NOT NULL AUTO_INCREMENT,
  `dateHeureDebut` datetime DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'planifie',
  `typeConsultation` varchar(100) DEFAULT NULL,
  `motif` text DEFAULT NULL,
  `idClient` int(11) NOT NULL,
  `idMedecin` int(11) NOT NULL,
  PRIMARY KEY (`idRDV`),
  KEY `fk_client` (`idClient`),
  KEY `fk_medecin` (`idMedecin`),
  CONSTRAINT `fk_client` FOREIGN KEY (`idClient`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `fk_medecin` FOREIGN KEY (`idMedecin`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table ficherendezvous
CREATE TABLE IF NOT EXISTS `ficherendezvous` (
  `idFiche` int(11) NOT NULL AUTO_INCREMENT,
  `idRDV` int(11) DEFAULT NULL,
  `dateGeneration` date DEFAULT NULL,
  `piecesAApporter` text DEFAULT NULL,
  `consignesAvantConsultation` text DEFAULT NULL,
  `tarifConsultation` float DEFAULT NULL,
  `modeRemboursement` varchar(100) DEFAULT NULL,
  `emailEnvoye` tinyint(1) DEFAULT 0,
  `calendrierAjoute` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`idFiche`),
  UNIQUE KEY `fk_rdv` (`idRDV`),
  CONSTRAINT `fk_rdv` FOREIGN KEY (`idRDV`) REFERENCES `rendezvous` (`idRDV`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion de quelques données de test si l'utilisateur de test existe
-- Assurez-vous d'avoir au moins un utilisateur avec role='user' et un avec role='admin' ou un medecin
