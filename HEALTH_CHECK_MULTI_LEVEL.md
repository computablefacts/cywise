# Health Check Multi-Niveau

Ce système de Health Check fournit trois endpoints séparés avec des seuils différents pour adapter la surveillance selon la criticité.

## Endpoints disponibles

Chaque niveau expose trois routes :
- **Simple** (`/check-health/{level}`) : résultat global uniquement, idéal pour les probes Kubernetes et les outils de monitoring qui vérifient uniquement le code HTTP
- **JSON** (`/check-health/{level}/json`) : résultat détaillé avec le statut de chaque check individuel (même structure que `/check-health/json` de Spatie)
- **UI** (`/check-health/{level}/ui`) : page HTML avec le détail visuel des checks (même rendu que `/check-health/ui` de Spatie, authentification requise)

---

### 🔴 Critical

#### `/check-health/critical` — résultat global

**Usage** : Alerting immédiat et critique (Kubernetes liveness probe, etc.)  
**Seuils** : Stricts pour une détection rapide des problèmes graves

**Code de statut HTTP** :
- `200` : Tous les checks sont ok ou en warning
- `503` : Au moins un check a échoué

**Exemple de réponse** :
```json
{"healthy": true}
```

#### `/check-health/critical/json` — résultat JSON détaillé

**Checks inclus** :
- **DatabaseCheck** : Connexion à la base de données
- **CacheCheck** : Connexion au cache
- **ApiVulnerabilityScanner** : Accès à l'API du scanner de vulnérabilités
- **Queues** (critical, medium, low, scout, default) : > 25 minutes
- **ScheduleCheck** : > 25 minutes depuis le dernier heartbeat
- **UsedDiskSpaceCheck** : < 5% libre (95% utilisé)
- **AssetsDiscoverCheck** : > 90 secondes

**Exemple de réponse** (structure identique à `/check-health/json` de Spatie) :
```json
{
  "finishedAt": 1709389200,
  "checkResults": [
    {
      "name": "critical.DatabaseCheck",
      "label": "Database",
      "notificationMessage": "",
      "shortSummary": "Ok",
      "status": "ok",
      "meta": {}
    }
  ]
}
```

#### `/check-health/critical/ui` — interface HTML *(auth requise)*

Page HTML affichant le statut visuel des checks du niveau critical uniquement. Rendu identique à `/check-health/ui` de Spatie.

---

### 🟡 Medium

#### `/check-health/medium` — résultat global

**Usage** : Surveillance opérationnelle standard (Kubernetes readiness probe, etc.)

**Code de statut HTTP** :
- `200` : Tous les checks sont ok ou en warning
- `503` : Au moins un check a échoué

#### `/check-health/medium/json` — résultat JSON détaillé

**Checks inclus** :
- **Queues** (critical, medium, low, scout, default) : > 5 minutes
- **ScheduleCheck** : > 5 minutes depuis le dernier heartbeat
- **UsedDiskSpaceCheck** : < 10% libre (90% utilisé)
- **AssetsDiscoverCheck** : > 60 secondes
- **DatabaseTableSizeCheck** : Table telescope_entries > 6000 MB
- **DebugModeCheck** : Le mode debug doit être désactivé (sauf en local)
- **OptimizedAppCheck** : L'application doit être optimisée (sauf en local)

#### `/check-health/medium/ui` — interface HTML *(auth requise)*

Page HTML affichant le statut visuel des checks du niveau medium uniquement.

---

### 🟢 Info

#### `/check-health/info` — résultat global

**Usage** : Monitoring informatif uniquement

**Code de statut HTTP** :
- `200` : Tous les checks sont ok ou en warning
- `503` : Au moins un check a échoué

#### `/check-health/info/json` — résultat JSON détaillé

**Checks inclus** :
- **UsedDiskSpaceCheck** : < 20% libre (80% utilisé)
- **DatabaseTableSizeCheck** : Table telescope_entries > 4000 MB

#### `/check-health/info/ui` — interface HTML *(auth requise)*

Page HTML affichant le statut visuel des checks du niveau info uniquement.

---

## Récapitulatif des routes

| Route | Type | Auth | Description |
|---|---|---|---|
| `GET /check-health/critical` | Simple | Non | `{"healthy":true}` / 503 |
| `GET /check-health/critical/json` | JSON | Non | Résultat détaillé (format Spatie) |
| `GET /check-health/critical/ui` | HTML | Oui | Interface visuelle |
| `GET /check-health/medium` | Simple | Non | `{"healthy":true}` / 503 |
| `GET /check-health/medium/json` | JSON | Non | Résultat détaillé (format Spatie) |
| `GET /check-health/medium/ui` | HTML | Oui | Interface visuelle |
| `GET /check-health/info` | Simple | Non | `{"healthy":true}` / 503 |
| `GET /check-health/info/json` | JSON | Non | Résultat détaillé (format Spatie) |
| `GET /check-health/info/ui` | HTML | Oui | Interface visuelle |

---

## Statuts possibles

