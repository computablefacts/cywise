<p align="center">
    <a href="https://cywise.io" target="_blank">
        <img src="/public/cywise/img/cywise-catchphrase-fr.png" width="300">
    </a>
</p>
<p align="center">
    <a href="https://github.com/computablefacts/towerify/releases" target="_blank">
        <img src="https://img.shields.io/github/v/release/computablefacts/towerify" alt="Latest Stable Version">
    </a>
    <a href="https://github.com/computablefacts/towerify/actions" target="_blank">
        <img src="https://github.com/computablefacts/towerify/actions/workflows/tests.yml/badge.svg" alt="Build Status">
    </a>
    <a href="https://github.com/computablefacts/towerify/commits" target="_blank">
        <img src="https://img.shields.io/github/commit-activity/y/computablefacts/towerify.svg" alt="GitHub commit activity">
    </a>
    <a href="https://github.com/computablefacts/towerify/graphs/contributors" target="_blank">
        <img src="https://img.shields.io/github/contributors-anon/computablefacts/towerify.svg" alt="GitHub contributors">
    </a>
    <a href="https://github.com/computablefacts/towerify/LICENSE.md" target="_blank">
        <img src="https://img.shields.io/badge/license-AGPLv3-green" alt="License">
    </a>
</p>
<p align="center">
  <em>
    Sécurisez en quelques minutes vos sites web (vitrines, e-commerce, portails clients, ...) et votre infrastructure exposée sur Internet (VPN, extranet, serveurs, ...)
  </em>
</p>

---

# Pourquoi une version open source et auto-hébergée de Cywise ?

Chez Cywise, nous croyons que la cybersécurité doit être accessible, transparente et adaptable à tous les
environnements, y compris les plus sensibles.

La **version auto-hébergée** de Cywise vous permet de scanner et sécuriser votre **réseau interne** (serveurs locaux,
équipements non exposés), là où la version SaaS se limite aux infrastructures accessibles depuis Internet.

Cette solution est idéale pour les entreprises souhaitant renforcer la sécurité de leur infrastructure complète, y
compris les **segments isolés du web**, tout en gardant un contrôle total sur leurs données et leur environnement.

# Fonctionnalités

Cywise intègre toutes les fonctionnalités essentielles aux TPE et PME.

## Protéger ce qui est accessible sur internet

### Scanner de vulnérabilités

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Surveillance proactive et correction automatisée.** Ce module analyse en continu votre infrastructure pour détecter
plus de 50 000 vulnérabilités. Les failles sont classées
par niveau de criticité, et des actions correctives détaillées sont proposées. Une vérification automatique confirme
l'application des correctifs, assurant une protection optimale et à jour.

### Surveillance des fuites de données

:x: auto-hébergé :white_check_mark: SaaS

**Prévention des attaques grâce à une veille active.** Anticipez les risques en analysant 10 millions d'identifiants
fuités quotidiennement, avec un historique de 130 milliards d'identifiants (emails, mots de passe, domaines, etc.).
Détectez les identifiants compromis et les sites vulnérables à l'usurpation d'identité, puis recevez des alertes
automatiques pour agir avant toute exploitation malveillante.

### Honeypots intelligents

:x: auto-hébergé :white_check_mark: SaaS

**Piégez les attaquants avant qu'ils ne frappent.** Cywise déploie et maintient des leurres numériques conçus pour
attirer les cybercriminels et révéler leurs attaques en temps réel. Ces honeypots permettent d'identifier les menaces
actives, de déterminer si votre entreprise est spécifiquement ciblée, et d'évaluer les risques encourus par votre
infrastructure réelle. Une solution discrète pour comprendre les tactiques des attaquants avant qu'ils ne touchent vos
systèmes critiques.

## Protéger les actifs internes de l'entreprise

### Hardening

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Renforcez la sécurité de vos serveurs Linux et Windows.** Optimisez la configuration de vos machines grâce à un audit
complet, l'application de référentiels reconnus, et la création de règles personnalisées. Une configuration maîtrisée
réduit les vulnérabilités et améliore la résilience de votre infrastructure face aux cyberattaques.

