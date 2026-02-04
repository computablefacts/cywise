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

### CyberScribe

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**Rédaction assistée de vos documents cyber.** Besoin de créer une Charte Informatique ou une Politique de Sécurité des
Systèmes d'Information (PSSI) ? CyberScribe, notre éditeur intelligent, vous accompagne pas à pas pour rédiger des
documents clairs, conformes et adaptés à vos besoins, grâce à l'intelligence artificielle.

## Divers

### Single Sign-On (SSO)

:white_check_mark: auto-hébergé :white_check_mark: SaaS

**En 2026, le SSO n'est plus une option.** Cywise intègre un module SSO moderne, compatible avec les standards du
marché (OAuth 2.0, SAML, OpenID Connect), pour vous permettre de contrôler les accès de manière unifiée.

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

Assurez-vous que nos scripts de gestion de la stack sont bien exécutables en
lançant la commande :

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
> Ce script va créer un fichier `.env` avec les paramètres par défaut 
> (principalement issus de `.env.example`) puis démarrer la stack.
>
> Comptez environ 15 minutes lors du premier démarrage.

## Utilisation de Cywise

Après démarrage de la stack, vous pouvez accéder à l'interface en utilisant
les paramètres :

- URL : [http://localhost:17801](http://localhost:17801)
- login : demo@mydomain.com
- mot de passe : DemoPass2026

### Protéger ce qui est accessible sur internet

#### Scanner de vulnérabilités

Depuis le [tableau de bord](http://localhost:17801/dashboard), vous pouvez
ajouter un domaine ou une adresse IP et demander à Cywise de la surveiller.

Cywise va alors scanner cet actif pour rechercher les éventuelles
vulnérabilités.

Comptez environ 5 minutes avant de voir le résultat du scan.

Vous pouvez rafraichir la page du tableau de bord pour voir combien de
vulnérabilités Cywise a découvert. Elles sont réparties dans les 3 catégories
de criticité Haute, Moyenne et Basse.

Vous pouvez également retrouver la liste des vulnérabilités en cliquant sur le
menu **Timelines > Vulnérabilités**.

> [!WARNING]
> Vous ne devez scanner que des domaines ou des adresses IP dont vous êtes
> le propriétaire.

### Protéger les actifs internes de l'entreprise

Afin que Cywise reçoivent les événements des actifs que vous voulez protéger,
vous devez exécuter, avec votre compte administrateur, la commande affichée
sur le tableau de bord dans l'encadré
**Vous souhaitez protéger un nouveau serveur ?**.

Copiez la commande après avoir choisi l'OS de votre machine, Linux ou Windows,
puis exécutez la.

Par exemple, pour une machine sous linux, la commande ressemble à :

```bash
curl -s "http://localhost:17801/setup/script?api_token=1|cmxxx75&server_ip=$(curl -s ipinfo.io | jq -r '.ip')&server_name=$(hostname)" | bash
```

> [!NOTE]
> Dans le cadre de la démo, Cywise est accessible uniquement sur localhost
> donc le seul serveur que vous pouvez protéger est la machine sur laquelle
> vous avez démarré la stack.

#### Hardening

En cours de rédaction.

#### Agents

Quelques minutes après avoir fait la commande sur votre serveur, vous devriez
voir apparaître des événements en cliquant sur le menu **Timelines > Évènements**.

Si certains événements semblent suspects à Cywise, d'après ses règles expertes,
vous pourrez les voir en cliquant sur le menu 
**Timelines > Indicateurs de compromission**.

#### Métriques

Vous pouvez accéder aux métriques en cliquant sur le menu **Timelines > Métriques**.

### Accompagner les utilisateurs

Pour activer CyberBuddy et CyberScribe, vous devez avoir une clé API chez 
[deepinfra](https://deepinfra.com/).

Vous devez mettre en place cette clé dans Cywise.

1. Arrêtez la stack avec la commande `./stack-stop.sh`.
2. Modifier le fichier `.env` pour ajouter la clé :
  ```env
  DEEPINFRA_API_KEY=<your_api_key>
  ```
3. Redémarrez la stack avec la commande `./stack-start.sh`.

#### CyberBuddy

Vous pouvez accéder à CyberBuddy en cliquant sur le menu **CyberBuddy**.

Vous pouvez lui poser des questions sur vos assets, les vulnérabilités 
détectées, etc.

Exemples de questions :

- Dis moi quel est mon serveur le plus vulnérable ?
- Quelle vulnérabilité dois-je corriger en priorité ?


#### CyberScribe

Vous pouvez accéder à CyberScribe en cliquant sur le menu **CyberScribe**.

Choisissez un modèle dans la liste puis commencez à écrire votre charte
informatique ou votre PSSI avec son aide.

### Divers

#### Single Sign-On (SSO)

En cours de rédaction.

## Arrêt

Vous pouvez arrêter la stack grâce à la commande :

```bash
./stack-stop.sh
```

## Suppression

Vous pouvez supprimer l'intégralité de la stack y compris toutes les données
associées grâce à la commande :

```bash
./stack-destroy.sh
```

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
