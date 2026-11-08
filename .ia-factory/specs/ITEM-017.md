# ITEM-017 Specs

## ITEM-017-S01: Clarification du positionnement (Accueil)

Status: completed
Requirement: Repenser la page d'accueil pour expliquer simplement ce que fait Cywise et pour qui.
- Remplacer "Votre entreprise est déjà une cible" par un ton plus factuel.
- Réintégrer le cadre "Diagnostiquer, Identifier, Accompagner" :
    - Diagnostiquer : Listez vos actifs exposés en quelques secondes.
    - Identifier : Détectez les vulnérabilités et identifiants compromis.
    - Accompagner : Suivez un plan d'action clair avec CyberBuddy.
- Supprimer les triades (ex: "Découvrez. Priorisez. Sécurisez.") au profit de descriptions concrètes.
- Éviter les surpromesses ("Une défense complète").
Commit: 2f68e56b
Test: Revue manuelle du contenu de la homepage (uniquement en mode `redesign`).

## ITEM-017-S02: Fusion des pages Solutions et Cas d'usage

Status: completed
Requirement: Créer une page Solutions unique regroupant la technique et les besoins concrets.
- Supprimer le menu "Cas d'usage".
- Intégrer les cas d'usage (ex: préparer un audit, rédiger une PSSI) comme des bénéfices/applications de la solution.
- Utiliser les témoignages clients pour illustrer les cas d'usage réels.
Commit: 65f663ed
Test: Les anciennes URLs redirigent vers la nouvelle page unique /solutions.

## ITEM-017-S03: Amélioration de la page Société et Visibilité Équipe

Status: completed
Requirement: Améliorer la visibilité de l'équipe.
- Ajouter "L'équipe" ou "Qui sommes-nous" dans le menu principal (Header).
- Préciser le rôle réel des membres de l'équipe (Salarié, Freelance, Partenaire).
- Mettre à jour les dates relatives (ex: "il y a 15 ans" -> "Depuis 2009").
Commit: 601bf690
Test: Lien présent dans le header. Page Société mise à jour.

## ITEM-017-S04: Clarification des tarifs

Status: completed
Requirement: Clarifier l'offre tarifaire sans masquer les prix.
- Rendre explicite "2 mois offerts" lors du passage en facturation annuelle (ex: badge ou texte dédié).
- Ajouter une section "Glossaire" ou des infobulles pour expliquer : CyberTodo, CyberScribe, Agents, Hardening.
- Aligner les noms d'offres entre FR et EN.
- Préciser la mention "HT" et l'unité de facturation (ex: par mois et par domaine).
Commit: a13c354f
Test: Revue de la page /pricing.

## ITEM-017-S05: Révision rédactionnelle (Anti-IA)

Status: completed
Requirement: Nettoyage global des tournures "IA".
- Supprimer toutes les triades d'impératifs (9 variantes).
- Reformuler les titres en deux phrases nominales (ex: "Une plateforme. Une défense complète." -> "Une plateforme unique pour votre cyberdéfense").
- Remplacer les cartes à titres abstraits ("Sérénité continue") par des fonctionnalités concrètes ("Surveillance 24/7").
- Éviter le ton anxiogène.
Commit: 04c1e8de
Test: Scan exhaustif des 57 pages du `redesign` pour vérification des motifs.

## ITEM-017-S06: Corrections techniques et incohérences

Status: completed
Requirement: Résoudre les 32 points d'incohérences listés (hors PSSI).
- Supprimer les articles de blog de test.
- Rediriger les anciennes pages (/a-propos, /pentest legacy).
- Unifier les emails sur le domaine @cywise.io.
- Corriger les erreurs 400 dans le footer.
- Mettre à jour les mentions légales (hébergeurs, siège social).
Commit: 82ff64c9
Test: Vérification point par point de la liste d'incohérences.
