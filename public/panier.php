<?php
session_start();
include 'header.php';
require_once "../src/config/database.php";

$db = new Database();
$pdo = $db->getConnection();


if (!isset($_SESSION["panier"])) {
    $_SESSION["panier"] = [];
}




if (isset($_GET["add"])) {
    $id = (int)$_GET["add"];
    $quantite = isset($_GET["qte"]) ? (int)$_GET["qte"] : 1;
    
    
    $stmt = $pdo->prepare("SELECT id, nom, prix, stock, image FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produit) {
        $found = false;
        foreach ($_SESSION["panier"] as $key => &$item) {
            if ($item["id"] == $id) {
                $_SESSION["panier"][$key]["quantite"] += $quantite;
                if ($_SESSION["panier"][$key]["quantite"] > $produit['stock']) {
                    $_SESSION["panier"][$key]["quantite"] = $produit['stock'];
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION["panier"][] = [
                "id" => $produit['id'],
                "nom" => $produit['nom'],
                "prix" => floatval($produit['prix']),
                "quantite" => min($quantite, $produit['stock']),
                "image" => $produit['image'],
                "stock" => $produit['stock']
            ];
        }
    }
    
    header("Location: panier.php");
    exit();
}


if (isset($_GET["remove"])) {
    $id = (int)$_GET["remove"];
    
    foreach ($_SESSION["panier"] as $key => $item) {
        if ($item["id"] == $id) {
            unset($_SESSION["panier"][$key]);
            break;
        }
    }
    
    $_SESSION["panier"] = array_values($_SESSION["panier"]);
    header("Location: panier.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    header('Content-Type: application/json');
    
    $id = (int)$_POST['id'];
    $quantite = (int)$_POST['quantite'];
    
    foreach ($_SESSION["panier"] as &$item) {
        if ($item["id"] == $id) {
            $item["quantite"] = max(1, min($quantite, $item['stock']));
            break;
        }
    }
    
    
    $total = 0;
    foreach ($_SESSION["panier"] as $item) {
        $total += $item["prix"] * $item["quantite"];
    }
    
    echo json_encode(['success' => true, 'total' => $total]);
    exit();
}


if (isset($_GET['clear'])) {
    $_SESSION["panier"] = [];
    header("Location: panier.php");
    exit();
}


$total = 0;
$total_articles = 0;

foreach ($_SESSION["panier"] as $item) {
    $total += $item["prix"] * $item["quantite"];
    $total_articles += $item["quantite"];
}


$frais_livraison = ($total > 50) ? 0 : 5.90;
$total_ttc = $total + $frais_livraison;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/panier.css">
</head>
<body>

<div class="panier-page">
    <div class="container">
        
        <div class="panier-header">
            <h1>Mon Panier</h1>
            <p><?= $total_articles ?> article(s) dans votre panier</p>
        </div>
        
        <?php if (empty($_SESSION["panier"])): ?>
            <div class="panier-vide">
                <div class="empty-icon"></div>
                <h2>Votre panier est vide</h2>
                <p>Découvrez nos produits et ajoutez-les à votre panier.</p>
                <a href="boutique.php" class="btn-continuer">Découvrir la boutique</a>
            </div>
        <?php else: ?>
            
            <div class="panier-container">
                
                <div class="panier-produits">
                    <div class="panier-table-header">
                        <div class="col-produit">Produit</div>
                        <div class="col-prix">Prix unitaire</div>
                        <div class="col-quantite">Quantité</div>
                        <div class="col-total">Total</div>
                        <div class="col-actions"></div>
                    </div>
                    
                    <div id="panierItems">
                        <?php foreach ($_SESSION["panier"] as $item): ?>
                            <div class="panier-item" data-id="<?= $item['id'] ?>">
                                <div class="col-produit">
                                    <div class="produit-info">
                                        <?php 
                                        if (!empty($item['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/produits/' . $item['image'])): 
                                        ?>
                                            <img src="/uploads/produits/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['nom']) ?>" class="produit-image">
                                        <?php else: ?>
                                            <div class="produit-image-placeholder"></div>
                                        <?php endif; ?>
                                        <div class="produit-details">
                                            <h3><?= htmlspecialchars($item['nom']) ?></h3>
                                            <?php if ($item['stock'] <= 5): ?>
                                                <span class="stock-warning"> Stock limité</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-prix">
                                    <span class="prix-unitaire"><?= number_format($item['prix'], 2) ?> €</span>
                                </div>
                                <div class="col-quantite">
                                    <div class="quantite-control">
                                        <button class="qte-btn qte-moins" data-id="<?= $item['id'] ?>">-</button>
                                        <input type="number" class="qte-input" data-id="<?= $item['id'] ?>" value="<?= $item['quantite'] ?>" min="1" max="<?= $item['stock'] ?>">
                                        <button class="qte-btn qte-plus" data-id="<?= $item['id'] ?>">+</button>
                                    </div>
                                </div>
                                <div class="col-total">
                                    <span class="total-ligne"><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</span>
                                </div>
                                <div class="col-actions">
                                    <button class="btn-remove" data-id="<?= $item['id'] ?>" title="Supprimer">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="panier-actions">
                        <a href="boutique.php" class="btn-continuer-shopping">
                            ← Continuer mes achats
                        </a>
                        <button id="viderPanier" class="btn-vider">
                            Vider le panier
                        </button>
                    </div>
                </div>
                
                
                <div class="panier-resume">
                    <h3>Récapitulatif</h3>
                    
                    <div class="resume-ligne">
                        <span>Sous-total</span>
                        <span id="sousTotal"><?= number_format($total, 2) ?> €</span>
                    </div>
                    
                    <div class="resume-ligne">
                        <span>Livraison</span>
                        <span id="fraisLivraison">
                            <?php if ($frais_livraison > 0): ?>
                                <?= number_format($frais_livraison, 2) ?> €
                            <?php else: ?>
                                Gratuite
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($frais_livraison > 0 && $total < 50): ?>
                        <div class="resume-livraison-offerte">
                            💫 Plus que <?= number_format(50 - $total, 2) ?> € pour la livraison gratuite !
                        </div>
                    <?php endif; ?>
                    
                    <div class="resume-total">
                        <span>Total TTC</span>
                        <span id="totalGeneral"><?= number_format($total_ttc, 2) ?> €</span>
                    </div>
                    
                    <a href="commander.php" class="btn-valider">
                        Valider la commande
                    </a>
                    
                    <p class="payment-info">
                        Paiement sécurisé • Livraison sous 3-5 jours
                    </p>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<script src="/js/panier.js"></script>

<?php include 'footer.php'; ?>