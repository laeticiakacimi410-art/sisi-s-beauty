<?php

require_once __DIR__ . "/../config/database.php";

class ReservationController {

    private $pdo;

    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }

    public function createReservation($user_id, $prestation_id, $date) {

        if (empty($user_id) || empty($prestation_id) || empty($date)) {
            return ["error" => "Champs manquants"];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO reservations (user_id, prestation_id, date)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$user_id, $prestation_id, $date]);

        return ["success" => "Réservation confirmée"];
    }

    public function getUserReservations($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM reservations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}