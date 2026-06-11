<?php
session_start();

require_once dirname(__DIR__, 2) . "/src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();


if (isset($_GET["action"]) && isset($_GET["id"])) {
    $id = (int) $_GET["id"];
    $action = $_GET["action"];

    $allowed = ["en_attente", "confirme", "annule", "termine"];

    if (in_array($action, $allowed)) {
        $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = ? WHERE id = ?");
        $stmt->execute([$action, $id]);
        $_SESSION['message'] = "Statut mis à jour";
    }

    header("Location: rendezvous.php");
    exit();
}


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $stmt = $pdo->prepare("DELETE FROM rendez_vous WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Rendez-vous supprimé";

    header("Location: rendezvous.php");
    exit();
}


$statut_filter = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';


$sql = "
SELECT r.*, u.nom, u.prenom, u.email, u.telephone, p.nom AS prestation, p.prix, p.duree
FROM rendez_vous r
JOIN utilisateurs u ON r.utilisateur_id = u.id
JOIN prestations p ON r.prestation_id = p.id
WHERE 1=1
";

$params = [];

if (!empty($statut_filter)) {
    $sql .= " AND r.statut = ?";
    $params[] = $statut_filter;
}

if (!empty($date_filter)) {
    $sql .= " AND r.date_rdv = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR p.nom LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY r.date_rdv DESC, r.heure DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rdv = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'confirme' THEN 1 ELSE 0 END) as confirme,
        SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as annule,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termine
    FROM rendez_vous
")->fetch(PDO::FETCH_ASSOC);

$page_title = "Rendez-vous";
ob_start();
?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>


<div class="stats-mini">
    <div class="stat-item">
        <span class="stat-label">Total</span>
        <span class="stat-value"><?= $stats['total'] ?></span>
    </div>
    <div class="stat-item en-attente">
        <span class="stat-label">En attente</span>
        <span class="stat-value"><?= $stats['en_attente'] ?></span>
    </div>
    <div class="stat-item confirme">
        <span class="stat-label">Confirmés</span>
        <span class="stat-value"><?= $stats['confirme'] ?></span>
    </div>
    <div class="stat-item annule">
        <span class="stat-label">Annulés</span>
        <span class="stat-value"><?= $stats['annule'] ?></span>
    </div>
    <div class="stat-item termine">
        <span class="stat-label">Terminés</span>
        <span class="stat-value"><?= $stats['termine'] ?></span>
    </div>
</div>


<div class="filters-card">
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <div class="filter-group">
            <select name="statut">
                <option value="">Tous les statuts</option>
                <option value="en_attente" <?= $statut_filter == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                <option value="confirme" <?= $statut_filter == 'confirme' ? 'selected' : '' ?>>Confirmé</option>
                <option value="annule" <?= $statut_filter == 'annule' ? 'selected' : '' ?>>Annulé</option>
                <option value="termine" <?= $statut_filter == 'termine' ? 'selected' : '' ?>>Terminé</option>
            </select>
        </div>
        
        <div class="filter-group">
            <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>" placeholder="Date">
        </div>
        
        <div class="filter-group">
            <button type="submit" class="btn-filter">Filtrer</button>
            <a href="rendezvous.php" class="btn-reset">Réinitialiser</a>
        </div>
    </form>
</div>


<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Contact</th>
                <th>Prestation</th>
                <th>Date</th>
                <th>Heure</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rdv)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        Aucun rendez-vous trouvé
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rdv as $r): ?>
                <tr class="status-<?= $r['statut'] ?>">
                    <td>
                        <strong><?= htmlspecialchars($r["prenom"] . " " . $r["nom"]) ?></strong>
                    </td>
                    <td>
                        <?= htmlspecialchars($r["email"]) ?><br>
                        <small><?= htmlspecialchars($r["telephone"] ?? 'Pas de téléphone') ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($r["prestation"]) ?><br>
                        <small><?= $r['duree'] ?> min - <?= number_format($r['prix'], 2) ?> €</small>
                    </td>
                    <td><?= date('d/m/Y', strtotime($r["date_rdv"])) ?></td>
                    <td><?= htmlspecialchars($r["heure"]) ?></td>
                    <td>
                        <form method="GET" class="status-form">
                            <input type="hidden" name="id" value="<?= $r["id"] ?>">
                            <select name="action" class="status-select status-<?= $r['statut'] ?>" onchange="this.form.submit()">
                                <option value="en_attente" <?= $r["statut"] === "en_attente" ? "selected" : "" ?>>📋 En attente</option>
                                <option value="confirme" <?= $r["statut"] === "confirme" ? "selected" : "" ?>>✓ Confirmé</option>
                                <option value="annule" <?= $r["statut"] === "annule" ? "selected" : "" ?>>✗ Annulé</option>
                                <option value="termine" <?= $r["statut"] === "termine" ? "selected" : "" ?>>✔ Terminé</option>
                            </select>
                        </form>
                    </td>
                    <td class="actions">
                        <a href="?delete=<?= $r["id"] ?>" class="btn-delete" onclick="return confirm('Supprimer ce rendez-vous ?')">
                            Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>

.stats-mini {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.stat-item {
    background: white;
    padding: 15px 25px;
    border-radius: 8px;
    text-align: center;
    flex: 1;
    min-width: 100px;
    border-left: 3px solid #1a1a1a;
}

.stat-item.en-attente {
    border-left-color: #f39c12;
}
.stat-item.confirme {
    border-left-color: #27ae60;
}
.stat-item.annule {
    border-left-color: #e74c3c;
}
.stat-item.termine {
    border-left-color: #3498db;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
}

.filters-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border: 1px solid #e5e5e5;
}

.filters-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 150px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.btn-filter, .btn-reset {
    display: inline-block;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    text-align: center;
}

.btn-filter {
    background: #1a1a1a;
    color: white;
}

.btn-filter:hover {
    background: #333;
}

.btn-reset {
    background: #f0f0f0;
    color: #333;
}

.btn-reset:hover {
    background: #e0e0e0;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.admin-table th {
    background: #1a1a1a;
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
}

.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
}

.admin-table tr:hover {
    background: #fafafa;
}

.admin-table tr.status-annule {
    opacity: 0.7;
    background: #fff5f5;
}

.status-select {
    padding: 6px 10px;
    border-radius: 20px;
    border: 1px solid #ddd;
    font-size: 0.75rem;
    cursor: pointer;
}

.status-select.status-en_attente {
    background: #fff3e0;
    color: #e67e22;
    border-color: #e67e22;
}

.status-select.status-confirme {
    background: #e8f5e9;
    color: #2e7d32;
    border-color: #2e7d32;
}

.status-select.status-annule {
    background: #ffebee;
    color: #c62828;
    border-color: #c62828;
}

.status-select.status-termine {
    background: #e3f2fd;
    color: #1565c0;
    border-color: #1565c0;
}

.actions {
    white-space: nowrap;
}

.btn-delete {
    color: #c62828;
    text-decoration: none;
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-delete:hover {
    background: #ffebee;
}

.empty-state {
    text-align: center;
    padding: 60px !important;
    color: #999;
}

@media (max-width: 768px) {
    .stats-mini {
        gap: 10px;
    }
    
    .stat-item {
        padding: 10px 15px;
        min-width: 80px;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 8px 10px;
        font-size: 0.8rem;
    }
    
    .filters-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
}
</style>

<?php
$content = ob_get_clean();
include "layout.php";
?>