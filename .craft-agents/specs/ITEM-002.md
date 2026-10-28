# ITEM-002 Specs

## ITEM-002-S01: Renommer en pluriel

Status: completed
Requirement: Changer "Cas d'usage" en "Cas d'usages" dans le menu.
Test: Vérification visuelle dans le header.
Commit: da57ac93

## ITEM-002-S02: Transformer en menu déroulant (Desktop)

Status: completed
Requirement: Déplacer "Cas d'usages" du tableau `$links` vers `$menus` dans `header.blade.php` pour en faire un dropdown.
Test: Vérification visuelle et interaction sur desktop.
Commit: da57ac93

## ITEM-002-S03: Liste des cas d'usages

Status: completed
Requirement: Inclure les cas d'usages suivants : "Surveiller la surface d'attaque", "Trouver les vulnérabilités", "Surveiller les identifiants", "Préparer un audit", "Créer une PSSI", "Lancer un Pentest".
Test: Vérification de la présence et des liens des items dans le menu.
Commit: da57ac93

## ITEM-002-S04: Adaptation Mobile

Status: completed
Requirement: S'assurer que le nouveau menu "Cas d'usages" est bien géré dans la section mobile du header.
Test: Vérification sur simulateur mobile ou réduction de fenêtre.
Commit: da57ac93

## ITEM-002-S05: Footer - Renommer Société

Status: completed
Requirement: Changer "La société" en "Société" dans la colonne Cywise du footer.
Test: Vérification visuelle dans le footer.
Commit: e4c5689d

## ITEM-002-S06: Footer - Colonne Cas d'usages

Status: completed
Requirement: Ajouter une colonne "Cas d'usages" (ou "Use cases") dans le footer juste après la colonne "Pour qui", supprimer la colonne "Ressources", et déplacer ses liens (Blog, Tarifs) dans la colonne "Cywise" sous "Société".
Test: Vérification de la nouvelle structure du footer.
Commit: e4c5689d, 3af50a30, e0e85909
