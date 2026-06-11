<?php
session_start();

require_once "../src/config/database.php";
require_once "../src/repositories/UserRepository.php";
require_once "../src/services/AuthService.php";

$error = "";


if (isset($_SESSION["user_id"])) {

    if (isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin") {
        header("Location: /admin/index.php");
        exit();
    } else {
        header("Location: /index.php");
        exit();
    }
}


$db = new Database();
$pdo = $db->getConnection();

$authService = new AuthService($pdo);


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Veuillez remplir tous les champs.";
    } else {

        $result = $authService->login($email, $password);

        if (isset($result["error"])) {
            $error = $result["error"];
        } else {

            $user = $result["user"];

            
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_nom"] = $user["nom"];
            $_SESSION["user_prenom"] = $user["prenom"] ?? "";
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["rolee"] ?? "user";

            
            if ($user["rolee"] === "admin") {
                header("Location: /admin/index.php");
                exit();
            } else {
                header("Location: /index.php");
                exit();
            }
        }
    }
}

include "header.php";
?>

<div class="connexion-page">
    <div class="connexion-card">

        <h2>Connexion</h2>

        <?php if (!empty($error)): ?>
            <div class="message-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="connexion-form">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-connexion-submit">
                Se connecter
            </button>

            <p style="text-align:center; margin-top:15px;">
                Première connexion ?
                <a href="./inscription.php">Inscription</a>
            </p>

        </form>

    </div>
</div>

<?php include "footer.php"; ?>