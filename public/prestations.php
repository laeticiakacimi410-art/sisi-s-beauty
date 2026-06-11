<?php
session_start();
include 'header.php';
require_once "../src/config/database.php";

$db = new Database();
$pdo = $db->getConnection();


$prestations = $pdo->query("
    SELECT id, nom, description, duree, prix, categorie, type_prestation, image 
    FROM prestations 
    ORDER BY type_prestation, nom
")->fetchAll(PDO::FETCH_ASSOC);


$categories_front = [
    'coiffure' => 'Coiffure',
    'esthetique' => 'Esthétique',
    'manucure' => 'Manucure',
    'epilation' => 'Épilation',
    'massage' => 'Massage / Bien-être',
    'makeup' => 'Maquillage',
    'forfait' => 'Forfaits'
];
?>

<div class="prestations-page">
    <div class="container">
        
        <div class="prestations-header">
            <h1>Nos Prestations</h1>
            <p>Découvrez tous nos services de coiffure, maquillage et bien-être</p>
        </div>
        
        <?php
        
        $grouped = [];
        foreach ($prestations as $p) {
            $type = $p['type_prestation'] ?: 'autre';
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $p;
        }
        
        
        foreach ($grouped as $type_key => $prestations_cat):
            $categorie_nom = $categories_front[$type_key] ?? ucfirst($type_key);
        ?>
        
        <div class="categorie-section">
            <h2><?php echo $categorie_nom; ?></h2>
            <div class="prestations-grid">
                <?php foreach ($prestations_cat as $p): ?>
                <div class="prestation-card-catalogue">
                    <div class="prestation-image">
                        <?php if ($p['image'] && file_exists("../uploads/prestations/" . $p['image'])): ?>
                            <img src="/uploads/prestations/<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['nom']); ?>">
                        <?php else: ?>
                            <div class="prestation-icon">
                                <?php 
                                    $icons = [
                                        'coiffure' => '✂️',
                                        'esthetique' => '💆',
                                        'manucure' => '💅',
                                        'epilation' => '✨',
                                        'massage' => '🌸',
                                        'makeup' => '💄',
                                        'forfait' => '🎁'
                                    ];
                                    echo $icons[$type_key] ?? '✧';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="prestation-content">
                        <h3><?php echo htmlspecialchars($p['nom']); ?></h3>
                        <p class="prestation-description"><?php echo htmlspecialchars($p['description']); ?></p>
                        <div class="prestation-details">
                            <span class="prestation-duree">⏱ <?php echo $p['duree']; ?> min</span>
                            <span class="prestation-prix"><?php echo number_format($p['prix'], 2); ?> €</span>
                        </div>
                        <a href="/reservation.php?prestation=<?php echo $p['id']; ?>" class="btn-reserver-prestation">Réserver</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php endforeach; ?>
        
        <?php if (empty($prestations)): ?>
        <div class="empty-prestations">
            <p>Aucune prestation disponible pour le moment.</p>
            <p>Revenez bientôt !</p>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>

.prestations-page {
    min-height: calc(100vh - 80px);
    background: #FFFFFF;
    padding: 120px 0 80px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.prestations-header {
    text-align: center;
    margin-bottom: 60px;
}

.prestations-header h1 {
    font-size: 3rem;
    font-weight: 400;
    color: #1a1a1a;
    margin-bottom: 15px;
}

.prestations-header p {
    color: #888;
    font-family: 'Montserrat', sans-serif;
}

.categorie-section {
    margin-bottom: 60px;
}

.categorie-section h2 {
    font-size: 1.8rem;
    font-weight: 400;
    margin-bottom: 30px;
    padding-bottom: 10px;
    border-bottom: 1px solid #EBE5E2;
}

.prestations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
}

.prestation-card-catalogue {
    background: #FDF9F7;
    transition: all 0.3s;
    overflow: hidden;
    border-radius: 8px;
}

.prestation-card-catalogue:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.05);
}

.prestation-image {
    background: #f0e8e4;
    padding: 30px;
    text-align: center;
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prestation-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prestation-icon {
    font-size: 3rem;
    color: #c4a4a4;
}

.prestation-content {
    padding: 25px;
}

.prestation-content h3 {
    font-size: 1.3rem;
    margin-bottom: 12px;
    color: #1a1a1a;
}

.prestation-description {
    color: #666;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.85rem;
    margin-bottom: 15px;
    line-height: 1.6;
}

.prestation-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #EBE5E2;
}

.prestation-duree {
    color: #888;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.8rem;
}

.prestation-prix {
    color: #c4a4a4;
    font-family: 'Montserrat', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
}

.btn-reserver-prestation {
    display: inline-block;
    background: #1a1a1a;
    color: white;
    padding: 10px 25px;
    text-decoration: none;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    transition: all 0.3s;
    width: 100%;
    text-align: center;
    border: none;
    cursor: pointer;
    border-radius: 4px;
}

.btn-reserver-prestation:hover {
    background: #c4a4a4;
}

.empty-prestations {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

@media (max-width: 768px) {
    .prestations-page {
        padding: 100px 0 60px;
    }
    
    .prestations-header h1 {
        font-size: 2rem;
    }
    
    .prestations-grid {
        grid-template-columns: 1fr;
    }
    
    .categorie-section h2 {
        font-size: 1.5rem;
    }
}
</style>

<?php include 'footer.php'; ?>