#!/bin/bash

echo "🧪 === Exécution complète de tous les tests ==="
echo ""

# Fonction pour afficher les résultats
display_results() {
    if [ $1 -eq 0 ]; then
        echo "✅ $2 - RÉUSSI"
    else
        echo "❌ $2 - ÉCHEC"
        GLOBAL_ERROR=1
    fi
    echo ""
}

GLOBAL_ERROR=0

echo "Vérification de PHPUnit..."
if [ -f "./vendor/bin/phpunit" ]; then
    echo "✓ PHPUnit trouvé"
else
    echo "❌ PHPUnit non trouvé"
    exit 1
fi

echo ""
echo "📋 Plan d'exécution:"
echo "  1. Tests de base (vérification setup)"
echo "  2. Tests API (endpoints REST)"
echo "  3. Tests d'authentification (inscription/connexion/annonces)"
echo "  4. Tests d'intégration (scénarios complets)"
echo ""

# 1. Tests de base
echo "🔧 === 1. Tests de base ==="
./vendor/bin/phpunit --testsuite "Basic Tests" --verbose
display_results $? "Tests de base"

# 2. Tests API
echo "🌐 === 2. Tests API ==="
./vendor/bin/phpunit --testsuite "API Tests" --verbose
display_results $? "Tests API"

# 3. Tests d'authentification
echo "🔐 === 3. Tests d'authentification ==="
./vendor/bin/phpunit --testsuite "Authentication Tests" --verbose
display_results $? "Tests d'authentification"

# 4. Tests d'intégration
echo "🔄 === 4. Tests d'intégration ==="
./vendor/bin/phpunit --testsuite "Integration Tests" --verbose
display_results $? "Tests d'intégration"

# Résumé final
echo "📊 === RÉSUMÉ FINAL ==="
if [ $GLOBAL_ERROR -eq 0 ]; then
    echo "🎉 TOUS LES TESTS SONT RÉUSSIS !"
    echo "   Votre application fonctionne correctement :"
    echo "   ✓ Configuration de base"
    echo "   ✓ Endpoints API fonctionnels"
    echo "   ✓ Système d'authentification sécurisé"
    echo "   ✓ Scénarios utilisateur complets"
else
    echo "⚠️  CERTAINS TESTS ONT ÉCHOUÉ"
    echo "   Vérifiez les détails ci-dessus pour corriger les problèmes"
    exit 1
fi

echo ""
echo "=== Tests terminés avec succès ==="