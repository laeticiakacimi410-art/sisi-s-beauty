<?php

require_once __DIR__ . "/../repositories/ReservationRepository.php";

class ReservationService {

    private $pdo;
    private $repo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->repo = new ReservationRepository($pdo);
    }

    public function createReservation($user_id, $prestation_id, $date, $heure, $employe_id = null, $commentaires = null) {

        if (!$user_id || !$prestation_id || !$date || !$heure) {
            return ["error" => "Champs manquants"];
        }

        
        $stmt = $this->pdo->prepare("SELECT nom FROM prestations WHERE id = ?");
        $stmt->execute([$prestation_id]);
        $prestation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prestation) {
            return ["error" => "Prestation introuvable"];
        }

        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM rendez_vous 
            WHERE date_rdv = ? AND heure = ? AND statut != 'annule'
        ");
        $stmt->execute([$date, $heure]);

        if ($stmt->fetchColumn() > 0) {
            return ["error" => "Créneau déjà réservé"];
        }

        
        $id = $this->repo->create(
            $user_id,
            $prestation_id,
            $date,
            $heure,
            $employe_id,
            $commentaires
        );

        if (!$id) {
            return ["error" => "Erreur insertion"];
        }

        return [
            "success" => true,
            "reservation_id" => $id,
            "prestation" => $prestation['nom']
        ];
    }
}