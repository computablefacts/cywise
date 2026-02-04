#!/usr/bin/env bash

set -euo pipefail

docker compose down --rmi all --volumes
sudo rm -Rf ./data
rm .env

echo -e "\nStack détruite.\nVous pouvez en démarrer une nouvelle avec :"
echo -e "  ./stack-start.sh"
