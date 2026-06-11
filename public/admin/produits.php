<?php
session_start();

require_once "../../src/config/database.php";

if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: /login.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();


$categories = [
    'makeup' => 'Maquillage',
    'soins' => 'Soins du visage',
    'corps' => 'Soins du corps',
    'cheveux' => 'Soins cheveux',
    'accessoires' => 'Accessoires',
    'coffret' => 'Coffrets'
];


$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/produits/';


if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add"])) {
    $nom = trim($_POST["nom"]);
    $description = trim($_POST["description"]);
    $prix = floatval($_POST["prix"]);
    $stock = intval($_POST["stock"]);
    $categorie = $_POST["categorie"];
    $image = null;
    
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destination = $upload_dir . $image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                
            } else {
                $_SESSION['message_error'] = "Erreur lors de l'upload";
                $image = null;
            }
        } else {
            $_SESSION['message_error'] = "Format non supporté (JPG, PNG, WEBP, GIF)";
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO produits (nom, description, prix, stock, categorie, image) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$nom, $description, $prix, $stock, $categorie, $image])) {
        $_SESSION['message'] = "Produit ajouté avec succès";
    } else {
        $_SESSION['message_error'] = "Erreur lors de l'ajout";
    }
    
    header("Location: produits.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit"])) {
    $id = (int) $_POST["id"];
    $nom = trim($_POST["nom"]);
    $description = trim($_POST["description"]);
    $prix = floatval($_POST["prix"]);
    $stock = intval($_POST["stock"]);
    $categorie = $_POST["categorie"];
    $image = $_POST["existing_image"] ?? null;
    
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            
            if ($image && file_exists($upload_dir . $image)) {
                unlink($upload_dir . $image);
            }
            
            $image = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destination = $upload_dir . $image;
            move_uploaded_file($_FILES['image']['tmp_name'], $destination);
        }
    }
    
    $stmt = $pdo->prepare("
        UPDATE produits 
        SET nom = ?, description = ?, prix = ?, stock = ?, categorie = ?, image = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$nom, $description, $prix, $stock, $categorie, $image, $id])) {
        $_SESSION['message'] = "Produit modifié avec succès";
    } else {
        $_SESSION['message_error'] = "Erreur lors de la modification";
    }
    
    header("Location: produits.php");
    exit();
}


