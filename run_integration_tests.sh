#!/bin/bash

echo "🔄 === Tests d'intégration seulement ==="

echo "Vérification de PHPUnit..."
if [ -f "./vendor/bin/phpunit" ]; then
    echo "✓ PHPUnit trouvé"
else
    echo "❌ PHPUnit non trouvé"
    exit 1
fi

echo ""
echo "=== Exécution des tests d'intégration ==="
./vendor/bin/phpunit --testsuite "Integration Tests" --verbose

echo ""
echo "=== Tests d'intégration terminés ==="