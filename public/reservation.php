<?php
session_start();
include 'header.php';
require_once "../src/config/database.php";
require_once __DIR__ . '/../src/services/ReservationService.php';

$db = new Database();
$pdo = $db->getConnection();
$reservationService = new ReservationService($pdo);


$prestation_id_url = isset($_GET['prestation']) ? (int)$_GET['prestation'] : null;


$prestations = $pdo->query("
    SELECT id, nom, description, duree, prix, categorie, type_prestation 
    FROM prestations 
    ORDER BY type_prestation, nom
")->fetchAll(PDO::FETCH_ASSOC);


$types_labels = [
    'coiffure' => 'Coiffure',
    'esthetique' => 'Esthétique',
    'manucure' => 'Manucure',
    'epilation' => 'Épilation',
    'massage' => 'Massage / Bien-être',
    'makeup' => 'Maquillage',
    'forfait' => 'Forfaits'
];


$reservation_success = false;
$reservation_error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserver') {
    $prestation_id = (int)$_POST['prestation_id'];
    $date_rdv = $_POST['date_rdv'];
    $heure_rdv = $_POST['heure_rdv'];
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $commentaires = trim($_POST['commentaires'] ?? '');
    
    $errors = [];
    if (!$prestation_id) $errors[] = "Prestation non sélectionnée";
    if (!$date_rdv) $errors[] = "Date non sélectionnée";
    if (!$heure_rdv) $errors[] = "Horaire non sélectionné";
    if (!$nom) $errors[] = "Nom requis";
    if (!$prenom) $errors[] = "Prénom requis";
    if (!$email) $errors[] = "Email requis";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    
    if (empty($errors)) {
        
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $utilisateur_id = $user['id'];
            $_SESSION['user_id'] = $utilisateur_id;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, telephone, rolee) 
                VALUES (?, ?, ?, ?, 'client')
            ");
            $stmt->execute([$nom, $prenom, $email, $telephone]);
            $utilisateur_id = $pdo->lastInsertId();
            
            $_SESSION['user_id'] = $utilisateur_id;
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;
        }
        
        
        $result = $reservationService->createReservation($utilisateur_id, $prestation_id, $date_rdv, $heure_rdv);
        
        $debug_info = "<pre>Résultat du service: " . print_r($result, true) . "</pre>";
        
        if (isset($result['error'])) {
            $reservation_error = $result['error'];
        } else {
            
            if ($commentaires && isset($result['reservation_id'])) {
                $stmt = $pdo->prepare("UPDATE rendez_vous SET commentaires = ? WHERE id = ?");
                $stmt->execute([$commentaires, $result['reservation_id']]);
            }
            $reservation_success = true;
        }
    } else {
        $reservation_error = implode(", ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/reservation.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="reservation-page">
    <div class="container">
        
        <div class="reservation-header">
            <h1>Réservation</h1>
            <p>Choisissez votre prestation, puis sélectionnez la date et l'heure</p>
        </div>
        
        <?php if ($reservation_success): ?>
            <div class="message-success">
                ✓ Votre réservation a été confirmée ! Nous vous attendons le <?php echo date('d/m/Y', strtotime($date_rdv)); ?> à <?php echo $heure_rdv; ?>.
            </div>
        <?php endif; ?>
        
        <?php if ($reservation_error): ?>
            <div class="message-error">
                ⚠ <?php echo htmlspecialchars($reservation_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($debug_info && !$reservation_success): ?>
            <div class="debug-info" style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; font-size: 12px; display: none;">
                <?php echo $debug_info; ?>
            </div>
        <?php endif; ?>
        
        <div class="reservation-container">
            
            
            <div class="reservation-step step-1 active" data-step="1">
                <div class="step-number">01</div>
                <h2>Choisissez votre prestation</h2>
                
                <div class="prestations-categories">
                    <?php
                    $categorie_actuelle = '';
                    foreach ($prestations as $p):
                        $categorie_nom = $types_labels[$p['type_prestation']] ?? ucfirst($p['type_prestation']);
                        
                        if ($categorie_actuelle != $p['type_prestation']):
                            if ($categorie_actuelle != ''): echo '</div></div>'; endif;
                            $categorie_actuelle = $p['type_prestation'];
                    ?>
                            <div class="categorie-bloc">
                                <h3><?php echo $categorie_nom; ?></h3>
                                <div class="prestations-grid">
                    <?php endif; ?>
                                    
                                    <div class="prestation-card <?php echo ($prestation_id_url == $p['id']) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $p['id']; ?>" 
                                         data-duree="<?php echo $p['duree']; ?>" 
                                         data-prix="<?php echo $p['prix']; ?>" 
                                         data-nom="<?php echo htmlspecialchars($p['nom']); ?>">
                                        <h4><?php echo htmlspecialchars($p['nom']); ?></h4>
                                        <p class="prestation-desc"><?php echo htmlspecialchars(substr($p['description'], 0, 80)); ?>...</p>
                                        <div class="prestation-meta">
                                            <span class="prestation-duree">⏱ <?php echo $p['duree']; ?> min</span>
                                            <span class="prestation-prix"><?php echo number_format($p['prix'], 2); ?> €</span>
                                        </div>
                                    </div>
                                    
                    <?php endforeach; ?>
                                </div>
                            </div>
                </div>
                
                <button class="btn-next-step" disabled>Continuer</button>
            </div>
            
            
            <div class="reservation-step step-2" data-step="2">
                <div class="step-number">02</div>
                <h2>Choisissez la date</h2>
                
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button type="button" class="calendar-prev">←</button>
                        <span class="calendar-month-year"></span>
                        <button type="button" class="calendar-next">→</button>
                    </div>
                    <div class="calendar-weekdays">
                        <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
                    </div>
                    <div class="calendar-days" id="calendar-days"></div>
                </div>
                
                <div class="selected-info">
                    <p>Prestation sélectionnée : <strong id="selected-prestation-nom">-</strong></p>
                    <p>Durée : <strong id="selected-prestation-duree">-</strong> minutes</p>
                </div>
                
                <div class="step-buttons">
                    <button type="button" class="btn-prev-step">Retour</button>
                    <button type="button" class="btn-next-step" disabled>Continuer</button>
                </div>
            </div>
            
            
            <div class="reservation-step step-3" data-step="3">
                <div class="step-number">03</div>
                <h2>Choisissez l'horaire</h2>
                
                <div class="selected-date-info">
                    <p>Date sélectionnée : <strong id="selected-date-display">-</strong></p>
                    <p>Prestation : <strong id="selected-prestation-nom2">-</strong></p>
                </div>
                
                <div class="horaires-container">
                    <h3>Créneaux disponibles</h3>
                    <div class="horaires-grid" id="horaires-grid">
                        <p class="loading">Chargement des créneaux...</p>
                    </div>
                </div>
                
                <div class="step-buttons">
                    <button type="button" class="btn-prev-step">Retour</button>
                    <button type="button" class="btn-next-step" disabled>Continuer</button>
                </div>
            </div>
            
            
            <div class="reservation-step step-4" data-step="4">
                <div class="step-number">04</div>
                <h2>Vos informations</h2>
                
                <form method="POST" id="reservation-form">
                    <input type="hidden" name="action" value="reserver">
                    <input type="hidden" name="prestation_id" id="form-prestation-id">
                    <input type="hidden" name="date_rdv" id="form-date">
                    <input type="hidden" name="heure_rdv" id="form-heure">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" required value="<?php echo htmlspecialchars($_SESSION['user_nom'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" required value="<?php echo htmlspecialchars($_SESSION['user_prenom'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="telephone">
                    </div>
                    
                    <div class="form-group">
                        <label>Commentaires</label>
                        <textarea name="commentaires" rows="3" placeholder="Informations complémentaires..."></textarea>
                    </div>
                    
                    <div class="recap-commande">
                        <h3>Récapitulatif</h3>
                        <div class="recap-row">
                            <span>Prestation :</span>
                            <strong id="recap-prestation">-</strong>
                        </div>
                        <div class="recap-row">
                            <span>Date :</span>
                            <strong id="recap-date">-</strong>
                        </div>
                        <div class="recap-row">
                            <span>Horaire :</span>
                            <strong id="recap-heure">-</strong>
                        </div>
                        <div class="recap-row">
                            <span>Prix :</span>
                            <strong id="recap-prix">-</strong> €
                        </div>
                    </div>
                    
                    <div class="step-buttons">
                        <button type="button" class="btn-prev-step">Retour</button>
                        <button type="submit" class="btn-submit-reservation">Confirmer la réservation</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>


<script>
    window.prestationsData = <?php 
        $data = [];
        foreach ($prestations as $p) {
            $data[$p['id']] = [
                'nom' => $p['nom'],
                'duree' => $p['duree'],
                'prix' => $p['prix']
            ];
        }
        echo json_encode($data);
    ?>;
    
    window.prestationIdFromUrl = <?php echo $prestation_id_url ?: 'null'; ?>;
</script>

<script src="/js/reservation.js"></script>

<?php include 'footer.php'; ?>