<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../src/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';

$sql = "SELECT id, nom, description, prix, stock, image FROM produits WHERE stock > 0";
$params = [];

if ($categorie) {
    $sql .= " AND categorie = ?";
    $params[] = $categorie;
}

if (!empty($recherche)) {
    $sql .= " AND (nom LIKE ? OR description LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$sql .= " ORDER BY nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'produits' => $produits,
    'total' => count($produits)
]);