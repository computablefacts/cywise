# ITEM-022 Specs

## ITEM-022-S01: Style des cartouches Solutions

Status: completed
Requirement: Modifier les cartouches de la section "Une plateforme au service de votre défense" pour utiliser le fond uni et supprimer le bord coloré.
Test: Les classes CSS appliquées aux cartouches de solutions correspondent à celles des audiences (ui:bg-slate-100, ui:hover:bg-slate-200, sans bordure épaisse).
Commit: b8854cb

## ITEM-022-S02: Coloration du lien "Découvrir →"

Status: completed
Requirement: Colorer le texte "Découvrir →" avec la couleur associée au `tone` de la solution.
Test: Chaque lien "Découvrir →" possède une classe de couleur de texte correspondant à son type (ex: ui:text-critical pour la surface d'attaque).
Commit: b8854cb

## ITEM-022-S03: Redesign de la page Solutions

Status: completed
Requirement: Appliquer le même redesign (variante `flat` et liens colorés) aux cartouches de la page /solutions (FR et EN).
Test: Les pages /solutions et /en/solutions utilisent le composant x-site.edge-card avec variant="flat" et les liens colorés selon le tone.
Commit: 11c74b3
