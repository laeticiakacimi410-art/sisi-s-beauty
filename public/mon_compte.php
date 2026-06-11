<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: /connexion.php');
    exit;
}

require_once '../src/config/database.php';
require_once '../src/repositories/UserRepository.php';
require_once '../src/repositories/ReservationRepository.php';
require_once '../src/services/UserService.php';

$db = new Database();
$pdo = $db->getConnection();

$userRepo = new UserRepository($pdo);
$reservationRepo = new ReservationRepository($pdo);
$userService = new UserService($pdo);

$user_id = $_SESSION['user_id'];
$user = $userRepo->findById($user_id);


$success_message = '';
$error_message = '';
$tab_active = $_GET['tab'] ?? 'profil';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_profil') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');

        
        if (empty($nom) || empty($prenom) || empty($email)) {
            $error_message = "Les champs nom, prénom et email sont obligatoires";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Email invalide";
        } else {
            
            $emailExists = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $emailExists->execute([$email, $user_id]);
            
            if ($emailExists->fetch()) {
                $error_message = "Cet email est déjà utilisé";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs 
                    SET nom = ?, prenom = ?, email = ?, telephone = ?
                    WHERE id = ?
                ");
                if ($stmt->execute([$nom, $prenom, $email, $telephone, $user_id])) {
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_email'] = $email;
                    $user = $userRepo->findById($user_id);
                    $success_message = "Profil mis à jour avec succès";
                } else {
                    $error_message = "Erreur lors de la mise à jour";
                }
            }
        }
        $tab_active = 'profil';
    }
    
    
    
    
    elseif ($_POST['action'] === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Tous les champs sont obligatoires";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Le nouveau mot de passe doit avoir au moins 6 caractères";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Les mots de passe ne correspondent pas";
        } elseif (!password_verify($old_password, $user['mot_de_passe'])) {
            $error_message = "L'ancien mot de passe est incorrect";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                $success_message = "Mot de passe changé avec succès";
            } else {
                $error_message = "Erreur lors du changement de mot de passe";
            }
        }
        $tab_active = 'securite';
    }
    
    
    
    
    elseif ($_POST['action'] === 'cancel_reservation') {
        $reservation_id = $_POST['reservation_id'] ?? 0;
        
        if ($reservation_id > 0) {
            
            $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE id = ? AND utilisateur_id = ?");
            $stmt->execute([$reservation_id, $user_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reservation) {
                $deleteStmt = $pdo->prepare("DELETE FROM rendez_vous WHERE id = ?");
                if ($deleteStmt->execute([$reservation_id])) {
                    $success_message = "Réservation annulée avec succès";
                } else {
                    $error_message = "Erreur lors de l'annulation";
                }
            } else {
                $error_message = "Réservation introuvable";
            }
        }
        $tab_active = 'reservations';
    }
}


