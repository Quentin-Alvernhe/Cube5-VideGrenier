#!/bin/bash

echo "=== Tests API seulement ==="

echo "Vérification de PHPUnit..."
if [ -f "./vendor/bin/phpunit" ]; then
    echo "✓ PHPUnit trouvé"
else
    echo "❌ PHPUnit non trouvé"
    exit 1
fi

echo "=== Exécution des tests API ==="
./vendor/bin/phpunit --testsuite "API Tests" --verbose

echo "=== Tests API terminés ==="