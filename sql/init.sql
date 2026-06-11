-- ============================================
-- SISI'S BEAUTY DATABASE
-- INIT SQL (selon diagramme)
-- ============================================

CREATE DATABASE IF NOT EXISTS sisis_beauty;
USE sisis_beauty;

-- ============================================
-- 1. UTILISATEURS
-- ============================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    role VARCHAR(10) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. PRESTATIONS
-- ============================================
CREATE TABLE prestations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    duree INT,
    categorie VARCHAR(100),
    image VARCHAR(255),
    type_prestation VARCHAR(50) DEFAULT 'autre'
);

-- ============================================
-- 3. PRODUITS
-- ============================================
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    categorie VARCHAR(100)
);

-- ============================================
-- 4. COMMANDES
-- ============================================
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_commande VARCHAR(50),
    utilisateur_id INT NOT NULL,
    total DECIMAL(10,2) DEFAULT 0,
    statut VARCHAR(20) DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ============================================
-- 5. LIGNES_COMMANDES (liaison commande/produit)
-- ============================================
CREATE TABLE lignes_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- ============================================
-- 6. COMMANDES_DETAILS (optionnelle - copie au moment de la commande)
-- ============================================
CREATE TABLE commandes_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    nom_produit VARCHAR(150) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- ============================================
-- 7. RENDEZ-VOUS
-- ============================================
CREATE TABLE rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    prestation_id INT NOT NULL,
    date_rdv DATE NOT NULL,
    heure TIME NOT NULL,
    statut VARCHAR(20) DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
);

-- ============================================
-- 8. MESSAGES_CONTACT
-- ============================================
CREATE TABLE messages_contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telephone VARCHAR(20) NULL,
    sujet VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT DEFAULT 0,
    repondu TINYINT DEFAULT 0,
    date_reponse DATETIME NULL,
    reponse_telephone TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 9. HORAIRES_OUVERTURE
-- ============================================
CREATE TABLE horaires_ouverture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jour ENUM('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    intervalle INT DEFAULT 30,
    est_actif TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 10. DISPONIBILITES
-- ============================================
CREATE TABLE disponibilites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_rdv DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    est_disponible TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_creneau (date_rdv, heure_debut)
);

-- ============================================
-- 11. FERMETURES
-- ============================================
CREATE TABLE fermetures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_fermeture DATE NOT NULL,
    motif VARCHAR(255) NULL,
    UNIQUE KEY unique_fermeture (date_fermeture)
);

-- ============================================
-- ADMIN PAR DEFAUT
-- Mot de passe: admin123
-- ============================================
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role)
VALUES (
    'Admin',
    'Sisi',
    'admin@sisisbeauty.com',
    '$2y$10$I61epspzfwtsJeGtI6tPEu/XsxtCH/oDUL1rvE7tapp9zn0OtH14C',
    '0600000000',
    'admin'
);

-- ============================================
-- HORAIRES PAR DEFAUT
-- ============================================
INSERT INTO horaires_ouverture (jour, heure_debut, heure_fin, intervalle, est_actif) VALUES
('lundi', '09:00', '19:00', 30, 0),
('mardi', '09:00', '19:00', 30, 1),
('mercredi', '09:00', '19:00', 30, 1),
('jeudi', '09:00', '19:00', 30, 1),
('vendredi', '09:00', '19:00', 30, 1),
('samedi', '09:00', '18:00', 30, 1),
('dimanche', '09:00', '18:00', 30, 0);