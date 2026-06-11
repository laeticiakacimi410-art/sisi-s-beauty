<?php
session_start();

require_once __DIR__ . "/../../src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();


$types_prestations = [
    'coiffure' => 'Coiffure',
    'esthetique' => 'Esthétique',
    'manucure' => 'Manucure',
    'epilation' => 'Épilation',
    'massage' => 'Massage / Bien-être',
    'makeup' => 'Maquillage',
    'forfait' => 'Forfait / Pack'
];


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add"])) {
    $nom = trim($_POST["nom"]);
    $description = trim($_POST["description"]);
    $prix = floatval($_POST["prix"]);
    $duree = $_POST["duree"];
    $categorie = $_POST["categorie"];
    $type_prestation = $_POST["type_prestation"];
    $image = null;
    
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image = time() . '_' . uniqid() . '.' . $ext;
            $upload_dir = __DIR__ . '/../../uploads/prestations/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO prestations (nom, description, prix, duree, categorie, type_prestation, image)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$nom, $description, $prix, $duree, $categorie, $type_prestation, $image])) {
        $_SESSION['message'] = "Prestation ajoutée avec succès";
    } else {
        $_SESSION['message_error'] = "Erreur lors de l'ajout";
    }
    
    header("Location: prestations.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit"])) {
    $id = (int) $_POST["id"];
    $nom = trim($_POST["nom"]);
    $description = trim($_POST["description"]);
    $prix = floatval($_POST["prix"]);
    $duree = $_POST["duree"];
    $categorie = $_POST["categorie"];
    $type_prestation = $_POST["type_prestation"];
    $image = $_POST["existing_image"] ?? null;
    
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            
            if ($image && file_exists(__DIR__ . '/../../uploads/prestations/' . $image)) {
                unlink(__DIR__ . '/../../uploads/prestations/' . $image);
            }
            $image = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../uploads/prestations/' . $image);
        }
    }
    
    $stmt = $pdo->prepare("
        UPDATE prestations 
        SET nom = ?, description = ?, prix = ?, duree = ?, categorie = ?, type_prestation = ?, image = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$nom, $description, $prix, $duree, $categorie, $type_prestation, $image, $id])) {
        $_SESSION['message'] = "Prestation modifiée avec succès";
    } else {
        $_SESSION['message_error'] = "Erreur lors de la modification";
    }
    
    header("Location: prestations.php");
    exit();
}


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    
    
    $stmt = $pdo->prepare("SELECT image FROM prestations WHERE id = ?");
    $stmt->execute([$id]);
    $prestation = $stmt->fetch();
    
    if ($prestation && $prestation['image']) {
        $image_path = __DIR__ . '/../../uploads/prestations/' . $prestation['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM prestations WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Prestation supprimée";
    header("Location: prestations.php");
    exit();
}


$prestations = $pdo->query("SELECT * FROM prestations ORDER BY type_prestation, nom ASC")->fetchAll(PDO::FETCH_ASSOC);


$edit_prestation = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id = ?");
    $stmt->execute([(int)$_GET["edit"]]);
    $edit_prestation = $stmt->fetch(PDO::FETCH_ASSOC);
}

$page_title = "Prestations";
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


<div class="form-card">
    <h3><?= $edit_prestation ? 'Modifier la prestation' : 'Ajouter une prestation' ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit_prestation): ?>
            <input type="hidden" name="id" value="<?= $edit_prestation['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= $edit_prestation['image'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Nom de la prestation *</label>
                <input type="text" name="nom" value="<?= $edit_prestation ? htmlspecialchars($edit_prestation['nom']) : '' ?>" required>
                <small>Ex: Brushing, French Manucure, Massage relaxant...</small>
            </div>
            
            <div class="form-group">
                <label>Type de prestation *</label>
                <select name="type_prestation" required>
                    <option value="">Sélectionner un type</option>
                    <?php foreach ($types_prestations as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($edit_prestation && $edit_prestation['type_prestation'] == $key) ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Description complète</label>
            <textarea name="description" rows="4" placeholder="Décrivez la prestation en détail..."><?= $edit_prestation ? htmlspecialchars($edit_prestation['description']) : '' ?></textarea>
            <small>Avantages, spécificités, matériel utilisé...</small>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Prix (€) *</label>
                <input type="number" step="0.01" name="prix" value="<?= $edit_prestation ? $edit_prestation['prix'] : '' ?>" required>
                <small>Prix TTC</small>
            </div>
            
            <div class="form-group">
                <label>Durée *</label>
                <select name="duree" required>
                    <option value="">Sélectionner une durée</option>
                    <option value="15" <?= ($edit_prestation && $edit_prestation['duree'] == 15) ? 'selected' : '' ?>>15 minutes</option>
                    <option value="30" <?= ($edit_prestation && $edit_prestation['duree'] == 30) ? 'selected' : '' ?>>30 minutes</option>
                    <option value="45" <?= ($edit_prestation && $edit_prestation['duree'] == 45) ? 'selected' : '' ?>>45 minutes</option>
                    <option value="60" <?= ($edit_prestation && $edit_prestation['duree'] == 60) ? 'selected' : '' ?>>1 heure</option>
                    <option value="90" <?= ($edit_prestation && $edit_prestation['duree'] == 90) ? 'selected' : '' ?>>1h30</option>
                    <option value="120" <?= ($edit_prestation && $edit_prestation['duree'] == 120) ? 'selected' : '' ?>>2 heures</option>
                </select>
                <small>Temps nécessaire pour la prestation</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Catégorie</label>
                <input type="text" name="categorie" value="<?= $edit_prestation ? htmlspecialchars($edit_prestation['categorie']) : '' ?>" placeholder="Ex: Soin cheveux, Onglerie...">
                <small>Optionnel : pour mieux organiser vos prestations</small>
            </div>
            
            <div class="form-group">
                <label>Image de la prestation</label>
                <input type="file" name="image" accept="image
.category-section {
    margin-bottom: 40px;
}

.category-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #1a1a1a;
}

.prestations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.prestation-card {
    background: #ffffff;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.prestation-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.prestation-image {
    width: 100%;
    height: 180px;
    overflow: hidden;
    background: #f5f5f5;
}

.prestation-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prestation-image.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #999;
}

.prestation-info {
    padding: 16px;
}

.prestation-info h5 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #1a1a1a;
}

.prestation-desc {
    font-size: 0.875rem;
    color: #666;
    line-height: 1.4;
    margin-bottom: 12px;
}

.prestation-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.prestation-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a1a;
}

.prestation-duration {
    font-size: 0.75rem;
    color: #888;
}

.prestation-cat {
    display: inline-block;
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    color: #666;
    margin-bottom: 12px;
}

.prestation-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.75rem;
}

.btn-danger {
    background: #fff;
    color: #c62828;
    border: 1px solid #c62828;
}

.btn-danger:hover {
    background: #c62828;
    color: #fff;
}
</style>

<?php
$content = ob_get_clean();
include "layout.php";
?>