<?php

class ProduitRepository {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    
    public function findAll() {
        $stmt = $this->pdo->query("SELECT * FROM produits");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM produits WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    public function create($nom, $description, $prix, $stock = 0, $categorie = '', $image = '') {
        $stmt = $this->pdo->prepare("
            INSERT INTO produits (nom, description, prix, stock, categorie, image)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$nom, $description, $prix, $stock, $categorie, $image]);
    }

    
    public function update($id, $nom, $description, $prix, $stock = 0, $categorie = '', $image = '') {
        $stmt = $this->pdo->prepare("
            UPDATE produits
            SET nom = ?, description = ?, prix = ?, stock = ?, categorie = ?, image = ?
            WHERE id = ?
        ");

        return $stmt->execute([$nom, $description, $prix, $stock, $categorie, $image, $id]);
    }

    
    public function delete($id) {
        $stmt = $this->pdo->prepare("
            DELETE FROM produits WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}