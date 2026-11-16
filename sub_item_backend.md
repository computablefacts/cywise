Part of #114.

## Requirement

**Scenario:** Démasquer un événement
  **Given** un événement masqué (`dismissed = true`)
  **When** la méthode `undismiss` est appelée avec son ID
  **Then** l'événement est marqué comme non masqué (`dismissed = false`)

**Scenario:** Filtrer les événements masqués
  **Given** des événements masqués et non masqués
  **When** la méthode `list` est appelée avec `include_dismissed = true`
  **Then** tous les événements sont retournés
  **When** la méthode `list` est appelée avec `only_dismissed = true`
  **Then** seuls les événements masqués sont retournés

## Test

`EventsProcedureTest::test_undismiss`
`EventsProcedureTest::test_list_with_filters`
