<?php
session_start();

require_once "../src/config/database.php";

$db = new Database();
$pdo = $db->getConnection();


if (empty($_SESSION["panier"])) {
    header("Location: panier.php");
    exit();
}


$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$_SESSION['commande'] = $_SESSION['commande'] ?? [];


$user_id = $_SESSION['user_id'] ?? null;
$user_info = [];

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
}


function calculPanier($panier) {
    $total = 0;
    foreach ($panier as $item) {
        $total += $item["prix"] * $item["quantite"];
    }
    return $total;
}

$total_produits = calculPanier($_SESSION["panier"]);
$frais_livraison = ($total_produits > 50) ? 0 : 5.90;
$total_general = $total_produits + $frais_livraison;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {

    $_SESSION['commande'] = [
        'nom' => trim($_POST['nom']),
        'prenom' => trim($_POST['prenom']),
        'email' => trim($_POST['email']),
        'telephone' => trim($_POST['telephone']),
        'adresse' => trim($_POST['adresse']),
        'ville' => trim($_POST['ville']),
        'code_postal' => trim($_POST['code_postal']),
        'pays' => trim($_POST['pays']),
        'commentaires' => trim($_POST['commentaires'])
    ];

    if (empty($_SESSION['commande']['nom'])) $errors[] = "Nom requis";
    if (empty($_SESSION['commande']['prenom'])) $errors[] = "Prénom requis";
    if (empty($_SESSION['commande']['email']) || !filter_var($_SESSION['commande']['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    if (empty($_SESSION['commande']['adresse'])) $errors[] = "Adresse requise";

    if (empty($errors)) {
        $step = 2;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2'])) {

    $_SESSION['commande']['mode_livraison'] = $_POST['mode_livraison'];
    $_SESSION['commande']['mode_paiement'] = $_POST['mode_paiement'];

    $step = 3;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3'])) {

    try {
        
        $pdo->beginTransaction();
        
        
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$_SESSION['commande']['email']]);
        $user = $stmt->fetch();

        if ($user) {
            $user_id = $user['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, telephone, adresse, ville, code_postal, rolee)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'client')
            ");
            $stmt->execute([
                $_SESSION['commande']['nom'],
                $_SESSION['commande']['prenom'],
                $_SESSION['commande']['email'],
                $_SESSION['commande']['telephone'],
                $_SESSION['commande']['adresse'],
                $_SESSION['commande']['ville'],
                $_SESSION['commande']['code_postal']
            ]);
            $user_id = $pdo->lastInsertId();
        }

        
        $columns = $pdo->query("DESCRIBE commandes")->fetchAll(PDO::FETCH_COLUMN);
        
        
        $numero = 'CMD-' . date('Ymd') . '-' . rand(1000, 9999);
        $adresse = $_SESSION['commande']['adresse'] . ', ' .
                   $_SESSION['commande']['code_postal'] . ' ' .
                   $_SESSION['commande']['ville'];
        
        $insertFields = ['utilisateur_id', 'total', 'statut', 'created_at'];
        $insertValues = [$user_id, $total_general, 'en_attente', date('Y-m-d H:i:s')];
        
        if (in_array('numero_commande', $columns)) {
            $insertFields[] = 'numero_commande';
            $insertValues[] = $numero;
        }
        
        if (in_array('frais_livraison', $columns)) {
            $insertFields[] = 'frais_livraison';
            $insertValues[] = $frais_livraison;
        }
        
        if (in_array('mode_livraison', $columns)) {
            $insertFields[] = 'mode_livraison';
            $insertValues[] = $_SESSION['commande']['mode_livraison'];
        }
        
        
        if (in_array('mode_paiement', $columns)) {
            $insertFields[] = 'mode_paiement';
            $insertValues[] = $_SESSION['commande']['mode_paiement'];
        }
        
        
        if (in_array('adresse_livraison', $columns)) {
            $insertFields[] = 'adresse_livraison';
            $insertValues[] = $adresse;
        }
        
        $sql = "INSERT INTO commandes (" . implode(', ', $insertFields) . ") 
                VALUES (" . implode(', ', array_fill(0, count($insertValues), '?')) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($insertValues);
        $commande_id = $pdo->lastInsertId();

        
        $lignesColumns = [];
        try {
            $lignesColumns = $pdo->query("DESCRIBE lignes_commandes")->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            
        }
        
        
        $checkDetails = $pdo->query("SHOW TABLES LIKE 'commandes_details'");
        $detailsExists = $checkDetails->rowCount() > 0;
        
        $checkLignes = $pdo->query("SHOW TABLES LIKE 'lignes_commandes'");
        $lignesExists = $checkLignes->rowCount() > 0;
        
        if ($detailsExists) {
            
            foreach ($_SESSION["panier"] as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO commandes_details
                    (commande_id, produit_id, nom_produit, prix, quantite)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $commande_id,
                    $item['id'],
                    $item['nom'],
                    $item['prix'],
                    $item['quantite']
                ]);

                
                $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?")
                    ->execute([$item['quantite'], $item['id']]);
            }
        } elseif ($lignesExists && !empty($lignesColumns)) {
            
            foreach ($_SESSION["panier"] as $item) {
                $lignesFields = ['commande_id', 'produit_id', 'quantite'];
                $lignesValues = [$commande_id, $item['id'], $item['quantite']];
                
                
                if (in_array('prix_unitaire', $lignesColumns)) {
                    $lignesFields[] = 'prix_unitaire';
                    $lignesValues[] = $item['prix'];
                } elseif (in_array('prix', $lignesColumns)) {
                    $lignesFields[] = 'prix';
                    $lignesValues[] = $item['prix'];
                } elseif (in_array('prix_unitaire_ht', $lignesColumns)) {
                    $lignesFields[] = 'prix_unitaire_ht';
                    $lignesValues[] = $item['prix'];
                }
                
                
                if (in_array('nom_produit', $lignesColumns)) {
                    $lignesFields[] = 'nom_produit';
                    $lignesValues[] = $item['nom'];
                }
                
                $sql = "INSERT INTO lignes_commandes (" . implode(', ', $lignesFields) . ") 
                        VALUES (" . implode(', ', array_fill(0, count($lignesValues), '?')) . ")";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($lignesValues);

                
                $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?")
                    ->execute([$item['quantite'], $item['id']]);
            }
        } else {
            
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `commandes_items` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `commande_id` int(11) NOT NULL,
                    `produit_id` int(11) NOT NULL,
                    `nom_produit` varchar(255) NOT NULL,
                    `prix` decimal(10,2) NOT NULL,
                    `quantite` int(11) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            foreach ($_SESSION["panier"] as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO commandes_items
                    (commande_id, produit_id, nom_produit, prix, quantite)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $commande_id,
                    $item['id'],
                    $item['nom'],
                    $item['prix'],
                    $item['quantite']
                ]);

                
                $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?")
                    ->execute([$item['quantite'], $item['id']]);
            }
        }

        
        $pdo->commit();

        $_SESSION["panier"] = [];
        $_SESSION['last_commande'] = $numero;
        $step = 4;

    } catch (Exception $e) {
        
        $pdo->rollBack();
        $errors[] = "Erreur : " . $e->getMessage();
        $step = 3;
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/commander.css">
</head>
<body>

<div class="commander-page">
    <div class="container">
        
        <div class="commander-header">
            <h1>Finaliser ma commande</h1>
            
            
            <div class="steps-progress">
                <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">Informations</div>
                </div>
                <div class="step-line <?= $step > 1 ? 'active' : '' ?>"></div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">Livraison</div>
                </div>
                <div class="step-line <?= $step > 2 ? 'active' : '' ?>"></div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
                    <div class="step-number">3</div>
                    <div class="step-label">Récapitulatif</div>
                </div>
                <div class="step-line <?= $step > 3 ? 'active' : '' ?>"></div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                    <div class="step-number">4</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="message-error">
                <strong>⚠️ Erreurs :</strong><br>
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>
        
        <div class="commander-layout">
            
            
            <div class="commander-content">
                
                
                <div class="step-content <?= $step == 1 ? 'active' : '' ?>" id="step1">
                    <form method="POST" class="commander-form">
                        <div class="form-card">
                            <h2>Informations personnelles</h2>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nom *</label>
                                    <input type="text" name="nom" required value="<?= htmlspecialchars($_SESSION['commande']['nom'] ?? $user_info['nom'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Prénom *</label>
                                    <input type="text" name="prenom" required value="<?= htmlspecialchars($_SESSION['commande']['prenom'] ?? $user_info['prenom'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" required value="<?= htmlspecialchars($_SESSION['commande']['email'] ?? $user_info['email'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input type="tel" name="telephone" value="<?= htmlspecialchars($_SESSION['commande']['telephone'] ?? $user_info['telephone'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <h2 style="margin-top: 30px;">Adresse de livraison</h2>
                            
                            <div class="form-group">
                                <label>Adresse *</label>
                                <input type="text" name="adresse" required value="<?= htmlspecialchars($_SESSION['commande']['adresse'] ?? '') ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ville *</label>
                                    <input type="text" name="ville" required value="<?= htmlspecialchars($_SESSION['commande']['ville'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Code postal *</label>
                                    <input type="text" name="code_postal" required value="<?= htmlspecialchars($_SESSION['commande']['code_postal'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Pays</label>
                                <input type="text" name="pays" value="<?= htmlspecialchars($_SESSION['commande']['pays'] ?? 'France') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Commentaires (optionnel)</label>
                                <textarea name="commentaires" rows="3" placeholder="Instructions particulières..."><?= htmlspecialchars($_SESSION['commande']['commentaires'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="panier.php" class="btn-secondary">← Retour au panier</a>
                            <button type="submit" name="step1" class="btn-primary">Continuer →</button>
                        </div>
                    </form>
                </div>
                
                
                <div class="step-content <?= $step == 2 ? 'active' : '' ?>" id="step2">
                    <form method="POST" class="commander-form">
                        <div class="form-card">
                            <h2>Mode de livraison</h2>
                            
                            <div class="options-group">
                                <label class="option-card">
                                    <input type="radio" name="mode_livraison" value="livraison" checked>
                                    <div class="option-content">
                                        <strong> Livraison à domicile</strong>
                                        <span class="price"><?= ($total_produits > 50) ? 'Gratuite' : '5,90 €' ?></span>
                                        <small>Livraison sous 3-5 jours ouvrés</small>
                                    </div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="mode_livraison" value="retrait">
                                    <div class="option-content">
                                        <strong> Retrait en salon</strong>
                                        <span class="price">Gratuit</span>
                                        <small>Retrait gratuit à notre salon</small>
                                    </div>
                                </label>
                            </div>
                            
                            <h2 style="margin-top: 30px;">Mode de paiement</h2>
                            
                            <div class="options-group">
                                <label class="option-card">
                                    <input type="radio" name="mode_paiement" value="carte" checked>
                                    <div class="option-content">
                                        <strong> Carte bancaire</strong>
                                        <small>Paiement sécurisé CB/Visa/Mastercard</small>
                                    </div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="mode_paiement" value="paypal">
                                    <div class="option-content">
                                        <strong> PayPal</strong>
                                        <small>Paiement en ligne sécurisé</small>
                                    </div>
                                </label>
                                
                                <label class="option-card">
                                    <input type="radio" name="mode_paiement" value="especes">
                                    <div class="option-content">
                                        <strong> Espèces</strong>
                                        <small>Paiement à la réception (retrait uniquement)</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="goToStep(1)">← Retour</button>
                            <button type="submit" name="step2" class="btn-primary">Continuer →</button>
                        </div>
                    </form>
                </div>
                
                
                <div class="step-content <?= $step == 3 ? 'active' : '' ?>" id="step3">
                    <form method="POST" class="commander-form">
                        <div class="form-card">
                            <h2>Récapitulatif de votre commande</h2>
                            
                            <div class="recap-grid">
                                <div class="recap-box">
                                    <h3>Vos informations</h3>
                                    <p>
                                        <strong><?= htmlspecialchars($_SESSION['commande']['prenom'] . ' ' . $_SESSION['commande']['nom']) ?></strong><br>
                                        <?= htmlspecialchars($_SESSION['commande']['email']) ?><br>
                                        <?= htmlspecialchars($_SESSION['commande']['telephone'] ?? 'Pas de téléphone') ?>
                                    </p>
                                </div>
                                
                                <div class="recap-box">
                                    <h3>Adresse de livraison</h3>
                                    <p>
                                        <?= nl2br(htmlspecialchars($_SESSION['commande']['adresse'])) ?><br>
                                        <?= htmlspecialchars($_SESSION['commande']['code_postal'] . ' ' . $_SESSION['commande']['ville']) ?><br>
                                        <?= htmlspecialchars($_SESSION['commande']['pays'] ?? 'France') ?>
                                    </p>
                                </div>
                                
                                <div class="recap-box">
                                    <h3>Livraison</h3>
                                    <p><?= $_SESSION['commande']['mode_livraison'] == 'livraison' ? ' Livraison à domicile' : ' Retrait en salon' ?></p>
                                </div>
                                
                                <div class="recap-box">
                                    <h3>Paiement</h3>
                                    <p>
                                        <?= $_SESSION['commande']['mode_paiement'] == 'carte' ? ' Carte bancaire' : 
                                          ($_SESSION['commande']['mode_paiement'] == 'paypal' ? ' PayPal' : ' Espèces') ?>
                                    </p>
                                </div>
                            </div>
                            
                            <h3>Produits commandés</h3>
                            <div class="recap-produits">
                                <table class="recap-table">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Prix unitaire</th>
                                            <th>Quantité</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_SESSION["panier"] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['nom']) ?></td>
                                            <td><?= number_format($item['prix'], 2) ?> €</span>
                                            </td>
                                            <td><?= $item['quantite'] ?> </span>
                                            <td>
                                            <td><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="recap-totaux">
                                <div class="total-line">
                                    <span>Sous-total</span>
                                    <span><?= number_format($total_produits, 2) ?> €</span>
                                </div>
                                <div class="total-line">
                                    <span>Livraison</span>
                                    <span><?= ($total_produits > 50) ? 'Gratuite' : number_format($frais_livraison, 2) . ' €' ?></span>
                                </div>
                                <div class="total-line grand-total">
                                    <span>Total TTC</span>
                                    <span><?= number_format($total_general, 2) ?> €</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($_SESSION['commande']['commentaires'])): ?>
                                <div class="recap-commentaire">
                                    <strong>📝 Commentaires :</strong>
                                    <p><?= nl2br(htmlspecialchars($_SESSION['commande']['commentaires'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="goToStep(2)">← Retour</button>
                            <button type="submit" name="step3" class="btn-confirm">Confirmer ma commande</button>
                        </div>
                    </form>
                </div>
                
                
                <div class="step-content <?= $step == 4 ? 'active' : '' ?>" id="step4">
                    <div class="confirmation-card">
                        <div class="confirmation-icon">✓</div>
                        <h2>Commande confirmée !</h2>
                        <p>Merci pour votre commande. Nous vous remercions de votre confiance.</p>
                        
                        <div class="commande-info">
                            <p><strong>Numéro de commande :</strong><br>
                            <?= $_SESSION['last_commande'] ?? 'CMD-' . date('Ymd') . '-XXXX' ?></p>
                            <p><strong>Un email de confirmation vous a été envoyé.</strong></p>
                        </div>
                        
                        <div class="confirmation-actions">
                            <a href="boutique.php" class="btn-primary">Continuer mes achats</a>
                            <a href="mes_commandes.php" class="btn-secondary">Suivre ma commande</a>
                        </div>
                    </div>
                </div>
                
            </div>
            
            
            <div class="commander-sidebar">
                <div class="cart-summary">
                    <h3>Votre panier</h3>
                    
                    <div class="cart-items">
                        <?php foreach ($_SESSION["panier"] as $item): ?>
                            <div class="cart-item">
                                <div class="item-info">
                                    <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                    <span class="item-qty">x<?= $item['quantite'] ?></span>
                                </div>
                                <span class="item-price"><?= number_format($item['prix'] * $item['quantite'], 2) ?> €</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-totals">
                        <div class="total-line">
                            <span>Total produits :</span>
                            <span><?= number_format($total_produits, 2) ?> €</span>
                        </div>
                        <div class="total-line">
                            <span>Livraison :</span>
                            <span><?= ($total_produits > 50) ? 'Gratuite' : number_format($frais_livraison, 2) . ' €' ?></span>
                        </div>
                        <div class="total-line grand-total">
                            <span>Total TTC :</span>
                            <span><?= number_format($total_general, 2) ?> €</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="/js/commander.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>