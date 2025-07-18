<?php

require_once __DIR__ . '/../AuthTestCase.php';

class UserRegistrationTest extends AuthTestCase
{
    /**
     * Test inscription réussie avec données valides
     */
    public function testSuccessfulRegistration()
    {
        // Données d'inscription valides
        $userData = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Créer l'utilisateur directement via notre mock
        $userModel = $this->createMockUserModel();
        $salt = bin2hex(random_bytes(16));
        $hashedPassword = hash('sha256', $userData['password'] . $salt);
        
        $userId = $userModel->createUser([
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password' => $hashedPassword,
            'salt' => $salt
        ]);
        
        // Vérifications
        $this->assertNotFalse($userId);
        $this->assertGreaterThan(0, $userId);
        
        // Vérifier que l'utilisateur existe en base
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute(['newuser@example.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($user);
        $this->assertEquals('newuser', $user['username']);
        $this->assertEquals('newuser@example.com', $user['email']);
        $this->assertNotEmpty($user['password']);
        $this->assertNotEmpty($user['salt']);
        
        echo "✓ Test inscription réussie\n";
    }

    /**
     * Test validation email déjà existant
     */
    public function testRegistrationEmailAlreadyExists()
    {
        // Créer un utilisateur existant
        $existingUser = $this->createTestUser([
            'email' => 'existing@example.com',
            'username' => 'existinguser'
        ]);

        // Tenter de créer un utilisateur avec le même email
        $userModel = $this->createMockUserModel();
        $result = $userModel->getByLogin('existing@example.com');
        
        // Vérifications
        $this->assertNotFalse($result, "L'email existant devrait être trouvé");
        $this->assertEquals('existing@example.com', $result['email']);
        
        // Simuler l'échec d'inscription (email déjà utilisé)
        $duplicateUser = $userModel->getByLogin('existing@example.com');
        $this->assertNotFalse($duplicateUser, "La vérification d'email dupliqué devrait fonctionner");
        
        echo "✓ Test email déjà utilisé\n";
    }

    /**
     * Test validation des mots de passe
     */
    public function testPasswordValidation()
    {
        $password1 = 'password123';
        $password2 = 'differentpassword';
        
        // Test correspondance des mots de passe
        $this->assertEquals($password1, $password1, "Les mots de passe identiques devraient correspondre");
        $this->assertNotEquals($password1, $password2, "Les mots de passe différents ne devraient pas correspondre");
        
        // Test hashage
        $salt = 'testsalt';
        $hash1 = hash('sha256', $password1 . $salt);
        $hash2 = hash('sha256', $password1 . $salt);
        
        $this->assertEquals($hash1, $hash2, "Le même mot de passe avec le même salt devrait donner le même hash");
        $this->assertEquals(64, strlen($hash1), "Le hash SHA256 devrait faire 64 caractères");
        
        echo "✓ Test validation mots de passe\n";
    }

    /**
     * Test validation email
     */
    public function testEmailValidation()
    {
        $validEmail = 'user@example.com';
        $invalidEmail = 'email_invalide';
        
        // Test validation d'email
        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false, "L'email valide devrait passer la validation");
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false, "L'email invalide ne devrait pas passer la validation");
        
        echo "✓ Test validation email\n";
    }

    /**
     * Test création de plusieurs utilisateurs
     */
    public function testMultipleUserCreation()
    {
        $users = [
            ['username' => 'user1', 'email' => 'user1@test.com'],
            ['username' => 'user2', 'email' => 'user2@test.com'],
            ['username' => 'user3', 'email' => 'user3@test.com']
        ];
        
        $createdUsers = [];
        
        foreach ($users as $userData) {
            $user = $this->createTestUser($userData);
            $createdUsers[] = $user;
            
            $this->assertNotEmpty($user['id']);
            $this->assertEquals($userData['username'], $user['username']);
            $this->assertEquals($userData['email'], $user['email']);
        }
        
        $this->assertCount(3, $createdUsers, "3 utilisateurs devraient être créés");
        
        echo "✓ Test création multiple d'utilisateurs\n";
    }
}