if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    
    
    $stmt = $pdo->prepare("SELECT image FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    $produit = $stmt->fetch();
    
    if ($produit && $produit['image'] && file_exists($upload_dir . $produit['image'])) {
        unlink($upload_dir . $produit['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['message'] = "Produit supprimé";
    header("Location: produits.php");
    exit();
}


if (isset($_GET["stock"]) && isset($_GET["id"])) {
    $id = (int) $_GET["id"];
    $operation = $_GET["stock"];
    
    if ($operation == 'increment') {
        $pdo->prepare("UPDATE produits SET stock = stock + 1 WHERE id = ?")->execute([$id]);
    } elseif ($operation == 'decrement') {
        $pdo->prepare("UPDATE produits SET stock = GREATEST(stock - 1, 0) WHERE id = ?")->execute([$id]);
    }
    
    header("Location: produits.php");
    exit();
}


$produits = $pdo->query("SELECT * FROM produits ORDER BY categorie, nom ASC")->fetchAll(PDO::FETCH_ASSOC);


$edit_produit = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([(int)$_GET["edit"]]);
    $edit_produit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$page_title = "Produits";
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
    <h3><?= $edit_produit ? 'Modifier le produit' : 'Ajouter un produit' ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit_produit): ?>
            <input type="hidden" name="id" value="<?= $edit_produit['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= $edit_produit['image'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Nom du produit *</label>
                <input type="text" name="nom" value="<?= $edit_produit ? htmlspecialchars($edit_produit['nom']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Catégorie *</label>
                <select name="categorie" required>
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($edit_produit && $edit_produit['categorie'] == $key) ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Description du produit..."><?= $edit_produit ? htmlspecialchars($edit_produit['description']) : '' ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Prix (€) *</label>
                <input type="number" step="0.01" name="prix" value="<?= $edit_produit ? $edit_produit['prix'] : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Stock *</label>
                <input type="number" name="stock" value="<?= $edit_produit ? $edit_produit['stock'] : '0' ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Image du produit</label>
            <input type="file" name="image" accept="image/*">
            <?php if ($edit_produit && $edit_produit['image']): ?>
                <div style="margin-top: 10px;">
                    <small>Image actuelle :</small><br>
                    <img src="/uploads/produits/<?= $edit_produit['image'] ?>" style="max-width: 100px; margin-top: 5px; border-radius: 4px;">
                </div>
            <?php endif; ?>
        </div>
        
        <button type="submit" name="<?= $edit_produit ? 'edit' : 'add' ?>" class="btn">
            <?= $edit_produit ? 'Modifier' : 'Ajouter' ?>
        </button>
        
        <?php if ($edit_produit): ?>
            <a href="produits.php" class="btn btn-secondary">Annuler</a>
        <?php endif; ?>
    </form>
</div>


<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produits)): ?>
                <tr>
                    <td colspan="6" class="empty-state">Aucun produit pour le moment</td>
                </tr>
            <?php else: ?>
                <?php foreach ($produits as $p): ?>
                <tr>
                    <td class="product-image-cell">
                        <?php if (!empty($p['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/produits/' . $p['image'])): ?>
                            <img src="/uploads/produits/<?= $p['image'] ?>" class="product-thumb">
                        <?php else: ?>
                            <div class="product-thumb placeholder">📷</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($p["nom"]) ?></strong><br>
                        <small><?= htmlspecialchars(substr($p["description"] ?? '', 0, 50)) ?>...</small>
                    </td>
                    <td><?= $categories[$p["categorie"]] ?? $p["categorie"] ?></td>
                    <td><strong><?= number_format($p["prix"], 2) ?> €</strong></td>
                    <td>
                        <div class="stock-control">
                            <a href="?stock=decrement&id=<?= $p['id'] ?>" class="stock-btn stock-minus">-</a>
                            <span class="stock-value <?= $p['stock'] <= 5 ? 'low-stock' : '' ?>"><?= $p["stock"] ?></span>
                            <a href="?stock=increment&id=<?= $p['id'] ?>" class="stock-btn stock-plus">+</a>
                        </div>
                     </td>
                    <td class="actions">
                        <a href="?edit=<?= $p['id'] ?>" class="btn-edit">Modifier</a>
                        <a href="?delete=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a>
                     </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.form-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border: 1px solid #e5e5e5;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

.form-group input, 
.form-group select, 
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.btn {
    background: #1a1a1a;
    color: white;
    padding: 10px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    padding: 10px 24px;
    border-radius: 6px;
    margin-left: 10px;
    display: inline-block;
}

.table-container {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background: #1a1a1a;
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-size: 13px;
}

.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.product-image-cell {
    width: 70px;
}

.product-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
}

.product-thumb.placeholder {
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stock-control {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stock-btn {
    width: 28px;
    height: 28px;
    background: #f0f0f0;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #333;
    font-weight: bold;
}

.stock-btn:hover {
    background: #1a1a1a;
    color: white;
}

.stock-value {
    font-weight: bold;
    min-width: 30px;
    text-align: center;
}

.stock-value.low-stock {
    color: #e74c3c;
}

.btn-edit {
    color: #3498db;
    text-decoration: none;
    margin-right: 12px;
}

.btn-delete {
    color: #e74c3c;
    text-decoration: none;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.empty-state {
    text-align: center;
    padding: 60px;
    color: #999;
}

.actions {
    white-space: nowrap;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .admin-table th, 
    .admin-table td {
        padding: 8px 10px;
        font-size: 12px;
    }
}
</style>

<?php
$content = ob_get_clean();
include "layout.php";
?>