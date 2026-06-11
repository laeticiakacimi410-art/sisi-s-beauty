<?php

require_once __DIR__ . "/../utils/mailer.php";

class ContactController {

    public function send($data) {

        $nom = trim($data["nom"] ?? "");
        $email = trim($data["email"] ?? "");
        $message = trim($data["message"] ?? "");

        
        if (empty($nom) || empty($email) || empty($message)) {
            return ["error" => "Tous les champs sont obligatoires"];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["error" => "Email invalide"];
        }

        
        $result = sendMail($nom, $email, $message);

        if ($result) {
            return ["success" => "Message envoyé avec succès"];
        } else {
            return ["error" => "Erreur lors de l'envoi"];
        }
    }
}