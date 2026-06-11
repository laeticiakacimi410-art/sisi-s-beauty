<?php

require_once __DIR__ . "/../repositories/UserRepository.php";

class AuthService {

    private $userRepo;

    public function __construct($pdo) {
        $this->userRepo = new UserRepository($pdo);
    }

    public function login($email, $password) {

        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ["error" => "Aucun compte trouvé avec cet email."];
        }

        if (!password_verify($password, $user["mot_de_passe"])) {
            return ["error" => "Mot de passe incorrect."];
        }

        return ["user" => $user];
    }

    public function register($nom, $prenom, $email, $password, $confirm)
    {
        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            return ["error" => "Champs manquants"];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["error" => "Email invalide"];
        }

        if (strlen($password) < 6) {
            return ["error" => "Mot de passe trop court"];
        }

        if ($password !== $confirm) {
            return ["error" => "Mots de passe différents"];
        }

        $existing = $this->userRepo->findByEmail($email);

        if ($existing) {
            return ["error" => "Email déjà utilisé"];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->userRepo->createUser($nom, $prenom, $email, $hash);

        return ["success" => true];
    }
}