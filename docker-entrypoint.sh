#!/bin/bash
set -e

echo "Running Docker Entrypoint..."

# Inject env vars into Apache
printenv | grep DB_ >> /etc/apache2/envvars

exec "$@"
