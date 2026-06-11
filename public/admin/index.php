<?php
session_start();

require_once __DIR__ . "/../../src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();



$total_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$total_rdv = $pdo->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
$pending_rdv = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut = 'en_attente'")->fetchColumn();
$total_prestations = $pdo->query("SELECT COUNT(*) FROM prestations")->fetchColumn();
$total_produits = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'")->fetchColumn();


$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages_contact")->fetchColumn();



$last_rdv = $pdo->query("
    SELECT r.*, u.nom, u.prenom, p.nom AS prestation
    FROM rendez_vous r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN prestations p ON r.prestation_id = p.id
    ORDER BY r.date_rdv DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);



$last_orders = $pdo->query("
    SELECT c.*, u.nom, u.prenom
    FROM commandes c
    JOIN utilisateurs u ON c.utilisateur_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Tableau de bord";

ob_start();
?>

<div class="stats-grid">

    <div class="stat-card">
        <h3>Utilisateurs</h3>
        <div class="stat-number"><?= $total_users ?></div>
    </div>

    <div class="stat-card">
        <h3>Rendez-vous</h3>
        <div class="stat-number"><?= $total_rdv ?></div>
    </div>

    <div class="stat-card">
        <h3>En attente RDV</h3>
        <div class="stat-number"><?= $pending_rdv ?></div>
    </div>

    <div class="stat-card">
        <h3>Prestations</h3>
        <div class="stat-number"><?= $total_prestations ?></div>
    </div>

    <div class="stat-card">
        <h3>Produits</h3>
        <div class="stat-number"><?= $total_produits ?></div>
    </div>

    <div class="stat-card">
        <h3>Commandes</h3>
        <div class="stat-number"><?= $pending_orders ?></div>
    </div>

    <div class="stat-card">
        <h3>Messages</h3>
        <div class="stat-number"><?= $unread_messages ?></div>
    </div>

</div>

<div class="section-header">
    <h3>Derniers rendez-vous</h3>
    <a href="/admin/rendezvous.php">Voir tout</a>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>Client</th>
            <th>Prestation</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Statut</th>
        </tr>
    </thead>

    <tbody>

    <?php if (empty($last_rdv)): ?>
        <tr>
            <td colspan="5">Aucun rendez-vous</td>
        </tr>
    <?php else: ?>
        <?php foreach ($last_rdv as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r["nom"] . " " . $r["prenom"]) ?></td>
            <td><?= htmlspecialchars($r["prestation"]) ?></td>

            <td>
                <?= $r["date_rdv"] ? date('d/m/Y', strtotime($r["date_rdv"])) : '' ?>
            </td>

            <td><?= htmlspecialchars($r["heure"]) ?></td>

            <td>
                <span class="status-<?= str_replace(' ', '_', $r["statut"]) ?>">
                    <?= htmlspecialchars($r["statut"]) ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>

    </tbody>
</table>

</div>

<div class="section-header" style="margin-top:30px;">
    <h3>Dernières commandes</h3>
    <a href="/admin/commandes.php">Voir tout</a>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>Client</th>
            <th>Total</th>
            <th>Statut</th>
            <th>Date</th>
        </tr>
    </thead>

    <tbody>

    <?php if (empty($last_orders)): ?>
        <tr>
            <td colspan="4">Aucune commande</td>
        </tr>
    <?php else: ?>
        <?php foreach ($last_orders as $order): ?>
        <tr>
            <td><?= htmlspecialchars($order["nom"] . " " . $order["prenom"]) ?></td>
            <td><?= number_format($order["total"], 2) ?> €</td>

            <td>
                <span class="status-<?= str_replace(' ', '_', $order["statut"]) ?>">
                    <?= htmlspecialchars($order["statut"]) ?>
                </span>
            </td>

            <td>
                <?= date('d/m/Y H:i', strtotime($order["created_at"])) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>

    </tbody>
</table>

</div>

<?php
$content = ob_get_clean();
include "layout.php";
?>