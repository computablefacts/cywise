#!/usr/bin/env bash

set -euo pipefail

docker compose down

echo -e "\nStack arrêtée.\nVous pouvez la redémarrer avec :"
echo -e "  ./stack-start.sh"
echo -e "Ou vous pouvez tout supprimer avec :"
echo -e "  ./stack-destroy.sh"
