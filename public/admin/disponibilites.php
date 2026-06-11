<?php
session_start();

require_once __DIR__ . "/../../src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();


$jours = [
    'lundi' => 'Lundi',
    'mardi' => 'Mardi',
    'mercredi' => 'Mercredi',
    'jeudi' => 'Jeudi',
    'vendredi' => 'Vendredi',
    'samedi' => 'Samedi',
    'dimanche' => 'Dimanche'
];


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save"])) {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : null;
    $jour = $_POST["jour"];
    $heure_debut = $_POST["heure_debut"];
    $heure_fin = $_POST["heure_fin"];
    $intervalle = (int)$_POST["intervalle"];
    $est_actif = isset($_POST["est_actif"]) ? 1 : 0;
    
    if ($id) {
        
        $stmt = $pdo->prepare("
            UPDATE horaires_ouverture 
            SET jour = ?, heure_debut = ?, heure_fin = ?, intervalle = ?, est_actif = ?
            WHERE id = ?
        ");
        $stmt->execute([$jour, $heure_debut, $heure_fin, $intervalle, $est_actif, $id]);
        $_SESSION['message'] = "Créneau modifié";
    } else {
        
        $stmt = $pdo->prepare("
            INSERT INTO horaires_ouverture (jour, heure_debut, heure_fin, intervalle, est_actif)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$jour, $heure_debut, $heure_fin, $intervalle, $est_actif]);
        $_SESSION['message'] = "Créneau ajouté";
    }
    
    header("Location: disponibilites.php");
    exit();
}


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $stmt = $pdo->prepare("DELETE FROM horaires_ouverture WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['message'] = "Créneau supprimé";
    header("Location: disponibilites.php");
    exit();
}


$creneaux = $pdo->query("SELECT * FROM horaires_ouverture ORDER BY FIELD(jour, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'), heure_debut")->fetchAll(PDO::FETCH_ASSOC);


$edit_creneau = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM horaires_ouverture WHERE id = ?");
    $stmt->execute([(int)$_GET["edit"]]);
    $edit_creneau = $stmt->fetch(PDO::FETCH_ASSOC);
}

$page_title = "Horaires d'ouverture";
ob_start();
?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>


<div class="form-card">
    <h3><?= $edit_creneau ? 'Modifier un créneau' : 'Ajouter un créneau horaire' ?></h3>
    
    <form method="POST">
        <?php if ($edit_creneau): ?>
            <input type="hidden" name="id" value="<?= $edit_creneau['id'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Jour</label>
                <select name="jour" required>
                    <option value="">Sélectionner un jour</option>
                    <?php foreach ($jours as $key => $nom): ?>
                        <option value="<?= $key ?>" <?= ($edit_creneau && $edit_creneau['jour'] == $key) ? 'selected' : '' ?>>
                            <?= $nom ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Heure d'ouverture</label>
                <input type="time" name="heure_debut" value="<?= $edit_creneau ? $edit_creneau['heure_debut'] : '09:00' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Heure de fermeture</label>
                <input type="time" name="heure_fin" value="<?= $edit_creneau ? $edit_creneau['heure_fin'] : '18:00' ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Intervalle (minutes)</label>
                <select name="intervalle">
                    <option value="15" <?= ($edit_creneau && $edit_creneau['intervalle'] == 15) ? 'selected' : '' ?>>15 minutes</option>
                    <option value="30" <?= ($edit_creneau && $edit_creneau['intervalle'] == 30) ? 'selected' : '' ?>>30 minutes</option>
                    <option value="60" <?= ($edit_creneau && $edit_creneau['intervalle'] == 60) ? 'selected' : '' ?>>1 heure</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="est_actif" value="1" <?= ($edit_creneau && $edit_creneau['est_actif']) || !$edit_creneau ? 'checked' : '' ?>>
                    Actif
                </label>
            </div>
        </div>
        
        <button type="submit" name="save" class="btn">
            <?= $edit_creneau ? 'Modifier' : 'Ajouter' ?>
        </button>
        
        <?php if ($edit_creneau): ?>
            <a href="disponibilites.php" class="btn btn-secondary">Annuler</a>
        <?php endif; ?>
    </form>
</div>


<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Jour</th>
                <th>Heure début</th>
                <th>Heure fin</th>
                <th>Intervalle</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($creneaux)): ?>
                <tr>
                    <td colspan="6" class="empty-state">Aucun créneau défini. Ajoutez vos horaires d'ouverture.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($creneaux as $c): ?>
                <tr>
                    <td><strong><?= $jours[$c['jour']] ?></strong></td>
                    <td><?= substr($c['heure_debut'], 0, 5) ?></td>
                    <td><?= substr($c['heure_fin'], 0, 5) ?></td>
                    <td><?= $c['intervalle'] ?> min</td>
                    <td>
                        <span class="status-badge <?= $c['est_actif'] ? 'status-confirme' : 'status-annule' ?>">
                            <?= $c['est_actif'] ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                    <td class="action-links">
                        <a href="?edit=<?= $c['id'] ?>" class="action-link">Modifier</a>
                        <a href="?delete=<?= $c['id'] ?>" class="action-link delete" onclick="return confirm('Supprimer ce créneau ?')">Supprimer</a>
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