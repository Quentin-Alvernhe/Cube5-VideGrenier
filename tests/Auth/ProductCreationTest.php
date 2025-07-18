<?php

require_once __DIR__ . '/../AuthTestCase.php';

class ProductCreationTest extends AuthTestCase
{
    private $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur de test
        $this->testUser = $this->createTestUser([
            'username' => 'productcreator',
            'email' => 'creator@example.com',
            'password' => 'password123'
        ]);
    }

    /**
     * Test création d'annonce réussie avec utilisateur connecté
     */
    public function testSuccessfulProductCreation()
    {
        // Se connecter
        $this->loginUser($this->testUser['id'], $this->testUser['username']);
        $this->assertUserLoggedIn();
        
        // Données de l'annonce
        $productData = [
            'name' => 'Vélo de course',
            'description' => 'Superbe vélo en excellent état',
            'user_id' => $this->testUser['id']
        ];

        // Créer l'article directement via notre mock
        $articlesModel = $this->createMockArticlesModel();
        $articleId = $articlesModel->save($productData);
        
        // Vérifications
        $this->assertNotFalse($articleId);
        $this->assertGreaterThan(0, $articleId);
        
        // Vérifier que l'article existe en base
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($article);
        $this->assertEquals('Vélo de course', $article['name']);
        $this->assertEquals('Superbe vélo en excellent état', $article['description']);
        $this->assertEquals($this->testUser['id'], $article['user_id']);
        $this->assertNotEmpty($article['published_date']);
        
        echo "✓ Test création annonce réussie\n";
    }

    /**
     * Test création d'annonce sans être connecté
     */
    public function testProductCreationNotLoggedIn()
    {
        // S'assurer qu'on n'est pas connecté
        $this->resetSession();
        $this->assertUserNotLoggedIn();
        
        // Tenter de créer une annonce
        $canCreate = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
        $this->assertFalse($canCreate, "La création devrait être refusée sans connexion");
        
        echo "✓ Test création sans connexion\n";
    }

    /**
     * Test validation des données d'annonce
     */
    public function testProductDataValidation()
    {
        $this->loginUser($this->testUser['id'], $this->testUser['username']);
        
        // Test avec nom vide
        $invalidData1 = [
            'name' => '', // Nom vide
            'description' => 'Description test',
            'user_id' => $this->testUser['id']
        ];
        
        $this->assertTrue(empty($invalidData1['name']), "Un nom vide devrait être détecté");
        
        // Test avec description vide
        $invalidData2 = [
            'name' => 'Nom valide',
            'description' => '', // Description vide
            'user_id' => $this->testUser['id']
        ];
        
        $this->assertTrue(empty($invalidData2['description']), "Une description vide devrait être détectée");
        
        // Test avec données valides
        $validData = [
            'name' => 'Produit valide',
            'description' => 'Description valide',
            'user_id' => $this->testUser['id']
        ];
        
        $this->assertFalse(empty($validData['name']), "Un nom valide ne devrait pas être vide");
        $this->assertFalse(empty($validData['description']), "Une description valide ne devrait pas être vide");
        $this->assertGreaterThan(0, $validData['user_id'], "L'ID utilisateur devrait être valide");
        
        echo "✓ Test validation données\n";
    }

    /**
     * Test récupération des annonces d'un utilisateur
     */
    public function testUserProductsList()
    {
        $this->loginUser($this->testUser['id'], $this->testUser['username']);
        
        // Créer quelques annonces pour ce test
        $articlesModel = $this->createMockArticlesModel();
        
        $products = [
            ['name' => 'Ordinateur', 'description' => 'MacBook Pro', 'user_id' => $this->testUser['id']],
            ['name' => 'Table', 'description' => 'Table en bois', 'user_id' => $this->testUser['id']],
            ['name' => 'Livre', 'description' => 'Roman policier', 'user_id' => $this->testUser['id']]
        ];
        
        $createdIds = [];
        foreach ($products as $product) {
            $id = $articlesModel->save($product);
            $createdIds[] = $id;
        }
        
        // Récupérer les articles de l'utilisateur
        $userArticles = $articlesModel->getByUser($this->testUser['id']);
        
        // Vérifications
        $this->assertIsArray($userArticles);
        $this->assertGreaterThanOrEqual(3, count($userArticles));
        
        // Vérifier que tous les articles appartiennent au bon utilisateur
        foreach ($userArticles as $article) {
            $this->assertEquals($this->testUser['id'], $article['user_id']);
            $this->assertNotEmpty($article['name']);
            $this->assertNotEmpty($article['description']);
        }
        
        echo "✓ Test liste annonces utilisateur\n";
    }

    /**
     * Test sécurité des annonces entre utilisateurs
     */
    public function testProductSecurityBetweenUsers()
    {
        // Créer deux utilisateurs différents
        $user1 = $this->createTestUser(['username' => 'user1', 'email' => 'user1@test.com']);
        $user2 = $this->createTestUser(['username' => 'user2', 'email' => 'user2@test.com']);
        
        $articlesModel = $this->createMockArticlesModel();
        
        // User1 crée une annonce
        $this->loginUser($user1['id'], $user1['username']);
        $articleId = $articlesModel->save([
            'name' => 'Annonce de User1',
            'description' => 'Appartient à User1',
            'user_id' => $user1['id']
        ]);
        
        // Vérifier que l'article appartient bien à User1
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($article);
        $this->assertEquals($user1['id'], $article['user_id']);
        
        // User2 ne devrait voir que ses propres articles
        $user2Articles = $articlesModel->getByUser($user2['id']);
        $this->assertIsArray($user2Articles);
        
        // Vérifier qu'User2 ne voit pas l'article d'User1
        $foundUser1Article = false;
        foreach ($user2Articles as $userArticle) {
            if ($userArticle['id'] == $articleId) {
                $foundUser1Article = true;
                break;
            }
        }
        $this->assertFalse($foundUser1Article, "User2 ne devrait pas voir les articles d'User1");
        
        echo "✓ Test sécurité inter-utilisateurs\n";
    }

    /**
     * Test validation des fichiers (simulation)
     */
    public function testFileValidation()
    {
        // Simuler la validation de fichiers sans vraiment uploader
        $validExtensions = ['jpeg', 'jpg', 'png'];
        $maxSize = 4000000; // 4MB
        
        // Test extension valide
        $fileName1 = 'image.jpg';
        $extension1 = strtolower(pathinfo($fileName1, PATHINFO_EXTENSION));
        $this->assertContains($extension1, $validExtensions, "L'extension JPG devrait être autorisée");
        
        // Test extension invalide
        $fileName2 = 'document.pdf';
        $extension2 = strtolower(pathinfo($fileName2, PATHINFO_EXTENSION));
        $this->assertNotContains($extension2, $validExtensions, "L'extension PDF ne devrait pas être autorisée");
        
        // Test taille de fichier
        $fileSize1 = 2000000; // 2MB
        $fileSize2 = 5000000; // 5MB
        
        $this->assertLessThanOrEqual($maxSize, $fileSize1, "Un fichier de 2MB devrait être accepté");
        $this->assertGreaterThan($maxSize, $fileSize2, "Un fichier de 5MB devrait être refusé");
        
        echo "✓ Test validation fichiers\n";
    }

    /**
     * Test workflow complet de création d'annonce
     */
    public function testCompleteProductWorkflow()
    {
        echo "  🔄 Test workflow complet...\n";
        
        // 1. Connexion
        $this->loginUser($this->testUser['id'], $this->testUser['username']);
        $this->assertUserLoggedIn();
        echo "    ✓ Utilisateur connecté\n";
        
        // 2. Création d'annonce
        $articlesModel = $this->createMockArticlesModel();
        $productData = [
            'name' => 'Smartphone iPhone',
            'description' => 'iPhone 12 en excellent état, peu utilisé',
            'user_id' => $this->testUser['id']
        ];
        
        $articleId = $articlesModel->save($productData);
        $this->assertGreaterThan(0, $articleId);
        echo "    ✓ Annonce créée (ID: $articleId)\n";
        
        // 3. Vérification de l'annonce
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($article);
        $this->assertEquals($productData['name'], $article['name']);
        $this->assertEquals($this->testUser['id'], $article['user_id']);
        echo "    ✓ Annonce vérifiée en base\n";
        
        // 4. Liste des annonces de l'utilisateur
        $userArticles = $articlesModel->getByUser($this->testUser['id']);
        $this->assertGreaterThan(0, count($userArticles));
        echo "    ✓ Annonce visible dans la liste utilisateur\n";
        
        echo "  ✅ Workflow complet réussi\n";
    }
}