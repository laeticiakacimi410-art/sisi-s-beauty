<?php

require_once __DIR__ . "/../repositories/UserRepository.php";

class UserService {

    private $userRepo;

    public function __construct($pdo) {
        $this->userRepo = new UserRepository($pdo);
    }

    
    public function register($nom, $prenom, $email, $password, $confirm) {

        
        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            return ["error" => "Tous les champs sont obligatoires"];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["error" => "Email invalide"];
        }

        if (strlen($password) < 6) {
            return ["error" => "Mot de passe trop court (min 6 caractères)"];
        }

        if ($password !== $confirm) {
            return ["error" => "Les mots de passe ne correspondent pas"];
        }

        
        $existing = $this->userRepo->findByEmail($email);

        if ($existing) {
            return ["error" => "Cet email est déjà utilisé"];
        }

        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        
        $this->userRepo->createUser($nom, $prenom, $email, $hashedPassword);

        return ["success" => "Compte créé avec succès"];
    }

    
    public function login($email, $password) {

        if (empty($email) || empty($password)) {
            return ["error" => "Champs manquants"];
        }

        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ["error" => "Aucun compte trouvé"];
        }

        if (!password_verify($password, $user["mot_de_passe"])) {
            return ["error" => "Mot de passe incorrect"];
        }

        return ["user" => $user];
    }
}