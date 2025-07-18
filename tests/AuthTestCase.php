<?php

require_once __DIR__ . '/ApiTestCase.php';

class AuthTestCase extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // ❌ SUPPRIMÉ : session_start() qui causait l'erreur
        // ✅ À la place : simulation des sessions avec variables globales
        
        // Simuler une session propre
        $this->resetSession();
        
        // Nettoyer les variables globales
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_COOKIE = [];
        
        // Pas besoin de gérer les headers dans les tests
    }

    /**
     * Simule une session propre
     */
    protected function resetSession()
    {
        global $test_session;
        $test_session = [];
        
        // Override de $_SESSION pour les tests
        $_SESSION = &$test_session;
    }

    /**
     * Crée un utilisateur de test dans la base
     */
    protected function createTestUser($userData = [])
    {
        $defaultData = [
            'username' => 'testuser_' . uniqid(),
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'password123',
            'salt' => 'test_salt_' . uniqid()
        ];
        
        $userData = array_merge($defaultData, $userData);
        
        // Hash du mot de passe comme dans l'application
        $hashedPassword = hash('sha256', $userData['password'] . $userData['salt']);
        
        $stmt = $this->pdo->prepare('
            INSERT INTO users (username, email, password, salt) 
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $userData['username'],
            $userData['email'], 
            $hashedPassword,
            $userData['salt']
        ]);
        
        $userData['id'] = $this->pdo->lastInsertId();
        $userData['hashed_password'] = $hashedPassword;
        
        return $userData;
    }

    /**
     * Simule une connexion utilisateur
     */
    protected function loginUser($userId, $username = 'testuser')
    {
        $_SESSION['user'] = [
            'id' => $userId,
            'username' => $username
        ];
    }

    /**
     * Vérifie qu'un utilisateur est connecté
     */
    protected function assertUserLoggedIn()
    {
        $this->assertArrayHasKey('user', $_SESSION, "L'utilisateur devrait être connecté (\$_SESSION['user'] doit exister)");
        $this->assertArrayHasKey('id', $_SESSION['user'], "L'ID utilisateur doit être défini");
        $this->assertArrayHasKey('username', $_SESSION['user'], "Le nom d'utilisateur doit être défini");
        $this->assertNotEmpty($_SESSION['user']['id'], "L'ID utilisateur ne doit pas être vide");
    }

    /**
     * Vérifie qu'un utilisateur n'est pas connecté
     */
    protected function assertUserNotLoggedIn()
    {
        $this->assertTrue(
            !isset($_SESSION['user']) || empty($_SESSION['user']), 
            "L'utilisateur ne devrait pas être connecté"
        );
    }

    /**
     * Mock du modèle User pour les tests
     */
    protected function createMockUserModel()
    {
        return new class($this->pdo) {
            private $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function createUser($data) {
                $stmt = $this->pdo->prepare('INSERT INTO users(username, email, password, salt) VALUES (?, ?, ?, ?)');
                $stmt->execute([$data['username'], $data['email'], $data['password'], $data['salt']]);
                return $this->pdo->lastInsertId();
            }
            
            public function getByLogin($email) {
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            public function getByRememberToken($token) {
                // Pour ce test, on simule qu'aucun token n'existe
                return false;
            }
        };
    }

    /**
     * Mock du modèle Articles pour les tests
     */
    protected function createMockArticlesModel()
    {
        return new class($this->pdo) {
            private $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function save($data) {
                $stmt = $this->pdo->prepare('INSERT INTO articles(name, description, user_id, published_date) VALUES (?, ?, ?, ?)');
                $publishedDate = date('Y-m-d');
                $stmt->execute([$data['name'], $data['description'], $data['user_id'], $publishedDate]);
                return $this->pdo->lastInsertId();
            }
            
            public function attachPicture($articleId, $pictureName) {
                $stmt = $this->pdo->prepare('UPDATE articles SET picture = ? WHERE id = ?');
                $stmt->execute([$pictureName, $articleId]);
            }
            
            public function getByUser($userId) {
                $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE user_id = ?');
                $stmt->execute([$userId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        };
    }

    /**
     * Helper pour capturer la sortie (au lieu des headers)
     */
    protected function captureOutput($callback)
    {
        ob_start();
        
        try {
            $result = $callback();
            $output = ob_get_clean();
            return ['result' => $result, 'output' => $output];
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer les variables globales
        $this->resetSession();
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_COOKIE = [];
        
        parent::tearDown();
    }
}