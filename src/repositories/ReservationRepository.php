<?php

class ReservationRepository {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    
    public function create($user_id, $prestation_id, $date, $heure, $employe_id = null, $commentaires = null) {

        $stmt = $this->pdo->prepare("
            INSERT INTO rendez_vous 
            (utilisateur_id, prestation_id, date_rdv, heure, employe_id, commentaires, statut, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'en_attente', NOW())
        ");

        $ok = $stmt->execute([
            $user_id,
            $prestation_id,
            $date,
            $heure,
            $employe_id,
            $commentaires
        ]);

        if (!$ok) {
            return false;
        }

        return $this->pdo->lastInsertId();
    }

    
    public function findByUserId($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, p.nom AS prestation_nom, p.duree, p.prix
            FROM rendez_vous r
            JOIN prestations p ON r.prestation_id = p.id
            WHERE r.utilisateur_id = ?
            ORDER BY r.date_rdv DESC, r.heure DESC
        ");

        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM rendez_vous WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM rendez_vous WHERE id = ?");
        return $stmt->execute([$id]);
    }

    
    public function getTakenSlots($date) {
        $stmt = $this->pdo->prepare("
            SELECT heure 
            FROM rendez_vous 
            WHERE date_rdv = ? AND statut != 'annule'
        ");

        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    
    public function updateStatus($id, $statut) {
        $stmt = $this->pdo->prepare("
            UPDATE rendez_vous 
            SET statut = ? 
            WHERE id = ?
        ");

        return $stmt->execute([$statut, $id]);
    }
}