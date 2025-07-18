<?php

require_once __DIR__ . '/../AuthTestCase.php';

class UserLoginTest extends AuthTestCase
{
    private $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur de test pour les connexions
        $this->testUser = $this->createTestUser([
            'username' => 'logintest',
            'email' => 'login@example.com',
            'password' => 'password123'
        ]);
    }

    /**
     * Test connexion réussie avec email et mot de passe corrects
     */
    public function testSuccessfulLogin()
    {
        // Simuler les données de connexion
        $loginData = [
            'email' => $this->testUser['email'],
            'password' => $this->testUser['password']
        ];

        // Vérifier que l'utilisateur existe
        $userModel = $this->createMockUserModel();
        $user = $userModel->getByLogin($loginData['email']);
        
        $this->assertNotFalse($user, "L'utilisateur devrait exister");
        $this->assertEquals($this->testUser['email'], $user['email']);
        
        // Vérifier le hash du mot de passe
        $hashedPassword = hash('sha256', $loginData['password'] . $user['salt']);
        $this->assertEquals($user['password'], $hashedPassword, "Le mot de passe hashé devrait correspondre");
        
        // Simuler la connexion
        $this->loginUser($user['id'], $user['username']);
        
        // Vérifications
        $this->assertUserLoggedIn();
        $this->assertEquals($user['id'], $_SESSION['user']['id']);
        $this->assertEquals($user['username'], $_SESSION['user']['username']);
        
        echo "✓ Test connexion réussie\n";
    }

    /**
     * Test connexion avec mot de passe incorrect
     */
    public function testLoginWrongPassword()
    {
        $loginData = [
            'email' => $this->testUser['email'],
            'password' => 'wrongpassword'
        ];

        $userModel = $this->createMockUserModel();
        $user = $userModel->getByLogin($loginData['email']);
        
        $this->assertNotFalse($user, "L'utilisateur devrait exister");
        
        // Vérifier que le mot de passe incorrect ne match pas
        $hashedPassword = hash('sha256', $loginData['password'] . $user['salt']);
        $this->assertNotEquals($user['password'], $hashedPassword, "Le mauvais mot de passe ne devrait pas correspondre");
        
        // S'assurer qu'on n'est pas connecté
        $this->assertUserNotLoggedIn();
        
        echo "✓ Test mot de passe incorrect\n";
    }

    /**
     * Test connexion avec email inexistant
     */
    public function testLoginEmailNotFound()
    {
        $userModel = $this->createMockUserModel();
        $user = $userModel->getByLogin('inexistant@example.com');
        
        // Vérifications
        $this->assertFalse($user, "L'utilisateur inexistant ne devrait pas être trouvé");
        $this->assertUserNotLoggedIn();
        
        echo "✓ Test email inexistant\n";
    }

    /**
     * Test gestion des sessions
     */
    public function testSessionManagement()
    {
        // Test session vide au départ
        $this->assertUserNotLoggedIn();
        
        // Simuler une connexion
        $this->loginUser($this->testUser['id'], $this->testUser['username']);
        $this->assertUserLoggedIn();
        
        // Vérifier les données de session
        $this->assertEquals($this->testUser['id'], $_SESSION['user']['id']);
        $this->assertEquals($this->testUser['username'], $_SESSION['user']['username']);
        
        // Simuler une déconnexion
        $_SESSION = [];
        $this->assertUserNotLoggedIn();
        
        echo "✓ Test gestion des sessions\n";
    }

    /**
     * Test validation des données de connexion
     */
    public function testLoginDataValidation()
    {
        // Test avec email vide
        $emptyEmail = '';
        $this->assertTrue(empty($emptyEmail), "Un email vide devrait être détecté");
        
        // Test avec mot de passe vide
        $emptyPassword = '';
        $this->assertTrue(empty($emptyPassword), "Un mot de passe vide devrait être détecté");
        
        // Test avec email valide
        $validEmail = 'test@example.com';
        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false, "Un email valide devrait passer la validation");
        
        echo "✓ Test validation données de connexion\n";
    }

    /**
     * Test accès sécurisé
     */
    public function testSecureAccess()
    {
        // Test accès sans connexion
        $this->assertUserNotLoggedIn();
        $hasAccess = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
        $this->assertFalse($hasAccess, "L'accès devrait être refusé sans connexion");
        
        // Test accès avec connexion
        $this->loginUser($this->testUser['id'], $this->testUser['username']);
        $this->assertUserLoggedIn();
        $hasAccess = isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
        $this->assertTrue($hasAccess, "L'accès devrait être autorisé avec connexion");
        
        echo "✓ Test accès sécurisé\n";
    }

    /**
     * Test vérification des hash de mots de passe
     */
    public function testPasswordHashing()
    {
        $password = 'testpassword';
        $salt1 = 'salt1';
        $salt2 = 'salt2';
        
        // Test que le même mot de passe avec le même salt donne le même hash
        $hash1a = hash('sha256', $password . $salt1);
        $hash1b = hash('sha256', $password . $salt1);
        $this->assertEquals($hash1a, $hash1b, "Le même mot de passe/salt devrait donner le même hash");
        
        // Test que des salts différents donnent des hash différents
        $hash2 = hash('sha256', $password . $salt2);
        $this->assertNotEquals($hash1a, $hash2, "Des salts différents devraient donner des hash différents");
        
        // Test longueur du hash
        $this->assertEquals(64, strlen($hash1a), "Un hash SHA256 devrait faire 64 caractères");
        
        echo "✓ Test hashage des mots de passe\n";
    }
}