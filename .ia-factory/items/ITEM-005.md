# ITEM-005: Réexécution et correction des tests automatiques

- Owner: Junie
- Base: main
- Branch: item/ITEM-005
- Status: done

## Description

Rejouer l'ensemble des tests automatiques du projet à l'aide de Pest et corriger les échecs éventuels.
Une attention particulière sera portée à l'allocation mémoire nécessaire pour l'exécution complète.

## Acceptance

- [x] Tous les tests automatiques passent avec succès.
- [x] La limite de mémoire est ajustée si nécessaire dans la configuration.
- [x] Les tests ignorés (skipped) sont analysés et réactivés si possible.
