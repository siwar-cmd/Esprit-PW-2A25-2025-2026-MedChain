-- Database: 2A25
CREATE DATABASE IF NOT EXISTS 2A25 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE 2A25;

-- Table: objet_loisir
CREATE TABLE IF NOT EXISTS objet_loisir (
    id_objet INT AUTO_INCREMENT PRIMARY KEY,
    nom_objet VARCHAR(255) NOT NULL,
    type_objet VARCHAR(100) NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    etat VARCHAR(50) NOT NULL DEFAULT 'bon',
    disponibilite VARCHAR(20) NOT NULL DEFAULT 'disponible',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: pret
CREATE TABLE IF NOT EXISTS pret (
    id_pret INT AUTO_INCREMENT PRIMARY KEY,
    id_objet INT NOT NULL,
    nom_patient VARCHAR(255) NOT NULL,
    date_pret DATE NOT NULL,
    date_retour_prevue DATE NOT NULL,
    date_retour_effective DATE NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_objet) REFERENCES objet_loisir(id_objet) ON DELETE CASCADE,
    INDEX idx_patient (nom_patient),
    INDEX idx_statut (statut),
    INDEX idx_dates (date_pret, date_retour_prevue)
);

-- Insert sample data for objet_loisir
INSERT INTO objet_loisir (nom_objet, type_objet, quantite, etat, disponibilite, description) VALUES
('Jeu de cartes', 'Jeu de société', 5, 'bon', 'disponible', 'Jeu de cartes standard 52 cartes'),
('Monopoly', 'Jeu de société', 2, 'bon', 'disponible', 'Jeu de plateau Monopoly classique'),
('Ballon de football', 'Sport', 3, 'bon', 'disponible', 'Ballon de football taille standard'),
('Raquette de tennis', 'Sport', 4, 'bon', 'disponible', 'Raquette de tennis professionnelle'),
('Livre: Harry Potter', 'Livre', 10, 'bon', 'disponible', 'Harry Potter à l''école des sorciers'),
('Casse-tête 1000 pièces', 'Casse-tête', 3, 'bon', 'disponible', 'Paysage montagneux 1000 pièces'),
('Guitare acoustique', 'Musique', 1, 'bon', 'disponible', 'Guitare acoustique avec étui'),
('Tablette numérique', 'Électronique', 2, 'bon', 'disponible', 'Tablette pour jeux et divertissement'),
('Échecs', 'Jeu de société', 4, 'bon', 'disponible', 'Jeu d''échecs en bois'),
('Film DVD: Titanic', 'Film', 1, 'bon', 'indisponible', 'Film Titanic actuellement en prêt');

-- Insert sample data for pret
INSERT INTO pret (id_objet, nom_patient, date_pret, date_retour_prevue, statut) VALUES
(10, 'Dupont Jean', '2024-01-15', '2024-01-22', 'en_cours'),
(1, 'Martin Sophie', '2024-01-16', '2024-01-23', 'en_attente'),
(3, 'Bernard Pierre', '2024-01-10', '2024-01-17', 'termine'),
(5, 'Durand Marie', '2024-01-14', '2024-01-21', 'en_cours'),
(7, 'Petit Thomas', '2024-01-12', '2024-01-19', 'en_attente');

-- Update some objects availability based on pret status
UPDATE objet_loisir SET disponibilite = 'indisponible' WHERE id_objet = 10;
UPDATE objet_loisir SET disponibilite = 'indisponible' WHERE id_objet = 1;
UPDATE objet_loisir SET disponibilite = 'indisponible' WHERE id_objet = 3;
UPDATE objet_loisir SET disponibilite = 'indisponible' WHERE id_objet = 5;
UPDATE objet_loisir SET disponibilite = 'indisponible' WHERE id_objet = 7;
