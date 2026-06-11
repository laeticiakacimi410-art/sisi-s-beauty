<?php
require_once '../../src/config/database.php';
require_once '../../src/repositories/ProduitRepository.php';

$pdo = $db->getConnection();
$repo = new ProduitRepository($pdo);

$produits = $repo->findAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des produits</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<h1>Gestion des produits</h1>

<a href="ajouter-produit.php">Ajouter un produit</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($produits as $produit): ?>
            <tr>
                <td><?= $produit['id'] ?></td>
                <td><?= htmlspecialchars($produit['nom']) ?></td>
                <td><?= $produit['prix'] ?> €</td>
                <td><?= $produit['stock'] ?></td>
                <td>
                    <a href="modifier-produit.php?id=<?= $produit['id'] ?>">Modifier</a>
                    <a href="supprimer-produit.php?id=<?= $produit['id'] ?>"
                       onclick="return confirm('Supprimer ce produit ?')">
                        Supprimer
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
