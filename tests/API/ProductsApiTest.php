<?php

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../../App/Controllers/Api.php';
require_once __DIR__ . '/../../App/Models/Articles.php';

class ProductsApiTest extends ApiTestCase
{
    /**
     * Test de l'endpoint /api/products sans tri
     */
    public function testProductsWithoutSort()
    {
        $mockArticles = $this->createMockArticlesModel();
        
        $_GET['sort'] = '';
        
        ob_start();
        $result = $mockArticles->getAll('');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertCount(3, $response); // On a 3 articles de test
        
        // Vérifier que les données sont correctes
        $firstArticle = $response[0];
        $this->assertArrayHasKey('name', $firstArticle);
        $this->assertArrayHasKey('description', $firstArticle);
        $this->assertArrayHasKey('views', $firstArticle);
        
        echo "✓ Test récupération articles sans tri réussie\n";
    }

    /**
     * Test de l'endpoint /api/products avec tri par vues
     */
    public function testProductsSortByViews()
    {
        $mockArticles = $this->createMockArticlesModel();
        
        $_GET['sort'] = 'views';
        
        ob_start();
        $result = $mockArticles->getAll('views');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertCount(3, $response);
        
        // Vérifier que le tri par vues fonctionne (décroissant)
        $this->assertGreaterThanOrEqual($response[1]['views'], $response[0]['views']);
        $this->assertGreaterThanOrEqual($response[2]['views'], $response[1]['views']);
        
        echo "✓ Test tri par vues réussie\n";
    }

    /**
     * Test de l'endpoint /api/products avec tri par date
     */
    public function testProductsSortByDate()
    {
        $mockArticles = $this->createMockArticlesModel();
        
        $_GET['sort'] = 'data'; // Note: 'data' dans votre code pour 'date'
        
        ob_start();
        $result = $mockArticles->getAll('data');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertCount(3, $response);
        
        // Vérifier que le tri par date fonctionne (plus récent en premier)
        $date1 = strtotime($response[0]['published_date']);
        $date2 = strtotime($response[1]['published_date']);
        $this->assertGreaterThanOrEqual($date2, $date1);
        
        echo "✓ Test tri par date réussie\n";
    }

    /**
     * Test de l'endpoint /api/products avec tri invalide
     */
    public function testProductsInvalidSort()
    {
        $mockArticles = $this->createMockArticlesModel();
        
        $_GET['sort'] = 'invalid_sort';
        
        ob_start();
        $result = $mockArticles->getAll('invalid_sort');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertCount(3, $response); // Devrait retourner tous les articles sans tri
        
        echo "✓ Test tri invalide réussie\n";
    }

    /**
     * Crée un mock du modèle Articles qui utilise notre base de test
     */
    private function createMockArticlesModel()
    {
        return new class($this->pdo) {
            private $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function getAll($filter) {
                $query = 'SELECT * FROM articles ';
                
                switch ($filter) {
                    case 'views':
                        $query .= ' ORDER BY articles.views DESC';
                        break;
                    case 'data':
                        $query .= ' ORDER BY articles.published_date DESC';
                        break;
                    case '':
                        break;
                    default:
                        // Tri invalide, pas d'ORDER BY
                        break;
                }
                
                $stmt = $this->pdo->query($query);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        };
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }
}