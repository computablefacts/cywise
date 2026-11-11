# ITEM-022: Redesign des cartouches de la homepage

- Owner: Junie
- Base: main
- Branch: item/ITEM-022
- Status: ready

## Description

Sur la homepage, dans la section "Une plateforme au service de votre défense", modifier le style des cartouches (edge-card) pour qu'ils ressemblent à ceux de la section "Conçu pour les équipes qui exigent de la clarté" (audiences).
Le texte "découvrir ->" doit être coloré avec la couleur du bord original de la cartouche.

## Acceptance

- [ ] Les cartouches de la section "Solutions" n'ont plus de bordure colorée épaisse à gauche.
- [ ] Les cartouches de la section "Solutions" ont un fond `ui:bg-slate-100` et passent à `ui:bg-slate-200` au survol.
- [ ] Le lien "Découvrir →" dans chaque cartouche est coloré selon le `tone` original de la solution (critical, medium, low, brand, ink).
- [ ] L'apparence générale est cohérente avec la section "Audiences".
