<?php

class UserRepository {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM utilisateurs 
            WHERE email = ?
        ");

        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    public function createUser($nom, $prenom, $email, $password) {
        $stmt = $this->pdo->prepare("
            INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, rolee, created_at)
            VALUES (?, ?, ?, ?, 'user', NOW())
        ");

        return $stmt->execute([$nom, $prenom, $email, $password]);
    }

    
    public function findById($id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM utilisateurs 
            WHERE id = ?
        ");

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAll()
    {
        $query = $this->pdo->query("SELECT * FROM utilisateurs ORDER BY created_at DESC");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function update($id, $nom, $prenom, $email, $telephone = '', $rolee = 'user') {
        $stmt = $this->pdo->prepare("
            UPDATE utilisateurs 
            SET nom = ?, prenom = ?, email = ?, telephone = ?, rolee = ?
            WHERE id = ?
        ");

        return $stmt->execute([$nom, $prenom, $email, $telephone, $rolee, $id]);
    }

    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}