Chaque check peut retourner un des statuts suivants :
- **ok** : Le check a réussi
- **warning** : Le check a détecté un problème mineur
- **failed** : Le check a échoué (problème critique)

Le statut global de l'endpoint est déterminé par :
- **failed** si au moins un check est `failed`
- **warning** si au moins un check est `warning` et aucun n'est `failed`
- **ok** si tous les checks sont `ok`

---

## Utilisation avec Kubernetes

Ces endpoints simples sont compatibles nativement avec les probes Kubernetes :

### Liveness Probe (utiliser Critical)
```yaml
livenessProbe:
  httpGet:
    path: /check-health/critical
    port: 80
  initialDelaySeconds: 30
  periodSeconds: 60
  timeoutSeconds: 5
  failureThreshold: 3
```

### Readiness Probe (utiliser Medium)
```yaml
readinessProbe:
  httpGet:
    path: /check-health/medium
    port: 80
  initialDelaySeconds: 10
  periodSeconds: 30
  timeoutSeconds: 5
  failureThreshold: 2
```

---

## Utilisation avec les outils de monitoring

### Prometheus / Grafana
Vous pouvez monitorer ces endpoints avec Blackbox Exporter :

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'health-critical'
    metrics_path: /probe
    params:
      module: [http_2xx]
    static_configs:
      - targets:
        - https://your-app.com/check-health/critical
    relabel_configs:
      - source_labels: [__address__]
        target_label: __param_target
      - source_labels: [__param_target]
        target_label: instance
      - target_label: __address__
        replacement: blackbox-exporter:9115
```

### Uptime Kuma / UptimeRobot
Configurez des monitors HTTP avec :
- URL : `https://your-app.com/check-health/critical`
- Status codes attendus : `200`
- Intervalle : selon le niveau (Critical: 1 min, Medium: 5 min, Info: 15 min)

### Nagios / Icinga
```bash
# check critical endpoint
check_http -H your-app.com -u /check-health/critical -s '"status":"ok"' -e 200
```

---

## Personnalisation des seuils

Pour modifier les seuils, éditez le fichier :
`app/Providers/HealthServiceProvider.php`

Exemple - modifier le seuil disk space pour critical :
```php
UsedDiskSpaceCheck::new()->name('critical.UsedDiskSpaceCheck')
    ->failWhenUsedSpaceIsAbovePercentage(97), // au lieu de 95
```

---

## Anciennes routes (rétrocompatibilité)

Les endpoints Spatie natifs restent disponibles :
- `GET /check-health` : Health check simple (tous niveaux confondus)
- `GET /check-health/ui` : Interface web complète (authentification requise)
- `GET /check-health/json` : Résultats JSON complets (authentification API requise)

---

## Architecture technique

### Fichiers modifiés/créés :
1. **app/Providers/HealthServiceProvider.php** : Configuration des checks par niveau
2. **app/Http/Controllers/MultiLevelHealthCheckController.php** : Contrôleur des endpoints (simple, JSON, UI)
3. **app/Console/Commands/RunLevelHealthChecksCommand.php** : Commande d'exécution par niveau
4. **app/Check/AssetsDiscoverCheck.php** : Ajout des méthodes `failAfterSeconds()` et `warnAfterSeconds()`
5. **routes/web.php** : Déclaration des 9 routes (3 niveaux × 3 formats)
6. **bootstrap/app.php** : Planification des checks par niveau

### Comment ça fonctionne :
1. Tous les checks sont enregistrés avec un préfixe (critical., medium., info.)
2. Chaque niveau a ses propres instances de checks avec des seuils différents
3. La commande `health:check-level {level}` exécute uniquement les checks du niveau demandé et stocke les résultats dans le cache Laravel (clé `health:level_results:{level}`)
4. Le contrôleur lit les résultats depuis le cache par niveau ; si le cache est vide (premier démarrage), il bascule sur le store Spatie (base de données)
5. Chaque niveau est rafraîchi indépendamment via le scheduler Laravel

### Fréquences de rafraîchissement :
| Niveau   | Commande                            | Fréquence     |
|----------|-------------------------------------|---------------|
| critical | `health:check-level critical`       | Chaque minute |
| medium   | `health:check-level medium`         | Toutes les 5 minutes |
| info     | `health:check-level info`           | Toutes les heures |

---

## Dépannage

### Les checks ne s'exécutent pas
Vérifiez que le scheduler Laravel tourne :
```bash
php artisan schedule:list
```

Et que les commandes suivantes sont exécutées toutes les minutes :
- `spatie:health:dispatch-queue-check-jobs`
- `spatie:health:schedule-check-heartbeat`

### Erreur "Class not found"
Régénérez l'autoloader :
```bash
composer dump-autoload
```

### Les résultats sont vides
Lancez manuellement l'exécution des checks par niveau :
```bash
php artisan health:check-level critical
php artisan health:check-level medium
php artisan health:check-level info
```

---

## Pour aller plus loin

Documentation officielle de Spatie Laravel Health :
https://spatie.be/docs/laravel-health/v1

Liste des checks disponibles :
https://spatie.be/docs/laravel-health/v1/available-checks/overview
