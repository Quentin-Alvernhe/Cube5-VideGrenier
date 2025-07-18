#!/bin/bash

echo "=== Vérification de l'environnement ==="
php --version || { echo "❌ PHP non disponible"; exit 1; }

# Vérifier si composer est disponible
if command -v composer &> /dev/null; then
    echo "✓ Composer disponible: $(composer --version)"
else
    echo "⚠️ Composer non disponible dans PATH, mais dépendances déjà installées"
fi

echo "=== Vérification de PHPUnit ==="
if [ -f "./vendor/bin/phpunit" ]; then
    echo "✓ PHPUnit trouvé"
    chmod +x ./vendor/bin/phpunit
    
    # Vérifier que PHPUnit peut s'exécuter
    ./vendor/bin/phpunit --version || { echo "❌ PHPUnit ne peut pas s'exécuter"; exit 1; }
else
    echo "❌ PHPUnit non trouvé"
    echo "Contenu du dossier vendor/bin:"
    ls -la vendor/bin/ 2>/dev/null || echo "Dossier vendor/bin introuvable"
    exit 1
fi

echo "=== Vérification de la configuration des tests ==="
if [ -f "phpunit.xml" ]; then
    echo "✓ Configuration PHPUnit trouvée"
else
    echo "❌ Fichier phpunit.xml non trouvé"
    exit 1
fi

if [ -f "tests/bootstrap.php" ]; then
    echo "✓ Bootstrap des tests trouvé"
else
    echo "❌ Fichier tests/bootstrap.php non trouvé"
    exit 1
fi

# Donner les permissions aux scripts de test
chmod +x run_all_tests.sh 2>/dev/null
chmod +x run_api_tests.sh 2>/dev/null
chmod +x run_auth_tests.sh 2>/dev/null
chmod +x run_integration_tests.sh 2>/dev/null

echo ""
echo "🚀 Lancement de tous les tests..."
echo ""

# Exécuter tous les tests avec le script complet
./run_all_tests.sh

echo "=== Tests terminés ==="