# ITEM-021 Specs

## ITEM-021-S01: Réduction de la taille XL

Status: dropped
Requirement: Réduire la taille `xl` de `clamp(34px, 9vw, 140px)` à `clamp(34px, 7vw, 90px)`.
Test: Vérification manuelle de la classe générée dans le composant.
Commit: f3c18882

## ITEM-021-S02: Réduction de la taille LG

Status: dropped
Requirement: Réduire la taille `lg` de `clamp(28px, 5vw, 76px)` à `clamp(28px, 4.5vw, 64px)`.
Test: Vérification manuelle de la classe générée dans le composant.
Commit: ec934c8c

## ITEM-021-S03: Réduction de la taille MD

Status: dropped
Requirement: Réduire la taille `md` de `clamp(28px, 3.8vw, 56px)` à `clamp(24px, 3.5vw, 48px)`.
Test: Vérification manuelle de la classe générée dans le composant.
Commit: b82eeb7f

## ITEM-021-S04: Ajustement taille XL (Compromis)

Status: dropped
Requirement: Ajuster la taille `xl` à `clamp(34px, 8vw, 110px)`.
Test: Vérification manuelle.
Commit: ae71c284

## ITEM-021-S05: Ajustement taille LG (Compromis)

Status: dropped
Requirement: Ajuster la taille `lg` à `clamp(28px, 4.8vw, 70px)`.
Test: Vérification manuelle.
Commit: ae71c284

## ITEM-021-S06: Ajustement taille MD (Compromis)

Status: dropped
Requirement: Ajuster la taille `md` à `clamp(26px, 3.6vw, 52px)`.
Test: Vérification manuelle.
Commit: ae71c284

## ITEM-021-S07: Harmonisation taille XL (Ref Page Solutions)

Status: dropped
Requirement: Ajuster la taille `xl` à `clamp(30px, 4.2vw, 54px)`.
Test: Vérification manuelle.
Commit: cb2e7b4f

## ITEM-021-S08: Harmonisation taille LG (Ref Page Solutions)

Status: dropped
Requirement: Ajuster la taille `lg` à `clamp(28px, 3.8vw, 48px)`.
Test: Vérification manuelle.
Commit: cb2e7b4f

## ITEM-021-S09: Harmonisation taille MD (Ref Page Solutions)

Status: dropped
Requirement: Ajuster la taille `md` à `clamp(24px, 3.5vw, 42px)`.
Test: Vérification manuelle.
Commit: cb2e7b4f

## ITEM-021-S10: Réduction des surcharges manuelles

Status: dropped
Requirement: Identifier et réduire les surcharges CSS inline (ui:text-[clamp(...)]) supérieures à 60px dans les fichiers Blade.
Test: Vérification visuelle via code.
Commit: a88c342e

## ITEM-021-S11: Mise à jour finale du composant Display

Status: dropped
Requirement: Ajuster les tailles par défaut du composant `x-site.display` (XL: 60px max, LG: 52px max, MD: 44px max).
Test: Vérification manuelle.
Commit: a88c342e

## ITEM-021-S12: Uniformisation Stricte (Réf 54px)

Status: dropped
Requirement: Fixer XL à `clamp(30px, 4.2vw, 54px)` et supprimer les surcharges de taille sur les H1 XL du site corporate.
Test: Vérification visuelle.
Commit: f0149fbe

## ITEM-021-S13: Équilibre final (Home 72px / Intérieur 54px)

Status: dropped
Requirement: Fixer XL par défaut à `clamp(32px, 7vw, 72px)` (pour la Home) et conserver/rétablir 54px pour les H1 des pages intérieures.
Test: Vérification visuelle.
Commit: -

## ITEM-021-S14: Uniformisation Universelle (64px)

Status: dropped
Requirement: Fixer XL à `clamp(30px, 5vw, 64px)` pour tous les H1 et supprimer toutes les surcharges manuelles sur le site corporate.
Test: Vérification visuelle.
Commit: ce6f9066

## ITEM-021-S15: Uniformisation Stricte (Réf Use-Cases 54px, Boosted VW)

Status: dropped
Requirement: Fixer XL à `clamp(30px, 5.4vw, 54px)` et supprimer toutes les surcharges de taille sur les H1 XL du site corporate.
Test: Vérification visuelle.
Commit: 7d32b80b

## ITEM-021-S16: Uniformisation Totale par Niveau (Sans surcharges)

Status: completed
Requirement: Aligner x-site.display sur les refs (XL=54px/125%, LG=48px/112%, MD=42px/118%) et supprimer TOUTES les surcharges de style sur Display dans le site corporate.
Test: Vérification visuelle du code.
Commit: 75ed2146

