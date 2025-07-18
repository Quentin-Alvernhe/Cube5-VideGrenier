# 🧪 Tests de l'application Vide Grenier

Ce dossier contient tous les tests automatisés de l'application.

## 📋 Types de tests

### 1. **Tests de base** (`FirstTest.php`)
- Vérification que PHPUnit fonctionne
- Test de chargement des classes
- Test de base des utilitaires (Hash)

### 2. **Tests API** (`tests/API/`)
- **CitiesApiTest** : Test de l'autocomplétion des villes
- **ProductsApiTest** : Test de l'API des produits (tri, filtres)
- **SearchApiTest** : Test de la recherche d'articles

### 3. **Tests d'authentification** (`tests/Auth/`)
- **UserRegistrationTest** : Inscription utilisateur (succès, erreurs)
- **UserLoginTest** : Connexion/déconnexion, gestion sessions
- **ProductCreationTest** : Création d'annonces, validation, sécurité

### 4. **Tests d'intégration** (`tests/Integration/`)
- **UserWorkflowTest** : Scénarios complets utilisateur
- Tests de sécurité inter-utilisateurs
- Tests de performance

## 🚀 Comment exécuter les tests

### Tous les tests (recommandé)
```bash
./run_tests.sh
```
ou
```bash
./run_all_tests.sh
```

### Tests spécifiques

#### Tests API uniquement
```bash
./run_api_tests.sh
```

#### Tests d'authentification uniquement
```bash
./run_auth_tests.sh
```

#### Tests d'intégration uniquement
```bash
./run_integration_tests.sh
```

#### Test spécifique par classe
```bash
./vendor/bin/phpunit tests/API/CitiesApiTest.php
```

#### Test spécifique par méthode
```bash
./vendor/bin/phpunit --filter testSuccessfulLogin tests/Auth/UserLoginTest.php
```

## 🏗️ Structure des tests

```
tests/
├── bootstrap.php           # Configuration de base
├── ApiTestCase.php         # Classe de base pour tests API
├── AuthTestCase.php        # Classe de base pour tests auth
├── FirstTest.php           # Tests de vérification
├── API/                    # Tests des endpoints
│   ├── CitiesApiTest.php
│   ├── ProductsApiTest.php
│   └── SearchApiTest.php
├── Auth/                   # Tests d'authentification
│   ├── UserRegistrationTest.php
│   ├── UserLoginTest.php
│   └── ProductCreationTest.php
└── Integration/            # Tests de scénarios complets
    └── UserWorkflowTest.php
```

## 📊 Couverture des tests

Les tests couvrent :

✅ **API Endpoints**
- `/api/cities` - Autocomplétion villes
- `/api/products` - Liste des produits avec tri
- `/api/search` - Recherche d'articles

✅ **Authentification**
- Inscription utilisateur (validation, erreurs)
- Connexion/déconnexion
- Gestion des sessions
- Fonction "Se souvenir de moi"

✅ **Gestion des annonces**
- Création d'annonces
- Validation des données
- Upload d'images (validation format/taille)
- Suppression sécurisée
- Listing des annonces utilisateur

✅ **Sécurité**
- Protection des actions nécessitant une connexion
- Isolation entre utilisateurs
- Validation des permissions

✅ **Scénarios complets**
- Workflow utilisateur complet
- Tests de performance
- Tests d'intégration multi-fonctionnalités

## 🔧 Configuration

### Base de données de test
Les tests utilisent SQLite en mémoire pour :
- ⚡ Rapidité d'exécution
- 🔒 Isolation complète
- 🧹 Pas de pollution de la vraie base

### Environnement
Les tests fonctionnent dans l'environnement Docker configuré avec :
- PHP 7.4 + extensions
- Composer + PHPUnit
- Toutes les dépendances installées

## 🐛 Debug des tests

### Affichage détaillé
```bash
./vendor/bin/phpunit --verbose
```

### Test d'une classe spécifique avec debug
```bash
./vendor/bin/phpunit --verbose tests/Auth/UserLoginTest.php
```

### Afficher la couverture
```bash
./vendor/bin/phpunit --coverage-text
```

## 📈 Métriques

Les tests vérifient :
- ✅ Fonctionnement correct des features
- 🔒 Respect des règles de sécurité  
- 🚀 Performance acceptable
- 🛡️ Validation des données
- 🧪 Gestion des cas d'erreur

## 🔄 CI/CD

Les tests s'exécutent automatiquement dans GitLab CI à chaque push sur :
- Branche `develop`
- Branche `release/*`
- Branche `main`

Le pipeline échoue si des tests ne passent pas, garantissant la qualité du code déployé.