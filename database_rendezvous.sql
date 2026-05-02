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
  `tarifConsultation` float DEFAULT NULL,
  `modeRemboursement` varchar(100) DEFAULT NULL,
  `emailEnvoye` tinyint(1) DEFAULT 0,
  `calendrierAjoute` tinyint(1) DEFAULT 0,
  `antecedents` varchar(255) DEFAULT NULL,
  `allergies` varchar(255) DEFAULT NULL,
  `motifPrincipal` varchar(255) DEFAULT NULL,
  `modeConsultation` varchar(50) DEFAULT 'Présentiel',
  `statutPaiement` varchar(50) DEFAULT 'En attente',
  `tensionArterielle` varchar(20) DEFAULT NULL,
  `poids` float DEFAULT NULL,
  `taille` int(11) DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `examensComplementaires` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `prochainRDV` date DEFAULT NULL,
  PRIMARY KEY (`idFiche`),
  UNIQUE KEY `fk_rdv` (`idRDV`),
  CONSTRAINT `fk_rdv` FOREIGN KEY (`idRDV`) REFERENCES `rendezvous` (`idRDV`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table documents_rendezvous
CREATE TABLE IF NOT EXISTS `documents_rendezvous` (
  `idDocument` int(11) NOT NULL AUTO_INCREMENT,
  `idRDV` int(11) NOT NULL,
  `nomFichier` varchar(255) NOT NULL,
  `cheminFichier` varchar(255) NOT NULL,
  `typeFichier` varchar(100) DEFAULT NULL,
  `dateUpload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idDocument`),
  CONSTRAINT `fk_doc_rdv` FOREIGN KEY (`idRDV`) REFERENCES `rendezvous` (`idRDV`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

