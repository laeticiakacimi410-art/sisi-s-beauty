<?php
require_once '../../src/config/database.php';
require_once '../../src/repositories/ReservationRepository.php';

$pdo = $db->getConnection();
$repo = new ReservationRepository($pdo);

$reservations = $repo->findAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des rendez-vous</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<h1>Gestion des rendez-vous</h1>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Prestation</th>
            <th>Statut</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($reservations as $reservation): ?>
            <tr>
                <td><?= $reservation['id'] ?></td>
                <td><?= htmlspecialchars($reservation['nom_client']) ?></td>
                <td><?= $reservation['date_rdv'] ?></td>
                <td><?= $reservation['heure_rdv'] ?></td>
                <td><?= htmlspecialchars($reservation['prestation']) ?></td>
                <td><?= htmlspecialchars($reservation['statut']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>