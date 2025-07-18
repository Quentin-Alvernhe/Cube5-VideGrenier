#!/bin/bash

echo "🔐 === Tests d'authentification seulement ==="

echo "Vérification de PHPUnit..."
if [ -f "./vendor/bin/phpunit" ]; then
    echo "✓ PHPUnit trouvé"
else
    echo "❌ PHPUnit non trouvé"
    exit 1
fi

echo ""
echo "=== Exécution des tests d'authentification ==="
./vendor/bin/phpunit --testsuite "Authentication Tests" --verbose

echo ""
echo "=== Tests d'authentification terminés ==="