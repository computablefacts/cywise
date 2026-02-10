#!/usr/bin/env bash

set -euo pipefail

if [[ ! -f .env.example ]]; then
  echo "❌ Fichier .env.example introuvable."
  exit 1
fi

# Fonction pour remplacer ou ajouter une clé dans .env
set_env_value () {
  local key="$1"
  local value="$2"

  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

# Generate a "random" alpha-numeric string.
str_random () {
  local length="${1:-32}"

  openssl rand -base64 $((length * 2)) \
    | tr -dc 'A-Za-z0-9' \
    | head -c "$length"
}

if [[ ! -f .env ]]; then
  echo "📝 Création du fichier .env..."
  cp .env.example .env

  # Génération mot de passe
  PWD_SUFFIX="$(str_random 8)"

  set_env_value "APP_KEY" "base64:$(openssl rand -base64 32)"
  set_env_value "HASHER_NONCE" "$(str_random 64)"
  set_env_value "PERFORMA_SECRET_KEY" "$(str_random 64)"
  set_env_value "JWT_SECRET" "$(str_random 64)"

  set_env_value "ADMIN_PASSWORD" "admin_pwd_${PWD_SUFFIX}"
  set_env_value "DB_PASSWORD" "towerify_pwd_${PWD_SUFFIX}"
  set_env_value "DB_ROOT_PASSWORD" "towerify_root_pwd_${PWD_SUFFIX}"
  set_env_value "CH_PASSWORD" "towerify_ch_pwd_${PWD_SUFFIX}"
  set_env_value "ZO_ROOT_USER_PASSWORD" "openobserve_pwd_${PWD_SUFFIX}"
  set_env_value "OPENOBSERVE_PASSWORD" "openobserve_pwd_${PWD_SUFFIX}"

  echo "✅ .env généré avec succès"
else
  echo "✅ Utilisation du fichier .env existant"
fi

# Définir les arguments par défaut pour docker compose up
DOCKER_COMPOSE_ARGS=${DOCKER_COMPOSE_ARGS:-"up --build --detach"}

docker compose $DOCKER_COMPOSE_ARGS

echo -e "\nStack démarrée.\nVous pouvez maintenant accéder à :"
echo -e "  • Cywise UI             : http://localhost:17801"
echo -e "  • Performa              : http://localhost:17802"
echo -e "  • Mailpit               : http://localhost:17803"
echo -e "  • Jobs scanner de vulns : http://localhost:17804"
echo -e "  • Logs scanner de vulns : http://localhost:17805"
echo -e "\nVous pouvez l'arrêter avec :"
echo -e "  ./stack-stop.sh"
