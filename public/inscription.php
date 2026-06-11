<?php

session_start();

require_once "../src/config/database.php";
require_once "../src/services/AuthService.php";

$error = "";
$success = "";

$db = new Database();
$pdo = $db->getConnection();

$authService = new AuthService($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $result = $authService->register(
        $_POST["nom"] ?? "",
        $_POST["prenom"] ?? "",
        $_POST["email"] ?? "",
        $_POST["password"] ?? "",
        $_POST["confirm_password"] ?? ""
    );

    if (isset($result["error"])) {
        $error = $result["error"];
    } else {
        $success = "Compte créé avec succès";
        header("Location: /connexion.php");
        exit();
    }
}

include 'header.php';
?>


<div class="connexion-page">
    <div class="connexion-card">
        <h2>Inscription</h2>
        <div class="connexion-subtitle">Créez votre compte client</div>
        
        <?php if (!empty($error)): ?>
            <div class="message-error"> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message-success"> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="connexion-form">
            <div class="form-row">
                <div class="form-group half">
                    <label>Nom</label>
                    <input type="text" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" placeholder="Votre nom">
                </div>
                
                <div class="form-group half">
                    <label>Prénom</label>
                    <input type="text" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" placeholder="Votre prénom">
                </div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="votre@email.com">
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label>Mot de passe (min. 6)</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                
                <div class="form-group half">
                    <label>Confirmer</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••">
                </div>
            </div>
            
            <button type="submit" class="btn-connexion-submit">Créer mon compte</button>
        </form>
        
        <div class="connexion-links">
            <p>Déjà un compte ? <a href="/connexion.php">Se connecter</a></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>