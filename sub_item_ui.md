Part of #114.

## Requirement

**Scenario:** Interface de filtrage
  **Given** la page de la timeline des événements
  **When** l'utilisateur utilise le nouveau toggle/filtre pour les événements masqués
  **Then** l'URL est mise à jour avec le paramètre approprié
  **And** la liste affiche les événements correspondants

**Scenario:** Action de démasquage
  **Given** un événement masqué affiché dans l'interface
  **When** l'utilisateur clique sur le bouton "Démasquer"
  **Then** l'action est envoyée au backend
  **And** l'interface est mise à jour pour refléter le changement

## Test

Vérification manuelle de l'UI et tests d'intégration si possible.
