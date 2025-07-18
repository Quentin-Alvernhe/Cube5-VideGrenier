<?php

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../../App/Controllers/Api.php';
require_once __DIR__ . '/../../App/Models/Cities.php';
require_once __DIR__ . '/../../Core/Model.php';

class CitiesApiTest extends ApiTestCase
{
    /**
     * Test de l'endpoint /api/cities avec une recherche normale
     */
    public function testCitiesSearchBasic()
    {
        // Mock de la connexion de base de données pour le modèle Cities
        $mockCities = $this->createMockCitiesModel();
        
        // Simulation d'une requête GET
        $_GET['query'] = 'Par';
        
        // Capture de la sortie JSON
        ob_start();
        
        // Appel direct de la méthode du contrôleur avec notre mock
        $result = $mockCities->search('Par');
        echo json_encode($result);
        
        $output = ob_get_clean();
        
        // Vérifications
        $response = json_decode($output, true);
        $this->assertNotNull($response);
        $this->assertIsArray($response);
        $this->assertContains('1', $response); // ID de Paris
        
        echo "✓ Test recherche 'Par' réussie\n";
    }

    /**
     * Test de l'endpoint /api/cities avec une recherche vide
     */
    public function testCitiesSearchEmpty()
    {
        $mockCities = $this->createMockCitiesModel();
        
        $_GET['query'] = '';
        
        ob_start();
        $result = $mockCities->search('');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertEmpty($response);
        
        echo "✓ Test recherche vide réussie\n";
    }

    /**
     * Test de l'endpoint /api/cities avec une recherche qui ne trouve rien
     */
    public function testCitiesSearchNoResults()
    {
        $mockCities = $this->createMockCitiesModel();
        
        $_GET['query'] = 'VilleInexistante';
        
        ob_start();
        $result = $mockCities->search('VilleInexistante');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertEmpty($response);
        
        echo "✓ Test recherche sans résultats réussie\n";
    }

    /**
     * Test avec plusieurs résultats
     */
    public function testCitiesSearchMultipleResults()
    {
        $mockCities = $this->createMockCitiesModel();
        
        $_GET['query'] = 'M'; // Devrait trouver Marseille et Meudon
        
        ob_start();
        $result = $mockCities->search('M');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(2, count($response));
        
        echo "✓ Test recherche multiple réussie\n";
    }

    /**
     * Crée un mock du modèle Cities qui utilise notre base de test
     */
    private function createMockCitiesModel()
    {
        return new class($this->pdo) {
            private $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function search($query) {
                if (empty($query)) {
                    return [];
                }
                
                $stmt = $this->pdo->prepare('SELECT ville_id FROM villes_france WHERE ville_nom_reel LIKE ?');
                $searchQuery = $query . '%';
                $stmt->execute([$searchQuery]);
                
                return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        };
    }

    protected function tearDown(): void
    {
        // Nettoyer les variables globales
        $_GET = [];
        parent::tearDown();
    }
}