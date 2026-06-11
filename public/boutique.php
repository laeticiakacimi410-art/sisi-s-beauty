<?php
session_start();
include 'header.php';
require_once "../src/config/database.php";

$db = new Database();
$pdo = $db->getConnection();


$categories = [
    'makeup' => 'Maquillage',
    'soins' => 'Soins du visage',
    'corps' => 'Soins du corps',
    'cheveux' => 'Soins cheveux',
    'accessoires' => 'Accessoires',
    'coffret' => 'Coffrets'
];


$categorie_active = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';


$sql = "SELECT * FROM produits WHERE stock > 0";
$params = [];

if ($categorie_active) {
    $sql .= " AND categorie = ?";
    $params[] = $categorie_active;
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


$compteurs = [];
foreach ($categories as $key => $label) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie = ? AND stock > 0");
    $stmt->execute([$key]);
    $compteurs[$key] = $stmt->fetchColumn();
}
$total_produits = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock > 0")->fetchColumn();


if (isset($_GET['ajouter']) && isset($_GET['id'])) {
    $produit_id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("SELECT id, nom, prix, stock, image FROM produits WHERE id = ? AND stock > 0");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produit) {
        if (!isset($_SESSION['panier'])) {
            $_SESSION['panier'] = [];
        }
        
        $found = false;
        foreach ($_SESSION['panier'] as $key => $item) {
            if ($item['id'] == $produit_id) {
                if ($_SESSION['panier'][$key]['quantite'] + 1 <= $produit['stock']) {
                    $_SESSION['panier'][$key]['quantite']++;
                } else {
                    $_SESSION['error_stock'] = "Stock insuffisant (max: {$produit['stock']})";
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['panier'][] = [
                'id' => $produit['id'],
                'nom' => $produit['nom'],
                'prix' => floatval($produit['prix']),
                'quantite' => 1,
                'image' => $produit['image'],
                'stock_max' => $produit['stock']
            ];
            $_SESSION['success_ajout'] = true;
        }
    }
    
    $redirect_url = "boutique.php";
    if ($categorie_active) $redirect_url .= "?categorie=$categorie_active";
    if (!empty($recherche)) $redirect_url .= (strpos($redirect_url, '?') === false ? "?recherche=" : "&recherche=") . urlencode($recherche);
    
    header("Location: $redirect_url");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/boutique.css">
</head>
<body>

<div class="boutique-page">
    <div class="container">
        
        <div class="boutique-header">
            <h1>Notre Boutique</h1>
            <p>Découvrez nos produits de beauté sélectionnés avec soin</p>
        </div>
        
        <?php if (isset($_SESSION['success_ajout'])): ?>
            <div class="message-success" id="successMessage">
                ✓ Produit ajouté au panier !
            </div>
            <?php unset($_SESSION['success_ajout']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_stock'])): ?>
            <div class="message-error" id="errorMessage">
                ⚠ <?= $_SESSION['error_stock'] ?>
            </div>
            <?php unset($_SESSION['error_stock']); ?>
        <?php endif; ?>
        
        
        <div class="search-section">
            <div class="search-wrapper">
                <input type="text" 
                       id="searchInput"
                       class="search-input" 
                       placeholder="Rechercher un produit..." 
                       value="<?= htmlspecialchars($recherche) ?>"
                       autocomplete="off">
                <button id="searchBtn" class="search-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="10" cy="10" r="7"></circle>
                        <line x1="21" y1="21" x2="15" y2="15"></line>
                    </svg>
                    Rechercher
                </button>
                <?php if (!empty($recherche) || !empty($categorie_active)): ?>
                    <a href="boutique.php" class="reset-btn">Réinitialiser</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="boutique-layout">
            
            
            <aside class="boutique-sidebar">
                <h3>Catégories</h3>
                <ul class="categories-list">
                    <li>
                        <a href="boutique.php" class="<?= !$categorie_active && empty($recherche) ? 'active' : '' ?>">
                            Tous les produits
                            <span class="count"><?= $total_produits ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $key => $label): ?>
                        <?php if ($compteurs[$key] > 0 || $categorie_active == $key): ?>
                            <li>
                                <a href="?categorie=<?= $key ?>" class="<?= $categorie_active == $key ? 'active' : '' ?>">
                                    <?= $label ?>
                                    <span class="count"><?= $compteurs[$key] ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </aside>
            
            
            <div class="boutique-products">
                
                <?php if (!empty($recherche)): ?>
                    <div class="search-results-info">
                         <?= count($produits) ?> résultat(s) pour "<strong><?= htmlspecialchars($recherche) ?></strong>"
                    </div>
                <?php endif; ?>
                
                <?php if (empty($produits)): ?>
                    <div class="empty-products">
                        <div class="empty-icon"></div>
                        <p>Aucun produit trouvé</p>
                        <?php if (!empty($recherche)): ?>
                            <p class="empty-suggestion">Essayez avec d'autres mots-clés</p>
                        <?php endif; ?>
                        <a href="boutique.php" class="btn-back">Voir tous les produits</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($produits as $p): ?>
                            <div class="product-card" data-id="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>">
                                <div class="product-image">
                                    <?php 
                                    if (!empty($p['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/produits/' . $p['image'])): 
                                    ?>
                                        <img src="/uploads/produits/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder">📷</div>
                                    <?php endif; ?>
                                    
                                    <?php if ($p['stock'] <= 3): ?>
                                        <span class="stock-badge stock-critique"> Stock très limité</span>
                                    <?php elseif ($p['stock'] <= 5): ?>
                                        <span class="stock-badge stock-low">Dernières pièces</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($p['nom']) ?></h3>
                                    <p class="product-description"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 80)) ?>...</p>
                                    
                                    <div class="product-stock">
                                        <?php if ($p['stock'] > 10): ?>
                                            <span class="stock-dispo in-stock"> En stock</span>
                                        <?php elseif ($p['stock'] > 0): ?>
                                            <span class="stock-dispo low-stock"> Plus que <?= $p['stock'] ?> exemplaire(s)</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-footer">
                                        <span class="product-price"><?= number_format($p['prix'], 2) ?> €</span>
                                        
                                        <a href="?ajouter=1&id=<?= $p['id'] ?><?= $categorie_active ? "&categorie=$categorie_active" : "" ?><?= !empty($recherche) ? "&recherche=" . urlencode($recherche) : "" ?>" class="btn-add-to-cart">
                                            Ajouter au panier
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/js/boutique.js"></script>

<?php include 'footer.php'; ?>