## ITEM-021-S17: Nouvelle Échelle Typographique (Base 72px)

Status: completed
Requirement: Re-scaler les titres sur une base de 72px pour le H1 (XL), avec ajustement proportionnel pour LG (60px) et MD (50px).
Test: Vérification visuelle.
Commit: 4ca5658f

## ITEM-021-S18: Ajustement Fluidité (Impact Boosté)

Status: completed
Requirement: Augmenter le facteur VW à 9vw pour le H1 (XL) afin d'atteindre le plafond de 72px plus tôt, garantissant l'impact sur tous les écrans.
Test: Vérification visuelle.
Commit: f63a50f7

## ITEM-021-S19: Retour Échelle Maximale (Base 140px)

Status: completed
Requirement: Re-scaler tous les titres sur une base de 140px pour le H1 (XL), avec LG à 90px et MD à 64px, tout en gardant l'uniformité.
Test: Vérification visuelle.
Commit: 6615d2dd

## ITEM-021-S20: Réduction Finale (Base 100px)

Status: completed
Requirement: Réduire le H1 (XL) à 100px max et ré-ajuster proportionnellement LG (68px) et MD (48px).
Test: Vérification visuelle.
Commit: 9f5cf48a

## ITEM-021-S21: Réduction Ultime (Base 80px)

Status: completed
Requirement: Réduire le H1 (XL) à 80px max et ré-ajuster proportionnellement LG (56px) et MD (42px).
Test: Vérification visuelle.
Commit: a4913a0a

## ITEM-021-S22: Réduction Finale (Base 70px)

Status: completed
Requirement: Réduire le H1 (XL) à 70px max et ré-ajuster proportionnellement LG (52px) et MD (40px).
Test: Vérification visuelle.
Commit: 318ad8a7

## ITEM-021-S23: Réduction Finale (Base 60px)

Status: completed
Requirement: Réduire le H1 (XL) à 60px max et ré-ajuster proportionnellement LG (44px) et MD (36px).
Test: Vérification visuelle.
Commit: c84be6a7

## ITEM-021-S24: Couleur Barre Pentest

Status: completed
Requirement: Changer la couleur de la barre à gauche de Pentest dans le menu vers noir (ink).
Test: Vérification visuelle.
Commit: afddcf44

## ITEM-021-S25: Cohérence Couleur Audit

Status: completed
Requirement: Aligner la couleur du mot "preuves" sur celle du menu (bleu/info) dans la page Audit.
Test: Vérification visuelle.
Commit: 982733b0

## ITEM-021-S26: Surlignage Cohérent Titres

Status: completed
Requirement: Remplacer les couleurs de texte par des surlignages (bg) dans les titres Solutions et Cas d'usage, sans changer la couleur du texte.
Test: Vérification visuelle.
Commit: f17cbd16

## ITEM-021-S27: Texte Surligné en Blanc

Status: completed
Requirement: Mettre le texte surligné en blanc pour assurer la lisibilité sur les fonds de couleur.
Test: Vérification visuelle.
Commit: 1dec71c2

## ITEM-021-S28: Couleur CyberBuddy Bleu

Status: completed
Requirement: Passer la couleur de CyberBuddy en bleu (info) dans le menu et sur sa page dédiée.
Test: Vérification visuelle.
Commit: a3f1e94b

## ITEM-021-S29: Uniformisation Audiences

Status: completed
Requirement: Ajouter les barres de couleur dans le menu "Pour qui" et le surlignage avec texte blanc dans les titres des pages Audiences.
Test: Vérification visuelle.
Commit: 5a8e214d

## ITEM-021-S30: Ordre Couleurs Audiences

Status: completed
Requirement: Réorganiser les couleurs Audiences (rouge, orange, vert, bleu, noir) et s'assurer du texte blanc partout.
Test: Vérification visuelle.
Commit: af826d9c

## ITEM-021-S31: Accents Titres en Orange

Status: completed
Requirement: Remplacer le texte gris (slate-400) par du orange (brand-500) dans les accents de titres.
Test: Vérification visuelle.
Commit: 8e8412ac

## ITEM-021-S32: Uniformisation Ordre Listes

Status: completed
Requirement: Uniformiser l'ordre des couleurs dans les listes (Solutions, Use-cases) : rouge, orange, vert, bleu, noir.
Test: Vérification visuelle.
Commit: dd2f150a

## ITEM-021-S33: Ordre Barre Multicolore

Status: completed
Requirement: Réorganiser la barre multicolore (strip) en haut et bas de page : rouge, orange, vert, bleu, noir.
Test: Vérification visuelle.
Commit: b8a7d1e2
