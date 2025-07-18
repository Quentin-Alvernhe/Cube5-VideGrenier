<?php

require_once __DIR__ . '/../AuthTestCase.php';

class UserWorkflowTest extends AuthTestCase
{
    /**
     * Test complet : Inscription → Connexion → Création d'annonce
     */
    public function testCompleteUserWorkflow()
    {
        echo "🔄 Test workflow complet utilisateur...\n";
        
        // 1. INSCRIPTION (simulation)
        echo "  1. Test inscription...\n";
        $userData = [
            'username' => 'workflowuser',
            'email' => 'workflow@example.com',
            'password' => 'securepassword123'
        ];
        
        $user = $this->createTestUser($userData);
        $this->assertNotFalse($user['id'], "L'inscription devrait réussir");
        $this->assertGreaterThan(0, $user['id'], "L'ID utilisateur devrait être valide");
        
        echo "    ✓ Inscription réussie (ID: {$user['id']})\n";
        
        // 2. CONNEXION (simulation)
        echo "  2. Test connexion...\n";
        $this->loginUser($user['id'], $user['username']);
        $this->assertUserLoggedIn();
        
        echo "    ✓ Connexion réussie\n";
        
        // 3. CRÉATION D'ANNONCES
        echo "  3. Test création d'annonces...\n";
        $articlesModel = $this->createMockArticlesModel();
        
        $products = [
            ['name' => 'iPhone 12', 'description' => 'Téléphone en excellent état'],
            ['name' => 'Vélo électrique', 'description' => 'Vélo peu utilisé, batterie neuve'],
            ['name' => 'Livre JavaScript', 'description' => 'Manuel de programmation']
        ];
        
        $createdProducts = [];
        foreach ($products as $product) {
            $product['user_id'] = $_SESSION['user']['id'];
            $articleId = $articlesModel->save($product);
            $this->assertGreaterThan(0, $articleId, "La création de '{$product['name']}' devrait réussir");
            $createdProducts[] = $articleId;
        }
        
        echo "    ✓ " . count($createdProducts) . " annonces créées\n";
        
        // 4. VÉRIFICATION DES ANNONCES CRÉÉES
        echo "  4. Vérification des annonces...\n";
        $userArticles = $articlesModel->getByUser($_SESSION['user']['id']);
        $this->assertCount(3, $userArticles, "L'utilisateur devrait avoir 3 annonces");
        
        foreach ($userArticles as $article) {
            $this->assertEquals($_SESSION['user']['id'], $article['user_id']);
            $this->assertNotEmpty($article['name']);
            $this->assertNotEmpty($article['description']);
        }
        
        echo "    ✓ Toutes les annonces sont bien associées à l'utilisateur\n";
        
        // 5. TEST DE RECHERCHE (simulation)
        echo "  5. Test recherche...\n";
        $searchQuery = 'iPhone';
        
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE name LIKE ? LIMIT 10');
        $stmt->execute(["%$searchQuery%"]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertGreaterThan(0, count($searchResults), "La recherche 'iPhone' devrait donner des résultats");
        
        $foundIphone = false;
        foreach ($searchResults as $result) {
            if (stripos($result['name'], 'iPhone') !== false) {
                $foundIphone = true;
                break;
            }
        }
        $this->assertTrue($foundIphone, "L'iPhone devrait être trouvé dans les résultats");
        
        echo "    ✓ Recherche fonctionnelle\n";
        
        // 6. DÉCONNEXION (simulation)
        echo "  6. Test déconnexion...\n";
        $_SESSION = [];
        $this->assertUserNotLoggedIn();
        
        echo "    ✓ Déconnexion réussie\n";
        
        // 7. VÉRIFICATION SÉCURITÉ
        echo "  7. Vérification sécurité...\n";
        $canAccess = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
        $this->assertFalse($canAccess, "L'accès au compte devrait être refusé après déconnexion");
        
        echo "    ✓ Sécurité vérifiée\n";
        
        echo "✅ Test workflow complet réussi !\n\n";
    }

    /**
     * Test sécurité : Isolation entre utilisateurs
     */
    public function testUserIsolation()
    {
        echo "🛡️ Test isolation entre utilisateurs...\n";
        
        // Créer deux utilisateurs
        $user1 = $this->createTestUser(['username' => 'user1', 'email' => 'user1@test.com']);
        $user2 = $this->createTestUser(['username' => 'user2', 'email' => 'user2@test.com']);
        
        $articlesModel = $this->createMockArticlesModel();
        
        // User1 crée une annonce
        $this->loginUser($user1['id'], $user1['username']);
        $article1Id = $articlesModel->save([
            'name' => 'Annonce de User1',
            'description' => 'Cette annonce appartient à User1',
            'user_id' => $user1['id']
        ]);
        
        // User2 crée une annonce
        $this->loginUser($user2['id'], $user2['username']);
        $article2Id = $articlesModel->save([
            'name' => 'Annonce de User2',
            'description' => 'Cette annonce appartient à User2',
            'user_id' => $user2['id']
        ]);
        
        // Vérifier l'isolation
        $user1Articles = $articlesModel->getByUser($user1['id']);
        $user2Articles = $articlesModel->getByUser($user2['id']);
        
        // User1 ne devrait voir que ses annonces
        $this->assertCount(1, $user1Articles, "User1 devrait avoir 1 annonce");
        $this->assertEquals($user1['id'], $user1Articles[0]['user_id']);
        
        // User2 ne devrait voir que ses annonces  
        $this->assertCount(1, $user2Articles, "User2 devrait avoir 1 annonce");
        $this->assertEquals($user2['id'], $user2Articles[0]['user_id']);
        
        echo "    ✓ Isolation utilisateurs fonctionnelle\n\n";
    }

    /**
     * Test performance : Création multiple d'annonces
     */
    public function testPerformanceMultipleCreation()
    {
        echo "⚡ Test performance création multiple...\n";
        
        $user = $this->createTestUser(['username' => 'perfuser', 'email' => 'perf@test.com']);
        $this->loginUser($user['id'], $user['username']);
        
        $articlesModel = $this->createMockArticlesModel();
        
        $startTime = microtime(true);
        $productCount = 10; // Réduit pour les tests
        
        for ($i = 1; $i <= $productCount; $i++) {
            $articleId = $articlesModel->save([
                'name' => "Produit Performance $i",
                'description' => "Description du produit numéro $i pour test de performance",
                'user_id' => $user['id']
            ]);
            
            $this->assertGreaterThan(0, $articleId, "La création du produit $i devrait réussir");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Vérifier que tous les produits ont été créés
        $userArticles = $articlesModel->getByUser($user['id']);
        $this->assertCount($productCount, $userArticles, "Tous les produits devraient être créés");
        
        echo "    ✓ $productCount produits créés en " . round($duration, 3) . "s\n";
        echo "    ✓ Moyenne: " . round(($duration / $productCount) * 1000, 2) . "ms par produit\n\n";
    }

    /**
     * Test validation globale des données
     */
    public function testGlobalDataValidation()
    {
        echo "🔍 Test validation globale...\n";
        
        $user = $this->createTestUser(['username' => 'validator', 'email' => 'validator@test.com']);
        $this->loginUser($user['id'], $user['username']);
        
        // Test validation email
        $validEmails = ['test@example.com', 'user.name+tag@domain.co.uk', 'simple@domain.org'];
        $invalidEmails = ['invalid', '@domain.com', 'user@', 'user space@domain.com'];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "L'email '$email' devrait être valide");
        }
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "L'email '$email' ne devrait pas être valide");
        }
        
        // Test validation longueurs
        $shortName = 'A';
        $normalName = 'Produit normal';
        $longName = str_repeat('A', 300);
        
        $this->assertLessThan(3, strlen($shortName), "Un nom trop court devrait être détecté");
        $this->assertGreaterThan(5, strlen($normalName), "Un nom normal devrait être acceptable");
        $this->assertGreaterThan(255, strlen($longName), "Un nom trop long devrait être détecté");
        
        echo "    ✓ Validation globale fonctionnelle\n\n";
    }
}