<?php
session_start();
require_once __DIR__ . "/../../src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    
    $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Commande supprimée avec succès";
    header("Location: commandes.php");
    exit();
}


if (isset($_GET["status"]) && isset($_GET["id"])) {
    $id = (int) $_GET["id"];
    $status = $_GET["status"];
    
    $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    $_SESSION['message'] = "Statut de la commande mis à jour";
    header("Location: commandes.php");
    exit();
}


$commandes = $pdo->query("
    SELECT c.*, u.nom, u.prenom, u.email
    FROM commandes c
    JOIN utilisateurs u ON c.utilisateur_id = u.id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Commandes";
ob_start();
?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Email</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($commandes)): ?>
                <tr>
                    <td colspan="6" class="empty-state">Aucune commande pour le moment</td>
                </tr>
            <?php else: ?>
                <?php foreach ($commandes as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c["nom"] . " " . $c["prenom"]) ?></td>
                    <td><?= htmlspecialchars($c["email"]) ?></td>
                    <td><strong><?= number_format($c["total"], 2) ?> €</strong></td>
                    <td>
                        <span class="status-badge status-<?= $c["statut"] ?>">
                            <?= $c["statut"] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($c["created_at"])) ?></td>
                    <td class="action-links">
                        <select onchange="updateStatus(this, <?= $c['id'] ?>)" class="status-select">
                            <option value="en_attente" <?= $c['statut'] == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="paye" <?= $c['statut'] == 'paye' ? 'selected' : '' ?>>Payé</option>
                            <option value="expedie" <?= $c['statut'] == 'expedie' ? 'selected' : '' ?>>Expédié</option>
                            <option value="annule" <?= $c['statut'] == 'annule' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                        <a href="?delete=<?= $c['id'] ?>" class="action-link delete" onclick="return confirm('Supprimer cette commande ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function updateStatus(select, id) {
    const status = select.value;
    window.location.href = '?id=' + id + '&status=' + status;
}
</script>

<?php
$content = ob_get_clean();
include "layout.php";
?>