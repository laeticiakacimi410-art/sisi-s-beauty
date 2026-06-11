<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && ($_SESSION['user_role'] ?? '') === 'admin';
$userName = '';

if ($isLoggedIn) {
    
    if (!empty($_SESSION['user_prenom'])) {
        $userName = $_SESSION['user_prenom'];
    } elseif (!empty($_SESSION['user_nom'])) {
        $userName = $_SESSION['user_nom'];
    } 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sisi's Beauty - Institut de beauté chic à Paris</title>
    <meta name="description" content="Institut de beauté à Paris. Coiffure, maquillage, soins. Réservation en ligne.">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header>
    <div class="container">
        <a href="/" class="logo">
            <span class="pink">Sisi's</span><span class="black">Beauty</span>
        </a>
        
        <nav>
            <ul>
                <li><a href="/" class="active">Accueil</a></li>
                <li><a href="/prestations.php">Prestations</a></li>
                <li><a href="/reservation.php">Réservation</a></li>
                <li><a href="/boutique.php">Boutique</a></li>
                <li><a href="/panier.php">Panier</a></li>
                <li><a href="/contact.php">Contact</a></li>
                
            </ul>
        </nav>
        
        <div class="header-buttons">
            <?php if ($isLoggedIn): ?>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                    <div class="user-dropdown-trigger">
                        <span class="user-initial"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                        <span class="dropdown-arrow">⌵</span>
                    </div>
                    <div class="user-dropdown">
                        <a href="/mon_compte.php" class="dropdown-link">Mon compte</a>
                        <a href="/mes_reservations.php" class="dropdown-link">Mes réservations</a>
                        <a href="/mes_commandes.php" class="dropdown-link">Mes commandes</a>
                        <?php if ($isAdmin): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/admin/" class="dropdown-link admin-link">Administration</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/deconnexion.php" class="dropdown-link logout-link">Déconnexion</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/connexion.php" class="btn-connexion">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>