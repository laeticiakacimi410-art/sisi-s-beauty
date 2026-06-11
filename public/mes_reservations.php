<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: /connexion.php');
    exit;
}

require_once '../src/config/database.php';
include 'header.php';

$db = new Database();
$pdo = $db->getConnection();

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
    $reservation_id = (int)$_POST['reservation_id'];
    
    
    $stmt = $pdo->prepare("
        SELECT rv.*, p.nom as prestation_nom 
        FROM rendez_vous rv
        LEFT JOIN prestations p ON rv.prestation_id = p.id
        WHERE rv.id = ? AND rv.utilisateur_id = ?
    ");
    $stmt->execute([$reservation_id, $user_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reservation) {
        
        $date_heure_rdv = new DateTime($reservation['date_rdv'] . ' ' . $reservation['heure']);
        $now = new DateTime();
        $interval = $now->diff($date_heure_rdv);
        
        if ($date_heure_rdv > $now && $interval->days < 1 && $interval->h < 24) {
            $error_message = "Vous ne pouvez annuler une réservation moins de 24h à l'avance. Contactez-nous directement.";
        } else {
            $deleteStmt = $pdo->prepare("DELETE FROM rendez_vous WHERE id = ?");
            if ($deleteStmt->execute([$reservation_id])) {
                $success_message = "Réservation annulée avec succès";
            } else {
                $error_message = "Erreur lors de l'annulation";
            }
        }
    } else {
        $error_message = "Réservation introuvable";
    }
}


$stmt = $pdo->prepare("
    SELECT 
        rv.*, 
        p.nom as prestation_nom, 
        p.prix, 
        p.duree,
        p.description as prestation_description
    FROM rendez_vous rv
    LEFT JOIN prestations p ON rv.prestation_id = p.id
    WHERE rv.utilisateur_id = ?
    ORDER BY 
        CASE 
            WHEN rv.date_rdv >= CURDATE() THEN 0 
            ELSE 1 
        END,
        rv.date_rdv ASC,
        rv.heure ASC
");
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);


$upcoming_reservations = [];
$past_reservations = [];

foreach ($reservations as $rdv) {
    $date_heure_rdv = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure']);
    $now = new DateTime();
    
    if ($date_heure_rdv >= $now) {
        $upcoming_reservations[] = $rdv;
    } else {
        $past_reservations[] = $rdv;
    }
}


$total_reservations = count($reservations);
$upcoming_count = count($upcoming_reservations);
$past_count = count($past_reservations);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/mes_reservations.css">
</head>
<body>

<main class="reservations-page">
    <div class="container">
        
        
        <div class="page-header">
            <div>
                <h1>Mes Réservations</h1>
                <p>Gérez vos rendez-vous chez Sisi's Beauty</p>
            </div>
            <a href="/reservation.php" class="btn-new-reservation">
                <span>+</span> Nouvelle réservation
            </a>
        </div>
        
        
        <?php if ($success_message): ?>
            <div class="alert alert-success" id="successMessage">
                <span class="alert-icon">✓</span>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error" id="errorMessage">
                <span class="alert-icon">⚠</span>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number" data-target="<?php echo $total_reservations; ?>">0</div>
                <div class="stat-label">Total rendez-vous</div>
            </div>
            <div class="stat-card upcoming">
                <div class="stat-number" data-target="<?php echo $upcoming_count; ?>">0</div>
                <div class="stat-label">À venir</div>
            </div>
            <div class="stat-card past">
                <div class="stat-number" data-target="<?php echo $past_count; ?>">0</div>
                <div class="stat-label">Passés</div>
            </div>
        </div>
        
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="upcoming">
                À venir 
                <?php if ($upcoming_count > 0): ?>
                    <span class="tab-count"><?php echo $upcoming_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-btn" data-tab="past">
                Historique 
                <?php if ($past_count > 0): ?>
                    <span class="tab-count"><?php echo $past_count; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        
        <div id="tab-upcoming" class="tab-content active">
            <?php if (empty($upcoming_reservations)): ?>
                <div class="empty-state">
                    <div class="empty-icon"></div>
                    <h3>Aucune réservation à venir</h3>
                    <p>Vous n'avez pas encore de rendez-vous programmé.</p>
                    <a href="/reservation.php" class="btn-empty-action">Réserver maintenant</a>
                </div>
            <?php else: ?>
                <div class="reservations-grid">
                    <?php foreach ($upcoming_reservations as $rdv): ?>
                        <div class="reservation-card upcoming" data-id="<?php echo $rdv['id']; ?>">
                            <div class="card-badge">
                                <?php
                                $date_rdv = new DateTime($rdv['date_rdv']);
                                $today = new DateTime();
                                $diff = $today->diff($date_rdv);
                                
                                if ($diff->days == 0 && $diff->h < 24) {
                                    echo '<span class="badge-today">Aujourd\'hui</span>';
                                } elseif ($diff->days == 1) {
                                    echo '<span class="badge-tomorrow">Demain</span>';
                                } else {
                                    echo '<span class="badge-upcoming">À venir</span>';
                                }
                                ?>
                            </div>
                            
                            <div class="card-date">
                                <div class="date-day"><?php echo $date_rdv->format('d'); ?></div>
                                <div class="date-month"><?php echo $date_rdv->format('M'); ?></div>
                            </div>
                            
                            <div class="card-content">
                                <h3><?php echo htmlspecialchars($rdv['prestation_nom'] ?? 'Prestation'); ?></h3>
                                
                                <div class="card-info">
                                    <div class="info-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        <span><?php echo $date_rdv->format('l d/m/Y'); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        <span><?php echo substr($rdv['heure'], 0, 5); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 12V8H6V4H4V20H6V16H20V12Z"/>
                                            <circle cx="12" cy="12" r="2"/>
                                        </svg>
                                        <span><?php echo $rdv['duree'] ?? '-'; ?> min</span>
                                    </div>
                                    <div class="info-item price">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="2" x2="12" y2="6"/>
                                            <line x1="12" y1="18" x2="12" y2="22"/>
                                            <line x1="2" y1="12" x2="6" y2="12"/>
                                            <line x1="18" y1="12" x2="22" y2="12"/>
                                        </svg>
                                        <span><?php echo number_format($rdv['prix'] ?? 0, 2); ?> €</span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($rdv['commentaires'])): ?>
                                    <div class="card-comment">
                                        <span class="comment-label"> Note :</span>
                                        <p><?php echo htmlspecialchars(substr($rdv['commentaires'], 0, 60)); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-actions">
                                <form method="POST" class="cancel-form" data-id="<?php echo $rdv['id']; ?>">
                                    <input type="hidden" name="action" value="cancel_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $rdv['id']; ?>">
                                    <button type="submit" class="btn-cancel">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"/>
                                            <line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                        Annuler
                                    </button>
                                </form>
                                <button class="btn-calendar" data-event='<?php echo json_encode([
                                    'nom' => $rdv['prestation_nom'],
                                    'date' => $rdv['date_rdv'],
                                    'heure' => $rdv['heure'],
                                    'duree' => $rdv['duree']
                                ]); ?>'>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    Agenda
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        
        <div id="tab-past" class="tab-content">
            <?php if (empty($past_reservations)): ?>
                <div class="empty-state">
                    <div class="empty-icon"></div>
                    <h3>Aucun historique</h3>
                    <p>Vos rendez-vous passés apparaîtront ici.</p>
                </div>
            <?php else: ?>
                <div class="reservations-list past-list">
                    <?php foreach ($past_reservations as $rdv): ?>
                        <div class="reservation-history-item">
                            <div class="history-icon">💇‍♀️</div>
                            <div class="history-content">
                                <div class="history-header">
                                    <h4><?php echo htmlspecialchars($rdv['prestation_nom'] ?? 'Prestation'); ?></h4>
                                    <span class="history-date">
                                        <?php 
                                        $date = new DateTime($rdv['date_rdv']);
                                        echo $date->format('d/m/Y');
                                        ?> à <?php echo substr($rdv['heure'], 0, 5); ?>
                                    </span>
                                </div>
                                <div class="history-details">
                                    <span>Durée: <?php echo $rdv['duree'] ?? '-'; ?> min</span>
                                    <span>•</span>
                                    <span><?php echo number_format($rdv['prix'] ?? 0, 2); ?> €</span>
                                </div>
                            </div>
                            <div class="history-badge completed">✓ Effectué</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</main>

<script src="/js/mes_reservations.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>