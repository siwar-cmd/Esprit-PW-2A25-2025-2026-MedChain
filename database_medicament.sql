CREATE TABLE IF NOT EXISTS `lot_medicament` (
  `id_lot` int(11) NOT NULL AUTO_INCREMENT,
  `nom_medicament` varchar(255) NOT NULL,
  `type_medicament` varchar(100) NOT NULL,
  `date_fabrication` date NOT NULL,
  `date_expiration` date NOT NULL,
  `quantite_initial` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id_lot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `distribution` (
  `id_distribution` int(11) NOT NULL AUTO_INCREMENT,
  `id_lot` int(11) NOT NULL,
  `date_distribution` date NOT NULL,
  `quantite_distribuee` int(11) NOT NULL,
  `patient` varchar(255) NOT NULL,
  `responsable` varchar(255) NOT NULL,
  PRIMARY KEY (`id_distribution`),
  KEY `fk_lot_medicament` (`id_lot`),
  CONSTRAINT `fk_lot_medicament` FOREIGN KEY (`id_lot`) REFERENCES `lot_medicament` (`id_lot`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
