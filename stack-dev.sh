#!/bin/bash

# Fichiers Docker Compose pour le développement
DEV_DOCKER_COMPOSE_FILES="-f docker-compose.yaml -f docker-compose.dev.yaml"

# Si aucun argument n'est passé
if [ $# -eq 0 ]; then
    # Définir les arguments personnalisés pour docker compose up
    export DOCKER_COMPOSE_ARGS="${DEV_DOCKER_COMPOSE_FILES} up --build --detach"
    source ./stack-start.sh

    echo -e "\nLe mode 'watch' va démarrer."
    echo -e "\nVous pouvez voir les logs en lançant dans un autre terminal :"
    echo -e "  ./stack-dev.sh logs --timestamps --follow"
    echo -e "\nOu vous pouvez executer une commande en lançant dans un autre terminal :"
    echo -e "  ./stack-dev.sh exec app php artisan"

    docker compose $DEV_DOCKER_COMPOSE_FILES watch --no-up
else
    # Exécuter docker compose avec les arguments passés
    docker compose $DEV_DOCKER_COMPOSE_FILES "$@"
fi
