<?php
session_start();


if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}


$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="admin-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h1 class="logo">Sisi's Beauty</h1>
            <p class="admin-badge">Administration</p>
        </div>

        <nav class="sidebar-nav">
            <a href="/admin/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Tableau de bord</span>
            </a>
            <a href="/admin/utilisateurs.php" class="<?= $current_page == 'utilisateurs.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Utilisateurs</span>
            </a>
            <a href="/admin/prestations.php" class="<?= $current_page == 'prestations.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Prestations</span>
            </a>
            <a href="/admin/rendezvous.php" class="<?= $current_page == 'rendezvous.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Rendez-vous</span>
            </a>
            <a href="/admin/produits.php" class="<?= $current_page == 'produits.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Produits</span>
            </a>
            <a href="/admin/commandes.php" class="<?= $current_page == 'commandes.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Commandes</span>
            </a>
            <a href="/admin/messages.php" class="<?= $current_page == 'messages.php' ? 'active' : '' ?>">
                <span class="nav-icon"></span>
                <span>Messages</span>
            </a>

            <a href="/admin/disponibilites.php" class="<?= $current_page == 'disponibilites.php' ? 'active' : '' ?>">
    <span class="nav-icon"></span>
    <span>Horaires</span>
</a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <span class="admin-name"><?= $_SESSION['user_nom'] ?? 'Administrateur' ?></span>
                <span class="admin-role">Administrateur</span>
            </div>
            <a href="/deconnexion.php" class="logout-btn">Déconnexion</a>
        </div>
    </aside>

    <main class="content">
        <div class="content-header">
            <div class="page-title">
                <h2><?= $page_title ?? 'Administration' ?></h2>
            </div>
            <div class="header-actions">
                <div class="date-info">
                    <?= date('l d F Y', strtotime('now')) ?>
                </div>
            </div>
        </div>
        <div class="content-body">
            <?php echo $content ?? "<p>Bienvenue dans l'espace d'administration.</p>"; ?>
        </div>
    </main>
</div>

</body>
</html>