#!/bin/bash

echo "=== REDÉMARRAGE DU GITLAB RUNNER ==="

echo "1. Arrêt du runner actuel..."
docker-compose -f docker-compose.gitlabe-runner.yml down

echo "2. Nettoyage des réseaux orphelins..."
docker network prune -f

echo "3. Démarrage du runner..."
docker-compose -f docker-compose.gitlabe-runner.yml up -d

echo "4. Attente du démarrage (30s)..."
sleep 30

echo "5. Vérification de l'état..."
docker ps | grep gitlab-runner

echo "6. Vérification des logs..."
docker logs $(docker ps | grep gitlab-runner | awk '{print $1}') --tail 20

echo "✅ Runner redémarré"