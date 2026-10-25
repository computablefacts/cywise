# ITEM-005: Réexécution et correction des tests automatiques

- Owner: Junie
- Base: main
- Branch: item/ITEM-005
- Status: ready

## Description

Rejouer l'ensemble des tests automatiques du projet à l'aide de Pest et corriger les échecs éventuels.
Une attention particulière sera portée à l'allocation mémoire nécessaire pour l'exécution complète.

## Acceptance

- [ ] Tous les tests automatiques passent avec succès.
- [ ] La limite de mémoire est ajustée si nécessaire dans la configuration.
- [ ] Les tests ignorés (skipped) sont analysés et réactivés si possible.
