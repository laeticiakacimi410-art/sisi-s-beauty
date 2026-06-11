<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . "/../../src/config/database.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $prestation_id = isset($_GET['prestation_id']) ? (int)$_GET['prestation_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    
    if (!$prestation_id || !$date) {
        echo json_encode(['error' => 'Paramètres manquants']);
        exit();
    }
    
    
    $stmt = $pdo->prepare("SELECT duree, nom FROM prestations WHERE id = ?");
    $stmt->execute([$prestation_id]);
    $prestation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prestation) {
        echo json_encode(['error' => 'Prestation non trouvée']);
        exit();
    }
    
    $duree_prestation = $prestation['duree'];
    
    
    $jours_fr = [
        'Monday' => 'lundi',
        'Tuesday' => 'mardi',
        'Wednesday' => 'mercredi',
        'Thursday' => 'jeudi',
        'Friday' => 'vendredi',
        'Saturday' => 'samedi',
        'Sunday' => 'dimanche'
    ];
    
    $jour_semaine = $jours_fr[date('l', strtotime($date))];
    
    
    $stmt = $pdo->prepare("
        SELECT heure_debut, heure_fin, intervalle 
        FROM horaires_ouverture 
        WHERE jour = ? AND est_actif = 1
    ");
    $stmt->execute([$jour_semaine]);
    $horaires = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$horaires) {
        echo json_encode(['error' => 'Le salon est fermé ce jour-là']);
        exit();
    }
    
    
    $debut = new DateTime($horaires['heure_debut']);
    $fin = new DateTime($horaires['heure_fin']);
    $intervalle = (int)$horaires['intervalle'];
    
    $tous_slots = [];
    while ($debut < $fin) {
        $slot_fin = clone $debut;
        $slot_fin->modify("+{$duree_prestation} minutes");
        
        if ($slot_fin <= $fin) {
            $tous_slots[] = $debut->format('H:i');
        }
        $debut->modify("+{$intervalle} minutes");
    }
    
    
    $stmt = $pdo->prepare("
        SELECT heure FROM rendez_vous 
        WHERE date_rdv = ? AND statut != 'annule'
    ");
    $stmt->execute([$date]);
    $slots_pris = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    
    $slots_disponibles = array_diff($tous_slots, $slots_pris);
    
    echo json_encode([
        'success' => true,
        'slots' => array_values($slots_disponibles),
        'duree' => $duree_prestation
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>