$stmt = $pdo->prepare("
    SELECT rv.*, p.nom as prestation_nom, p.prix, p.duree
    FROM rendez_vous rv
    LEFT JOIN prestations p ON rv.prestation_id = p.id
    WHERE rv.utilisateur_id = ?
    ORDER BY rv.date_rdv DESC, rv.heure DESC
");
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
    SELECT c.*, COUNT(lc.id) as nb_articles
    FROM commandes c
    LEFT JOIN lignes_commandes lc ON c.id = lc.commande_id
    WHERE c.utilisateur_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .compte-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .compte-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        .compte-header h1 {
            margin: 0;
            color: #333;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 20px;
            background: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #ff69b4;
        }

        .tab-btn.active {
            color: #ff69b4;
            border-bottom-color: #ff69b4;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff69b4;
            box-shadow: 0 0 5px rgba(255, 105, 180, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            background: #ff69b4;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }

        .btn-submit:hover {
            background: #ff1493;
        }

        
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table thead {
            background: #f5f5f5;
        }

        table th {
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        table tr:hover {
            background: #f9f9f9;
        }

        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        
        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }

        .close-modal:hover {
            color: #333;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .tabs {
                gap: 5px;
            }

            .tab-btn {
                padding: 10px 15px;
                font-size: 14px;
            }

            table {
                font-size: 12px;
            }

            table th,
            table td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>

<?php include '../public/header.php'; ?>

<main class="compte-container">
    
    <div class="compte-header">
        <h1> Mon Compte</h1>
        <a href="/deconnexion.php" class="logout-btn">Se déconnecter</a>
    </div>

    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    
    <div class="tabs">
        <button class="tab-btn <?php echo $tab_active === 'profil' ? 'active' : ''; ?>" onclick="switchTab('profil')">
            Mon Profil
        </button>
        <button class="tab-btn <?php echo $tab_active === 'reservations' ? 'active' : ''; ?>" onclick="switchTab('reservations')">
            Mes Rendez-vous (<?php echo count($reservations); ?>)
        </button>
        <button class="tab-btn <?php echo $tab_active === 'commandes' ? 'active' : ''; ?>" onclick="switchTab('commandes')">
            Mes Commandes (<?php echo count($commandes); ?>)
        </button>
        <button class="tab-btn <?php echo $tab_active === 'securite' ? 'active' : ''; ?>" onclick="switchTab('securite')">
            Sécurité
        </button>
    </div>

    
    <div id="profil" class="tab-content <?php echo $tab_active === 'profil' ? 'active' : ''; ?>">
        <div class="card">
            <div class="card-title">Informations Personnelles</div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profil">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit">💾 Enregistrer les modifications</button>
            </form>
        </div>

        <div class="card">
            <div class="card-title">Informations du Compte</div>
            <div class="info-row">
                <span class="info-label">ID Client:</span>
                <span class="info-value">#<?php echo $user['id']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Compte créé le:</span>
                <span class="info-value">
                    <?php 
                    $date = new DateTime($user['created_at']);
                    echo $date->format('d/m/Y à H:i'); 
                    ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="info-value">
                    <?php 
                    $role = ucfirst(str_replace('_', ' ', $user['rolee'] ?? 'user'));
                    echo htmlspecialchars($role);
                    ?>
                </span>
            </div>
        </div>
    </div>

    
    <div id="reservations" class="tab-content <?php echo $tab_active === 'reservations' ? 'active' : ''; ?>">
        <?php if (count($reservations) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Prestation</th>
                        <th>Durée</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $rdv): ?>
                        <tr>
                            <td>
                                <?php 
                                $date = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure']);
                                echo $date->format('d/m/Y à H:i'); 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($rdv['prestation_nom'] ?? 'Service'); ?></td>
                            <td><?php echo $rdv['duree'] ?? '-'; ?> min</td>
                            <td><?php echo number_format($rdv['prix'] ?? 0, 2, ',', ' '); ?> €</td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($rdv['statut']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $rdv['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="cancel_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $rdv['id']; ?>">
                                    <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Confirmer l\'annulation ?')">
                                        Annuler
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon"></div>
                <p>Vous n'avez pas encore de rendez-vous.</p>
                <a href="/reservation.php" class="btn-submit" style="display: inline-block; margin-top: 15px; text-decoration: none;">Réserver un rendez-vous</a>
            </div>
        <?php endif; ?>
    </div>

    
    <div id="commandes" class="tab-content <?php echo $tab_active === 'commandes' ? 'active' : ''; ?>">
        <?php if (count($commandes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Articles</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $cmd): ?>
                        <tr>
                            <td>#<?php echo str_pad($cmd['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <?php 
                                $date = new DateTime($cmd['created_at']);
                                echo $date->format('d/m/Y à H:i'); 
                                ?>
                            </td>
                            <td><?php echo $cmd['nb_articles'] ?? 0; ?> article(s)</td>
                            <td><?php echo number_format($cmd['total'], 2, ',', ' '); ?> €</td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($cmd['statut']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $cmd['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="/commande-details.php?id=<?php echo $cmd['id']; ?>" class="btn-sm btn-info">
                                    Voir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon"></div>
                <p>Vous n'avez pas encore de commandes.</p>
                <a href="/boutique.php" class="btn-submit" style="display: inline-block; margin-top: 15px; text-decoration: none;">Consulter la boutique</a>
            </div>
        <?php endif; ?>
    </div>

    
    <div id="securite" class="tab-content <?php echo $tab_active === 'securite' ? 'active' : ''; ?>">
        <div class="card">
            <div class="card-title">Changer le Mot de Passe</div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="old_password">Mot de passe actuel</label>
                    <input type="password" id="old_password" name="old_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small style="color: #999;">Minimum 6 caractères</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn-submit"> Changer le mot de passe</button>
            </form>
        </div>

        <div class="card">
            <div class="card-title">Sécurité du Compte</div>
            <div class="info-row">
                <span class="info-label">Dernière connexion:</span>
                <span class="info-value">Aujourd'hui</span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut:</span>
                <span class="info-value"> Actif</span>
            </div>
            <div class="info-row">
                <span class="info-label">Authentification:</span>
                <span class="info-value">Mot de passe</span>
            </div>
        </div>
    </div>

</main>

<?php include '../public/footer.php'; ?>

<script>
    function switchTab(tabName) {
        
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        
        document.getElementById(tabName).classList.add('active');
        
        
        event.target.classList.add('active');
    }
</script>

</body>
</html>
