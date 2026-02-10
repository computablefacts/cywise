#!/bin/bash

# Définir les arguments personnalisés pour docker compose up
export DOCKER_COMPOSE_ARGS="-f docker-compose.yaml -f docker-compose.dev.yaml up --build --watch"

source ./stack-start.sh
