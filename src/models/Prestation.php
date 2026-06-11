<?php
require_once '../../src/config/database.php';

$pdo = $db->getConnection();

$query = $pdo->query("SELECT * FROM prestations ORDER BY id DESC");
$prestations = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des prestations</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<h1>Gestion des prestations</h1>

<a href="ajouter-prestation.php">Ajouter une prestation</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Prix</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($prestations as $prestation): ?>
            <tr>
                <td><?= $prestation['id'] ?></td>
                <td><?= htmlspecialchars($prestation['nom']) ?></td>
                <td><?= htmlspecialchars($prestation['description']) ?></td>
                <td><?= $prestation['prix'] ?> €</td>
                <td>
                    <a href="modifier-prestation.php?id=<?= $prestation['id'] ?>">Modifier</a>
                    <a href="supprimer-prestation.php?id=<?= $prestation['id'] ?>"
                       onclick="return confirm('Supprimer cette prestation ?')">
                        Supprimer
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>