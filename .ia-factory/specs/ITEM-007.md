# ITEM-007 Specs

## ITEM-007-S01: Utiliser un service MariaDB standard

Status: completed
Requirement: Remplacer l'action YakkaDev/mariadb-actions par un conteneur de service MariaDB natif à GitHub Actions.
Test: Exécution réussie du workflow GHA.
Commit: f71eb15e

## ITEM-007-S02: Supprimer l'arrêt manuel de MySQL

Status: completed
Requirement: Supprimer l'étape "Shutting down MySQL" car le service MariaDB s'exécutera sur son propre port ou ne sera pas en conflit avec l'installation système locale.
Test: Exécution réussie du workflow GHA.
Commit: f71eb15e
