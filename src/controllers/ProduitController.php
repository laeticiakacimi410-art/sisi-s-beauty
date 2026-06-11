<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../repositories/ProduitRepository.php";

class ProduitController {

    private $produitRepo;

    public function __construct() {
        $db = new Database();
        $pdo = $db->getConnection();
        $this->produitRepo = new ProduitRepository($pdo);
    }

    
    public function getAllProduits() {
        return $this->produitRepo->findAll();
    }
}