<?php

require_once __DIR__ . "/../services/AuthService.php";
require_once __DIR__ . "/../config/database.php";

class AuthController {

    private $authService;

    public function __construct() {
        $db = new Database();
        $pdo = $db->getConnection();
        $this->authService = new AuthService($pdo);
    }

    
    public function login($data) {
        return $this->authService->login(
            $data["email"] ?? "",
            $data["password"] ?? ""
        );
    }

    
    public function register($data) {
        return $this->authService->register(
            $data["nom"] ?? "",
            $data["prenom"] ?? "",
            $data["email"] ?? "",
            $data["password"] ?? "",
            $data["confirm_password"] ?? ""
        );
    }
}