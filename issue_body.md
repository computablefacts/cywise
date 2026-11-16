## Description

Ajouter la possibilité d'afficher les événements de sécurité précédemment masqués (dismissed) et de les démasquer si nécessaire.

## Acceptance Criteria

**Scenario:** Afficher les événements masqués
**Given** un utilisateur avec des événements masqués
**When** l'utilisateur active le filtre "Afficher les événements masqués"
**Then** les événements masqués apparaissent dans la liste avec un indicateur visuel

**Scenario:** Démasquer un événement
**Given** un événement masqué affiché dans la liste
**When** l'utilisateur clique sur le bouton "Démasquer"
**Then** l'événement est marqué comme non masqué
**And** il redevient visible dans la liste normale (si le filtre est désactivé)

**Scenario:** Masquer un événement (existant, pour vérification)
**Given** un événement non masqué
**When** l'utilisateur clique sur "Masquer"
**Then** l'événement disparaît de la liste normale
