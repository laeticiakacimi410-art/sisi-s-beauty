<?php
session_start();

require_once "../../src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    
    
    if ($id == $_SESSION["user_id"]) {
        $_SESSION['message_error'] = "Vous ne pouvez pas supprimer votre propre compte";
    } else {
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Utilisateur supprimé";
    }
    
    header("Location: utilisateurs.php");
    exit();
}


if (isset($_GET["promote"])) {
    $id = (int) $_GET["promote"];
    
    $stmt = $pdo->prepare("UPDATE utilisateurs SET rolee = 'admin' WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Utilisateur promu administrateur";
    header("Location: utilisateurs.php");
    exit();
}


if (isset($_GET["demote"])) {
    $id = (int) $_GET["demote"];
    
    
    if ($id == $_SESSION["user_id"]) {
        $_SESSION['message_error'] = "Vous ne pouvez pas modifier votre propre rôle";
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET rolee = 'client' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Administrateur rétrogradé en client";
    }
    
    header("Location: utilisateurs.php");
    exit();
}


$users = $pdo->query("SELECT * FROM utilisateurs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Utilisateurs";
ob_start();
?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['message_error'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['message_error']) ?>
    </div>
    <?php unset($_SESSION['message_error']); ?>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Inscrit le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u["prenom"] . " " . $u["nom"]) ?></strong></td>
                <td><?= htmlspecialchars($u["email"]) ?></td>
                <td>
                    <span class="status-badge <?= $u["rolee"] == 'admin' ? 'status-confirme' : '' ?>">
                        <?= $u["rolee"] ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($u["created_at"] ?? 'now')) ?></td>
                <td class="action-links">
                    <?php if ($u["rolee"] !== "admin"): ?>
                        <a href="?promote=<?= $u["id"] ?>" class="action-link">Promouvoir admin</a>
                    <?php elseif ($u["id"] != $_SESSION["user_id"]): ?>
                        <a href="?demote=<?= $u["id"] ?>" class="action-link">Rétrograder</a>
                    <?php endif; ?>
                    
                    <?php if ($u["id"] != $_SESSION["user_id"]): ?>
                        <a href="?delete=<?= $u["id"] ?>" class="action-link delete" onclick="return confirm('Supprimer cet utilisateur ?')">Supprimer</a>
                    <?php else: ?>
                        <span class="action-link" style="opacity: 0.5;">(Vous)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include "layout.php";
?>