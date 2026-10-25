# ITEM-007: Correction de l'action MariaDB GitHub

- Owner: Junie
- Base: main
- Branch: item/ITEM-007
- Status: done

## Description

Corriger l'erreur de workflow GitHub Actions causée par l'action `yakkadev/mariadb-actions` introuvable.
Le workflow `tests.yml` doit être mis à jour pour utiliser un service MariaDB standard.

## Acceptance

- [ ] L'action `yakkadev/mariadb-actions` est supprimée de `tests.yml`.
- [ ] Un service MariaDB est configuré dans `tests.yml`.
- [ ] Le workflow s'exécute correctement avec la base de données.
