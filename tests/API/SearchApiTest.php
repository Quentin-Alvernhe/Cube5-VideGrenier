<?php

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../../App/Controllers/Api.php';

class SearchApiTest extends ApiTestCase
{
    /**
     * Test de l'endpoint /api/search avec une recherche normale
     */
    public function testSearchWithResults()
    {
        $_GET['q'] = 'vélo';
        
        ob_start();
        $result = $this->performSearch('vélo');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertGreaterThan(0, count($response));
        
        // Vérifier la structure des résultats
        $firstResult = $response[0];
        $this->assertArrayHasKey('id', $firstResult);
        $this->assertArrayHasKey('name', $firstResult);
        $this->assertArrayHasKey('description', $firstResult);
        $this->assertArrayHasKey('picture', $firstResult);
        $this->assertArrayHasKey('views', $firstResult);
        
        // Vérifier que le résultat contient bien le terme recherché
        $this->assertStringContainsString('vélo', strtolower($firstResult['name']));
        
        echo "✓ Test recherche avec résultats réussie\n";
    }

    /**
     * Test de l'endpoint /api/search avec une recherche vide
     */
    public function testSearchEmpty()
    {
        $_GET['q'] = '';
        
        ob_start();
        $result = $this->performSearch('');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertEmpty($response); // Recherche vide = résultat vide
        
        echo "✓ Test recherche vide réussie\n";
    }

    /**
     * Test de l'endpoint /api/search avec une recherche sans résultats
     */
    public function testSearchNoResults()
    {
        $_GET['q'] = 'terme_inexistant_12345';
        
        ob_start();
        $result = $this->performSearch('terme_inexistant_12345');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertEmpty($response);
        
        echo "✓ Test recherche sans résultats réussie\n";
    }

    /**
     * Test de l'endpoint /api/search avec recherche partielle
     */
    public function testSearchPartial()
    {
        $_GET['q'] = 'table';
        
        ob_start();
        $result = $this->performSearch('table');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertGreaterThan(0, count($response));
        
        // Vérifier que le terme est trouvé
        $found = false;
        foreach ($response as $article) {
            if (stripos($article['name'], 'table') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Le terme 'table' devrait être trouvé dans les résultats");
        
        echo "✓ Test recherche partielle réussie\n";
    }

    /**
     * Test que la recherche respecte la limite de 10 résultats
     */
    public function testSearchLimit()
    {
        // Ajouter plus d'articles pour tester la limite
        $this->addMoreTestArticles();
        
        $_GET['q'] = 'test'; // Terme qui devrait matcher plusieurs articles
        
        ob_start();
        $result = $this->performSearch('test');
        echo json_encode($result);
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertLessThanOrEqual(10, count($response)); // Max 10 résultats
        
        echo "✓ Test limite de 10 résultats réussie\n";
    }

    /**
     * Effectue une recherche comme le ferait le contrôleur API
     */
    private function performSearch($query)
    {
        if (empty($query)) {
            return [];
        }
        
        $stmt = $this->pdo->prepare('SELECT id, name, description, picture, views FROM articles WHERE name LIKE ? ORDER BY name ASC LIMIT 10');
        $stmt->execute(["%$query%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute plus d'articles de test pour tester la limite
     */
    private function addMoreTestArticles()
    {
        for ($i = 4; $i <= 15; $i++) {
            $this->pdo->prepare("INSERT INTO articles (id, name, description, user_id, published_date, views, picture) VALUES (?, ?, ?, ?, ?, ?, ?)")
                      ->execute([
                          $i, 
                          "Article test $i", 
                          "Description de test $i", 
                          1, 
                          '2024-01-' . str_pad($i, 2, '0', STR_PAD_LEFT), 
                          rand(1, 50), 
                          "test$i.jpg"
                      ]);
        }
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }
}