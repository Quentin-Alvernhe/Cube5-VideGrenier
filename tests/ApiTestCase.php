<?php

use PHPUnit\Framework\TestCase;

class ApiTestCase extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration de la base de données de test en mémoire
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer les tables nécessaires pour les tests
        $this->createTestTables();
        $this->seedTestData();
        
        // Mock de la connexion dans vos modèles
        $this->mockDatabaseConnection();
    }

    protected function createTestTables()
    {
        // Table articles
        $this->pdo->exec("
            CREATE TABLE articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                user_id INTEGER,
                published_date DATE,
                views INTEGER DEFAULT 0,
                picture VARCHAR(255)
            )
        ");

        // Table villes_france
        $this->pdo->exec("
            CREATE TABLE villes_france (
                ville_id INTEGER PRIMARY KEY AUTOINCREMENT,
                ville_nom_reel VARCHAR(255) NOT NULL
            )
        ");

        // Table users
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255),
                salt VARCHAR(255)
            )
        ");
    }

    protected function seedTestData()
    {
        // Données de test pour les villes
        $cities = [
            ['ville_id' => 1, 'ville_nom_reel' => 'Paris'],
            ['ville_id' => 2, 'ville_nom_reel' => 'Lyon'],
            ['ville_id' => 3, 'ville_nom_reel' => 'Marseille'],
            ['ville_id' => 4, 'ville_nom_reel' => 'Lille'],
            ['ville_id' => 5, 'ville_nom_reel' => 'Meudon']
        ];

        foreach ($cities as $city) {
            $this->pdo->prepare("INSERT INTO villes_france (ville_id, ville_nom_reel) VALUES (?, ?)")
                      ->execute([$city['ville_id'], $city['ville_nom_reel']]);
        }

        // Données de test pour les utilisateurs
        $this->pdo->prepare("INSERT INTO users (id, username, email, password, salt) VALUES (?, ?, ?, ?, ?)")
                  ->execute([1, 'testuser', 'test@example.com', 'hashed_password', 'test_salt']);

        // Données de test pour les articles
        $articles = [
            [1, 'Vélo vintage', 'Superbe vélo des années 80', 1, '2024-01-01', 15, 'bike.jpg'],
            [2, 'Table en bois', 'Table de salon en chêne', 1, '2024-01-02', 8, 'table.jpg'],
            [3, 'Livre de cuisine', 'Recettes traditionnelles', 1, '2024-01-03', 22, 'book.jpg']
        ];

        foreach ($articles as $article) {
            $this->pdo->prepare("INSERT INTO articles (id, name, description, user_id, published_date, views, picture) VALUES (?, ?, ?, ?, ?, ?, ?)")
                      ->execute($article);
        }
    }

    protected function mockDatabaseConnection()
    {
        // Cette méthode sera utilisée pour injecter notre PDO de test dans les modèles
        // On va override la méthode getDB() dans nos tests
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }
}