> [!NOTE]
> La vérification de la configuration de vos machines est réalisée au moyen de règles [OSSEC](https://www.ossec.net/).

### Agents

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Surveillez et détectez les activités suspectes en temps réel.** Protégez vos serveurs Linux et Windows avec une
détection proactive des comportements anormaux. Bénéficiez de règles expertes ou créez les vôtres pour identifier les
menaces dès leur apparition. Une détection précoce limite les risques de compromission et renforce la sécurité de votre
environnement.

> [!NOTE]
> La collecte des événements de sécurité est réalisée avec [Osquery](https://osquery.io/).

### Métriques

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Assurez la disponibilité et la performance de vos serveurs.** Collectez et analysez des métriques essentielles (CPU,
stockage, ressources) pour garantir la disponibilité de vos applications. Anticipez les besoins en capacité et maintenez
une infrastructure stable, un élément clé pour une cybersécurité robuste.

> [!NOTE]
> La collecte des métriques systèmes est réalisée avec [Performa](https://github.com/jhuckaby/performa).

## Accompagner les utilisateurs

### CyberBuddy

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Votre expert en cybersécurité, disponible 24/7.** Avec Cywise, posez toutes vos questions sur la cybersécurité ou les
résultats de vos scans. CyberBuddy, notre assistant virtuel, vous guide en temps réel en s'appuyant sur des bases de
connaissances vérifiées et des bonnes pratiques reconnues. Une expertise accessible, où que vous soyez.

> [!WARNING]
> Pour activer cette fonctionnalité dans la version auto-hébergée, vous devrez fournir votre propre clé d'API DeepInfra.

### CyberScribe

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Rédaction assistée de vos documents cyber.** Besoin de créer une Charte Informatique ou une Politique de Sécurité des
Systèmes d'Information (PSSI) ? CyberScribe, notre éditeur intelligent, vous accompagne pas à pas pour rédiger des
documents clairs, conformes et adaptés à vos besoins, grâce à l'intelligence artificielle.

> [!WARNING]
> Pour activer cette fonctionnalité dans la version auto-hébergée, vous devrez fournir votre propre clé d'API DeepInfra.

### Telegram & WhatsApp

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Restez informés, où que vous soyez.** Cywise vous permet d'interagir avec la plateforme en langage naturel au moyen
des messageries [Telegram](https://telegram.org) et [WhatsApp](https://www.whatsapp.com/).

> [!WARNING]
> Pour activer cette fonctionnalité dans la version auto-hébergée, vous devrez fournir votre propre clé d'API DeepInfra.

## Divers

### Fond documentaire

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Intégrez votre fond documentaire (Charte Informatique, PSSI, etc.) à CyberBuddy.** Offrez à vos équipes un accès
instantané et intuitif à l'information. Il leur suffit de poser une question en langage naturel, comme « CyberBuddy,
quelles sont les règles de télétravail dans notre PSSI ? », pour obtenir une réponse précise, extraite directement de
vos ressources internes. Une manière simple et efficace de diffuser vos bonnes pratiques et de faciliter l'accès à
l'information au quotidien.

### Single Sign-On (SSO)

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**En 2026, le SSO n'est plus une option.** Cywise intègre un module SSO moderne, compatible avec les standards du
marché (OAuth 2.0, SAML, OpenID Connect), pour vous permettre de contrôler les accès de manière unifiée.

# Fonctionnement

## CyberBuddy

CyberBuddy est au coeur de l'expérience Cywise. Il agit comme un orchestrateur intelligent capable de comprendre vos
demandes en langage naturel et d'y répondre en interrogeant différentes sources de données.

```text
   UTILISATEUR           MESSAGERIES              CYWISE WEBHOOK                   CYBERBUDDY (IA)
   +----------+        +-------------+        +-------------------+      +--------------------------------+
   |  Mobile  | <----> | - Telegram  | <----> |  - Validation     | <--> |  - Orchestrateur               |
   |   App    |        | - WhatsApp  |        |  - Mapping Thread |      |  - AgentSquad                  |
   +----------+        +-------------+        +-------------------+      |  - Thought/Action/Observation  |
                                                                         +---------------+----------------+
                                                                                         |
         +------------------------+------------------------+-----------------------------+--+
         |                        |                        |                                |
         v                        v                        v                                v
   [ DONNÉES STRUCTURÉES ]    [ BASE DE CONNAISSANCES ]  [ ACTIONS LOCALES ]             [ ACTIONS DISTANTES ]
   
   +-----------------------+  +-----------------------+  +----------------------------+  +---------------------------------+
   |  TABLES ANALYTIQUES   |  |   RECHERCHE HYBRIDE   |  |   APIS INTERNES JSON-RPC   |  |      APIS EXTERNES JSON-RPC     |
   |      (ClickHouse)     |  |    (MariaDB / RAG)    |  |       (Annotations)        |  |          (HTTP Request)         |
   +-----------------------+  +-----------------------+  +-------------+--------------+  +---------------------------------+
   | - Tables de données   |  | - Mémos               |                |                 | - URL & Headers                 |
   | - Génération SQL      |  | - Collections         |                v                 | - Payload JSON                  |
   | - Résultats TSV       |  | - Documents / Chunks  |  +----------------------------+  | - Response JSON                 |
   |                       |  | - Vecteurs (Cosine)   |  |    PROCÉDURES JSON-RPC     |  +---------------------------------+
   +-----------------------+  +-----------------------+  +----------------------------+ 
                                                         | - #[RpcMethod]             | 
                                                         | - Payload JsonRequest      | 
                                                         | - Response array           |
                                                         +----------------------------+
```

Le schéma ci-dessus illustre le flux de traitement d'une demande. Par exemple, si vous envoyez dans votre client de
messagerie « surveille www.example.com », CyberBuddy :

- identifie votre intention ;
- choisit l'action appropriée ;
- appelle la procédure JSON-RPC associée à cette action avec le domaine fourni ;
- confirme la mise en place de la surveillance après avoir reçu la réponse de la procédure.

Les composants clefs de cette architecture sont :

- **CyberBuddy.** A partir de votre demande en langage naturel, planifie les outils à appeler pour atteindre votre
  objectif. Par exemple :
    - « Quelles sont mes priorités ce matin ? »
- **Données structurées.** Utilise ClickHouse pour effectuer des analyses complexes et des calculs statistiques sur de
  grands volumes de données techniques (fuites de données ou imports de vos données tabulaires). Par exemple :
    - « Quels sont mes identifiants fuités ? »
- **Base de connaissance.** Système de RAG (Retrieval Augmented Generation) qui fouille dans vos documents (Chartes
  informatiques, PSSI, PDF) et vos notes personnelles (mémos) par recherche sémantique pour vous renvoyer des réponses
  factuelles et sourcées. Par exemple :
    - « Comment isoler un serveur compromis d'après nos procédures ? »
- **Actions locales.** Permettent de piloter directement les fonctionnalités de Cywise via ses API JSON-RPC. Par
  exemple :
    - « Surveille www.example.com »
    - « Envoie-moi un rapport de sécurité tous les mercredi à 11h. »
- **Actions distantes.** Permettent à Cywise d'interagir avec des systèmes tiers (SIEM, outils de ticketing, Cloud) en
  consommant leurs API JSON-RPC. Par exemple :
    - « Ouvre un ticket Jira pour cette vulnérabilité »
    - « Liste mes instances actives sur AWS. »

## Opérateur SOC

L'Opérateur SOC est un agent spécialisé dans l'analyse d'événements de sécurité. Il agit comme un analyste de niveau
1 capable de traiter de gros volumes d'événements techniques pour en extraire des signaux faibles et des comportements
anormaux.

```text
   SERVEUR UTILISATEUR                   OPÉRATEUR SOC (IA)                          RÉSULTATS
   +-----------------+      +--------------------------------------------+      +-------------------+
   |  - Événements   |      |  - Collecte & Compression                  |      |  - Verdict        |
   |  - IoC          | ---> |  - Contexte (Mémos)                        | ---> |  - Criticité      |
   +-----------------+      |  - Analyse d'intention (IA)                |      |  - Remédiation    |
                            +--------------------------------------------+      +-------------------+
```

Contrairement à CyberBuddy qui répond aux questions de l'utilisateur, l'Opérateur SOC travaille de manière autonome
pour :

- **Analyser les événements de sécurité.** Il traite les logs système collectés par Osquery sur vos serveurs Linux et
  Windows.
- **Prendre en compte le contexte.** Il utilise vos notes et procédures internes (mémos) pour adapter son analyse à
  votre environnement spécifique.
- **Qualifier la menace.** Il émet un verdict (NORMAL, SUSPECT, ANORMAL) accompagné d'un score de confiance et d'une
  justification détaillée.
- **Suggérer des actions.** En cas de détection suspecte, il recommande immédiatement les premières étapes de
  remédiation.

## Mémos

Les mémos sont des notes personnelles ou des procédures spécifiques que vous pouvez créer directement dans l'interface
de Cywise. Ils constituent le "contexte local" indispensable pour transformer une IA générique en un assistant
véritablement au fait de vos contraintes.

Leur importance est capitale car ils permettent de :

- **Personnaliser l'analyse.** En indiquant que tel serveur est un serveur de test, l'Opérateur SOC pourra qualifier
  certains comportements de "normaux" alors qu'ils seraient jugés "suspects" ailleurs.
- **Capitaliser sur votre savoir-faire.** En renseignant vos procédures d'astreinte ou vos contacts d'urgence,
  CyberBuddy peut les restituer immédiatement en cas de crise.
- **Réduire les faux positifs.** Plus l'IA connaît votre environnement (plages horaires, outils d'administration
  utilisés), plus son diagnostic est précis.

# Installation

## Pré-requis

- Un ordinateur sous linux
- Avoir installé [Docker](https://www.docker.com/)
- Avoir installé [git](https://git-scm.com/)

## Récupérer ce dépôt de code

Récupérez notre dépôt de code grâce à la commande :

```bash
git clone https://github.com/computablefacts/cywise.git
```

Puis placez-vous dans le répertoire nouvellement créé :

```bash
cd cywise
```

> [!NOTE]
> Toutes les commandes ci-après fonctionnent si elles sont lancées depuis ce
> répertoire.

Assurez-vous que nos scripts de gestion de la stack sont bien exécutables en lançant la commande :

```bash
chmod +x ./stack*
```

## Démarrage

Notre application consiste en plusieurs services Docker assemblés grâce à une
stack [docker compose](https://docs.docker.com/compose).

Vous pouvez démarrer la stack grâce à la commande :

```bash
./stack-start.sh
```

> [!NOTE]
> Ce script va créer un fichier `.env` avec les paramètres par défaut (principalement issus de `.env.example`) puis
> démarrer la stack.
>
> Comptez environ 15 minutes lors du premier démarrage.

## Arrêt

Vous pouvez arrêter la stack grâce à la commande :

```bash
./stack-stop.sh
```

## Suppression

Vous pouvez supprimer l'intégralité de la stack y compris toutes les données associées grâce à la commande :

```bash
./stack-destroy.sh
```

## Images Docker utilisées

| Service               | Local                                              | Image                                            | Open Source                      |
|:----------------------|:---------------------------------------------------|:-------------------------------------------------|:---------------------------------|
| app                   | [http://localhost:17801/](http://localhost:17801/) | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| scheduler             |                                                    | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| queue                 |                                                    | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| queue-low             |                                                    | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| queue-medium          |                                                    | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| queue-critical        |                                                    | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| queue-scout           |                                                    | :white_check_mark: [Publique][cywise]            | :white_check_mark: Oui (AGPL v3) |
| mariadb               |                                                    | :white_check_mark: [Publique][mariadb]           | :white_check_mark: Oui           |
| performa              | [http://localhost:17802/](http://localhost:17802/) | :white_check_mark: [Publique][cywise-performa]   | :white_check_mark: Oui           |
| mailpit               | [http://localhost:17803/](http://localhost:17803/) | :white_check_mark: [Publique][mailpit]           | :white_check_mark: Oui           |
| clickhouse-server     |                                                    | :white_check_mark: [Publique][clickhouse-server] | :white_check_mark: Oui           |
| sentinel-api          |                                                    | :white_check_mark: [Publique][sentinel-api]      | :x: Non (à venir)                |
| sentinel-wrq          |                                                    | :white_check_mark: [Publique][sentinel-wrq]      | :x: Non (à venir)                |
| sentinel-wrq-nuclei   |                                                    | :white_check_mark: [Publique][sentinel-wrq]      | :x: Non (à venir)                |
| sentinel-rq-dashboard | [http://localhost:17804/](http://localhost:17804/) | :white_check_mark: [Publique][rq-dashboard]      | :white_check_mark: Oui (MIT)     |
| sentinel-openobserve  | [http://localhost:17805/](http://localhost:17805/) | :white_check_mark: [Publique][openobserve]       | :white_check_mark: Oui (AGPL v3) |
| sentinel-redis        |                                                    | :white_check_mark: [Publique][redis]             | :white_check_mark: Oui (AGPL v3) |
| sentinel-mongodb      |                                                    | :white_check_mark: [Publique][mongodb]           | :white_check_mark: Oui (SSPL)    |
| sentinel-wappalyzer   |                                                    | :white_check_mark: [Publique][wappalyzer]        | :white_check_mark: Oui (GPL v3)  |
| sentinel-splash       |                                                    | :white_check_mark: [Publique][splash]            | :white_check_mark: Oui (BSD)     |

[cywise]: https://hub.docker.com/r/computablefacts/cywise

[mariadb]: https://hub.docker.com/_/mariadb

[cywise-performa]: https://hub.docker.com/r/computablefacts/cywise-performa

[mailpit]: https://hub.docker.com/r/axllent/mailpit

[clickhouse-server]: https://hub.docker.com/r/clickhouse/clickhouse-server

[sentinel-api]: https://hub.docker.com/r/computablefacts/sentinel-api

[sentinel-wrq]: https://hub.docker.com/r/computablefacts/sentinel-wrq

[rq-dashboard]: https://hub.docker.com/r/cjlapao/rq-dashboard/tags

[openobserve]: https://gallery.ecr.aws/zinclabs/openobserve

[redis]: https://hub.docker.com/_/redis

[mongodb]: https://hub.docker.com/_/mongo

[wappalyzer]: https://github.com/hunter-io/wappalyzer-api/pkgs/container/wappalyzer-api

[splash]: https://hub.docker.com/r/scrapinghub/splash

## Activation de certaines fonctionnalités

### CyberBuddy et CyberScribe

Pour activer CyberBuddy et CyberScribe, vous devez avoir créé une clé d'API chez [deepinfra](https://deepinfra.com/).

Vous devez ensuite mettre en place cette clé dans Cywise.

1. Arrêtez la stack avec la commande `./stack-stop.sh`.
2. Modifier le fichier `.env` pour ajouter la clé :

  ```env
  DEEPINFRA_API_KEY=<your_api_key>
  ```

3. Redémarrez la stack avec la commande `./stack-start.sh`.

### Tables

Afin de pouvoir importer des tables et interroger celles-ci en langage naturel dans CyberBuddy, vous devez avoir 2
buckets S3 chez AWS. Un bucket public, par exemple `mydomain-cywise-public`, et un bucket privé, par exemple
`mydomain-cywise-private`.

1. Arrêtez la stack avec la commande `./stack-stop.sh`.
2. Modifier le fichier `.env` pour ajouter les clés :

  ```env
  # AWS Buckets
  AWS_ACCESS_KEY_ID=AKIA...6R # Your access key
  AWS_SECRET_ACCESS_KEY=TlX...FmA # Your secret key
  AWS_DEFAULT_REGION=eu-west-3 # The region where you've created the buckets
  AWS_USE_PATH_STYLE_ENDPOINT=false
  AWS_BUCKET_PUBLIC=mydomain-towerify-public
  AWS_BUCKET_PRIVATE=mydomain-towerify-private
  ```

3. Redémarrez la stack avec la commande `./stack-start.sh`.

### Liens et CyberBuddy

CyberBuddy est capable de récupérer le contenu des liens que vous mettez dans
une conversation avec lui.

Pour que cela fonctionne, vous devez fournir votre clé d'API de scrapfly **ou**
de scraperapi dans le fichier `.env`.

1. Arrêtez la stack avec la commande `./stack-stop.sh`.
2. Modifier le fichier `.env` pour ajouter la clé :

Pour scrapfly :

  ```env
  SCRAPFLY_API_KEY=<your_scrapfly_api_key>
  ```

Pour scraperapi :

  ```env
  SCRAPERAPI_API_KEY=<your_scraperapi_api_key>
  ```

3. Redémarrez la stack avec la commande `./stack-start.sh`.

## Travaux en cours

Plusieurs fonctionnalités ne sont pas utilisables avec la stack pour le moment. Nous travaillons à les rendre
accessibles. Notamment :

- [ ] l'envoi de mails à CyberBuddy (intégration IMAP)
- [ ] le déploiement de l'agent sur un serveur (la stack étant accessible sur localhost par défaut)
- [x] l'import de documents depuis **Gestion des données > Documents**

Si vous avez des difficultés à faire fonctionner notre
stack, [ouvrez un ticket](https://github.com/computablefacts/cywise/issues/new).

## Utilisation

Après démarrage de la stack, vous pouvez accéder à l'interface en utilisant les paramètres :

- URL : [http://localhost:17801](http://localhost:17801)
- login : demo@mydomain.com
- mot de passe : DemoPass2026

Pour l'utilisation de Cywise, reportez-vous à la documentation dont le lien se trouve ci-dessous.

# Liens utiles

- Vous pouvez accéder <a href="https://www.cywise.io" target="_blank">ici</a> à la version SaaS de Cywise.
- Vous pouvez accéder <a href="https://www.cywise.io/changelog" target="_blank">ici</a> au changelog de la version SaaS
  de Cywise.
- Vous pouvez
  accéder <a href="https://www.youtube.com/playlist?list=PLu1f_CSMJyoIf6yx9CUI2oWLZQhi5P8QO" target="_blank">ici</a> à
  quelques vidéos de démonstration.
- Vous pouvez
  accéder <a href="https://computablefacts.notion.site/Guide-utilisateur-2160a1f68ecc80689497e7dd5c07a817?source=copy_link" target="_blank">
  ici</a> à la documentation de l'interface utilisateur.
- Vous pouvez accéder <a href="https://app.cywise.io/api/v2/private/docs" target="_blank">ici</a> à la documentation de
  l'API.
