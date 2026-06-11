<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: /connexion.php');
    exit;
}

require_once '../src/config/database.php';
include 'header.php';

$db = new Database();
$pdo = $db->getConnection();

$user_id = $_SESSION['user_id'];


$statut_labels = [
    'en_attente' => ['label' => 'En attente', 'class' => 'status-pending'],
    'confirmee' => ['label' => 'Confirmée', 'class' => 'status-confirmed'],
    'expediee' => ['label' => 'Expédiée', 'class' => 'status-shipped'],
    'livree' => ['label' => 'Livrée', 'class' => 'status-delivered'],
    'annulee' => ['label' => 'Annulée', 'class' => 'status-cancelled']
];


$stmt = $pdo->prepare("
    SELECT 
        c.*
    FROM commandes c
    WHERE c.utilisateur_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);


try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'commandes_details'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if ($tableExists) {
        foreach ($commandes as &$commande) {
            $stmt = $pdo->prepare("
                SELECT * FROM commandes_details 
                WHERE commande_id = ?
            ");
            $stmt->execute([$commande['id']]);
            $commande['produits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $commande['nb_articles'] = count($commande['produits']);
            $commande['total_articles'] = array_sum(array_column($commande['produits'], 'quantite'));
        }
    } else {
        foreach ($commandes as &$commande) {
            $commande['produits'] = [];
            $commande['nb_articles'] = 0;
            $commande['total_articles'] = 0;
        }
    }
} catch (PDOException $e) {
    foreach ($commandes as &$commande) {
        $commande['produits'] = [];
        $commande['nb_articles'] = 0;
        $commande['total_articles'] = 0;
    }
}


$total_commandes = count($commandes);
$total_depenses = 0;
$commandes_en_cours = 0;
$commandes_livrees = 0;

foreach ($commandes as $cmd) {
    $total_depenses += $cmd['total'];
    if ($cmd['statut'] == 'livree') {
        $commandes_livrees++;
    } elseif (!in_array($cmd['statut'], ['livree', 'annulee'])) {
        $commandes_en_cours++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/mes_commandes.css">
</head>
<body>

<main class="commandes-page">
    <div class="container">
        
        
        <div class="page-header">
            <div>
                <h1>Mes Commandes</h1>
                <p>Suivez l'état de vos achats</p>
            </div>
            <a href="/boutique.php" class="btn-shop">
                <span></span> Continuer mes achats
            </a>
        </div>
        
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <div class="stat-number" data-target="<?php echo $total_commandes; ?>">0</div>
                    <div class="stat-label">Commandes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <div class="stat-number" data-target="<?php echo $commandes_en_cours; ?>">0</div>
                    <div class="stat-label">En cours</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <div class="stat-number" data-target="<?php echo $commandes_livrees; ?>">0</div>
                    <div class="stat-label">Livrées</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-info">
                    <div class="stat-number" data-target="<?php echo $total_depenses; ?>" data-currency="true">0</div>
                    <div class="stat-label">Total dépensé</div>
                </div>
            </div>
        </div>
        
        
        <?php if (empty($commandes)): ?>
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <h3>Vous n'avez pas encore de commandes</h3>
                <p>Découvrez notre boutique et profitez de nos produits de beauté.</p>
                <a href="/boutique.php" class="btn-empty-action">Découvrir la boutique</a>
            </div>
        <?php else: ?>
            <div class="commandes-list">
                <?php foreach ($commandes as $cmd): 
                    $status = $statut_labels[$cmd['statut']] ?? ['label' => ucfirst($cmd['statut']), 'class' => 'status-default'];
                ?>
                    <div class="commande-card" data-commande-id="<?php echo $cmd['id']; ?>">
                        <div class="commande-header">
                            <div class="commande-info">
                                <div class="commande-number">
                                    <span class="label">N° commande</span>
                                    <strong><?php echo htmlspecialchars($cmd['numero_commande'] ?? '#' . str_pad($cmd['id'], 6, '0', STR_PAD_LEFT)); ?></strong>
                                </div>
                                <div class="commande-date">
                                    <span class="label">Date</span>
                                    <span>
                                        <?php 
                                        $date = new DateTime($cmd['created_at']);
                                        echo $date->format('d/m/Y à H:i');
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="commande-status">
                                <span class="status-badge <?php echo $status['class']; ?>">
                                    <?php echo $status['label']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="commande-body">
                            <div class="commande-produits">
                                <div class="produits-header">
                                    <span>Récapitulatif</span>
                                </div>
                                
                                <div class="produits-list">
                                    <div class="produit-item">
                                        <div class="produit-image-placeholder">📦</div>
                                        <div class="produit-details">
                                            <span class="produit-nom">Commande n°<?php echo htmlspecialchars($cmd['numero_commande'] ?? '#' . str_pad($cmd['id'], 6, '0', STR_PAD_LEFT)); ?></span>
                                        </div>
                                        <div class="produit-prix">
                                            <?php echo number_format($cmd['total'], 2); ?> €
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="commande-totaux">
                                <div class="total-line">
                                    <span>Total TTC</span>
                                    <span><?php echo number_format($cmd['total'], 2); ?> €</span>
                                </div>
                                <?php if (isset($cmd['frais_livraison'])): ?>
                                <div class="total-line shipping">
                                    <span>Dont livraison</span>
                                    <span><?php echo ($cmd['frais_livraison'] ?? 0) > 0 ? number_format($cmd['frais_livraison'], 2) . ' €' : 'Offerte'; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="commande-footer">
                            <div class="commande-actions">
                                <?php if (!empty($cmd['produits'])): ?>
                                    <button class="btn-details" data-id="<?php echo $cmd['id']; ?>">
                                        <span class="arrow">▼</span> Voir les détails
                                    </button>
                                <?php endif; ?>
                                <?php if ($cmd['statut'] !== 'livree' && $cmd['statut'] !== 'annulee'): ?>
                                    <a href="/contact.php" class="btn-support">Contacter le support</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($cmd['mode_livraison']) && $cmd['mode_livraison']): ?>
                                <div class="commande-livraison">
                                    📍 <?php echo $cmd['mode_livraison'] == 'livraison' ? 'Livraison à domicile' : 'Retrait en salon'; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        
                        <div class="commande-details" id="details-<?php echo $cmd['id']; ?>" style="display: none;">
                            <?php if (!empty($cmd['produits'])): ?>
                            <div class="details-section">
                                <h4>📦 Détail des produits</h4>
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Prix unitaire</th>
                                            <th>Quantité</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cmd['produits'] as $produit): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($produit['nom_produit']); ?></td>
                                            <td><?php echo number_format($produit['prix'], 2); ?> €</td>
                                            <td>x<?php echo $produit['quantite']; ?></td>
                                            <td><?php echo number_format($produit['prix'] * $produit['quantite'], 2); ?> €</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($cmd['mode_livraison']) || isset($cmd['mode_paiement']) || isset($cmd['adresse_livraison'])): ?>
                            <div class="details-section">
                                <h4>🚚 Informations de livraison</h4>
                                <div class="delivery-info">
                                    <?php if (isset($cmd['mode_livraison'])): ?>
                                    <div class="info-row">
                                        <strong>Mode de livraison :</strong>
                                        <span><?php echo $cmd['mode_livraison'] == 'livraison' ? 'Livraison à domicile' : 'Retrait en salon'; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (isset($cmd['mode_paiement'])): ?>
                                    <div class="info-row">
                                        <strong>Mode de paiement :</strong>
                                        <span>
                                            <?php 
                                            $paiements = ['carte' => 'Carte bancaire', 'paypal' => 'PayPal', 'especes' => 'Espèces'];
                                            echo $paiements[$cmd['mode_paiement']] ?? ucfirst($cmd['mode_paiement']);
                                            ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cmd['adresse_livraison'])): ?>
                                    <div class="info-row">
                                        <strong>Adresse :</strong>
                                        <span><?php echo nl2br(htmlspecialchars($cmd['adresse_livraison'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</main>

<script src="/js/mes_commandes.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>