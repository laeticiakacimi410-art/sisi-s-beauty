<?php

require_once __DIR__ . "/../repositories/ProduitRepository.php";

class ProduitService {

    private $produitRepo;

    public function __construct($pdo) {
        $this->produitRepo = new ProduitRepository($pdo);
    }

    
    public function getAllProduits() {
        return $this->produitRepo->findAll();
    }

    
    public function getProduit($id) {

        if (empty($id)) {
            return ["error" => "ID invalide"];
        }

        $produit = $this->produitRepo->findById($id);

        if (!$produit) {
            return ["error" => "Produit introuvable"];
        }

        return ["data" => $produit];
    }

    
    public function getProduitsPasChers($maxPrice = 20) {
        $produits = $this->produitRepo->findAll();

        $result = array_filter($produits, function ($p) use ($maxPrice) {
            return $p["prix"] <= $maxPrice;
        });

        return array_values($result);
    }
}