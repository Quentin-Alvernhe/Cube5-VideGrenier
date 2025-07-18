<?php

// Chargement de l'autoloader Composer (déjà configuré dans votre Dockerfile)
require_once __DIR__ . '/../vendor/autoload.php';

// Inclusion des classes nécessaires pour les tests
require_once __DIR__ . '/../App/Utility/Hash.php';
require_once __DIR__ . '/../App/Models/Cities.php';
require_once __DIR__ . '/../App/Models/Articles.php';
require_once __DIR__ . '/../App/Models/User.php';
require_once __DIR__ . '/../App/Controllers/Api.php';
require_once __DIR__ . '/../App/Controllers/User.php';
require_once __DIR__ . '/../App/Controllers/Product.php';
require_once __DIR__ . '/../Core/Model.php';

// Configuration pour éviter les erreurs de session dans les tests
if (!defined('TESTING_ENV')) {
    define('TESTING_ENV', true);
}

// Variables globales pour simuler les sessions dans les tests
global $test_session;
$test_session = [];

echo "✓ Bootstrap des tests chargé\n";