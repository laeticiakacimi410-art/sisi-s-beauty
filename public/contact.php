<?php
session_start();

require_once "../src/config/database.php";
include 'header.php';

$db = new Database();
$pdo = $db->getConnection();

$message_envoye = false;
$erreur = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    
    $sujets_map = [
        'reservation' => 'Prise de rendez-vous',
        'produit' => 'Question sur un produit',
        'information' => 'Demande d\'information',
        'reclamation' => 'Réclamation',
        'partenariat' => 'Partenariat',
        'autre' => 'Autre'
    ];
    
    $sujet_label = $sujets_map[$sujet] ?? $sujet;
    
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($sujet) || empty($message)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Veuillez entrer une adresse email valide.';
    } else {
        try {
            
            $stmt = $pdo->prepare("
                INSERT INTO messages_contact (nom, prenom, email, telephone, sujet, message, lu, repondu, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, NOW())
            ");
            $stmt->execute([$nom, $prenom, $email, $telephone, $sujet_label, $message]);
            
            
            $to = $email;
            $subject = "Confirmation de réception - Sisi's Beauty";
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Sisi's Beauty <contact@sisis-beauty.fr>\r\n";
            $headers .= "Reply-To: contact@sisis-beauty.fr\r\n";
            
            $email_body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #1a1a1a; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #888; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Sisi's Beauty</h2>
                    </div>
                    <div class='content'>
                        <p>Bonjour $prenom $nom,</p>
                        <p>Nous avons bien reçu votre message et nous vous en remercions.</p>
                        <p>Notre équipe traite votre demande et vous répondra dans les plus brefs délais (sous 48h ouvrés).</p>
                        <p><strong>Récapitulatif :</strong></p>
                        <p><strong>Sujet :</strong> $sujet_label</p>
                        <p><strong>Message :</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                        <hr>
                        <p>Cordialement,<br>L'équipe Sisi's Beauty</p>
                    </div>
                    <div class='footer'>
                        <p>© 2024 Sisi's Beauty - Tous droits réservés</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            mail($to, $subject, $email_body, $headers);
            $message_envoye = true;
            $_POST = [];
            
        } catch (PDOException $e) {
            $erreur = "Une erreur est survenue. Veuillez réessayer.";
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Sisi's Beauty</title>
    <link rel="stylesheet" href="/css/contact.css">
</head>
<body>

<div class="contact-page">
    <div class="container">
        
        <div class="contact-header">
            <h1>Contact</h1>
            <p>Nous sommes à votre écoute pour répondre à toutes vos questions</p>
        </div>
        
        <div class="contact-grid">
            
            
            <div class="contact-form-container">
                <h2>Envoyez-nous un message</h2>
                
                <?php if ($message_envoye): ?>
                    <div class="alert alert-success" id="successMessage">
                        <span class="alert-icon">✓</span>
                        Votre message a été envoyé avec succès. Un email de confirmation vous a été envoyé.
                    </div>
                <?php endif; ?>
                
                <?php if ($erreur): ?>
                    <div class="alert alert-error" id="errorMessage">
                        <span class="alert-icon">⚠</span>
                        <?php echo htmlspecialchars($erreur); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="contact-form" id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required 
                                   value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" required 
                                   value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" 
                                   value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sujet">Sujet *</label>
                        <select id="sujet" name="sujet" required>
                            <option value="">Sélectionnez un sujet</option>
                            <option value="reservation" <?php echo (($_POST['sujet'] ?? '') == 'reservation') ? 'selected' : ''; ?>>📅 Prise de rendez-vous</option>
                            <option value="produit" <?php echo (($_POST['sujet'] ?? '') == 'produit') ? 'selected' : ''; ?>>🛍️ Question sur un produit</option>
                            <option value="information" <?php echo (($_POST['sujet'] ?? '') == 'information') ? 'selected' : ''; ?>>ℹ️ Demande d'information</option>
                            <option value="reclamation" <?php echo (($_POST['sujet'] ?? '') == 'reclamation') ? 'selected' : ''; ?>>⚠️ Réclamation</option>
                            <option value="partenariat" <?php echo (($_POST['sujet'] ?? '') == 'partenariat') ? 'selected' : ''; ?>>🤝 Partenariat</option>
                            <option value="autre" <?php echo (($_POST['sujet'] ?? '') == 'autre') ? 'selected' : ''; ?>>📝 Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="6" required 
                                  placeholder="Décrivez votre demande..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span>✉️</span> Envoyer le message
                    </button>
                </form>
            </div>
            
            
            <div class="contact-infos-container">
                <h2>Informations pratiques</h2>
                
                <div class="info-card">
                    <div class="info-icon"></div>
                    <div class="info-content">
                        <h3>Notre salon</h3>
                        <p>123 rue de la Paix<br>75002 Paris, France</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon"></div>
                    <div class="info-content">
                        <h3>Horaires d'ouverture</h3>
                        <p>Mardi - Samedi : 10h - 19h<br>
                        Dimanche & Lundi : Fermé</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon"></div>
                    <div class="info-content">
                        <h3>Contact direct</h3>
                        <p>Tél : 01 23 45 67 89<br>
                        Email : contact@sisis-beauty.fr</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon"></div>
                    <div class="info-content">
                        <h3>Délai de réponse</h3>
                        <p>Nous répondons à tous les messages<br>sous 48h ouvrés.</p>
                    </div>
                </div>
                
                <div class="social-section">
                    <h3>Suivez-nous</h3>
                    <div class="social-links">
                        <a href="#" class="social-link instagram"> Instagram</a>
                        <a href="#" class="social-link facebook"> Facebook</a>
                        <a href="#" class="social-link pinterest"> Pinterest</a>
                    </div>
                </div>
                
                <div class="map-section">
                    <h3>Nous trouver</h3>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.292292615509614!3d48.85837360893654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sOp%C3%A9ra%20Garnier!5e0!3m2!1sfr!2sfr!4v1640000000000!5m2!1sfr!2sfr" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="/js/contact.js"></script>

<?php include 'footer.php'; ?>
</body>
</html>