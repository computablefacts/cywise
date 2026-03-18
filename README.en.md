<p align="center">
    <a href="https://cywise.io" target="_blank">
        <img src="/public/cywise/img/cywise-catchphrase-fr.png" width="300">
    </a>
</p>
<p align="center">
    <a href="https://github.com/computablefacts/cywise/blob/main/README.md" target="_blank">
        <img src="https://img.shields.io/badge/lang-fr-lightyellow.svg" alt="fr">
    </a>
    <a href="https://github.com/computablefacts/cywise/blob/main/README.en.md" target="_blank">
        <img src="https://img.shields.io/badge/lang-en-lightyellow.svg" alt="en">
    </a>
    <a href="https://github.com/computablefacts/cywise/releases" target="_blank">
        <img src="https://img.shields.io/github/v/release/computablefacts/cywise" alt="Latest Stable Version">
    </a>
    <a href="https://github.com/computablefacts/cywise/actions" target="_blank">
        <img src="https://github.com/computablefacts/cywise/actions/workflows/tests.yml/badge.svg" alt="Build Status">
    </a>
    <a href="https://github.com/computablefacts/cywise/commits" target="_blank">
        <img src="https://img.shields.io/github/commit-activity/y/computablefacts/cywise.svg" alt="GitHub commit activity">
    </a>
    <a href="https://github.com/computablefacts/cywise/graphs/contributors" target="_blank">
        <img src="https://img.shields.io/github/contributors-anon/computablefacts/cywise.svg" alt="GitHub contributors">
    </a>
    <a href="https://github.com/computablefacts/cywise/LICENSE.md" target="_blank">
        <img src="https://img.shields.io/badge/license-AGPLv3-green" alt="License">
    </a>
</p>
<p align="center">
  <em>
    Secure in minutes your websites (showcase, e-commerce, customer portals, ...) and your infrastructure exposed on the Internet (VPN, extranet, servers, ...)
  </em>
</p>

---

> This README is a machine translation of the [original French version](https://github.com/computablefacts/cywise/blob/main/README.md).

---

# Why an open source and self-hosted version of Cywise?

At Cywise, we believe that cybersecurity must be accessible, transparent, and adaptable to all environments, including the most sensitive ones.

The **self-hosted version** of Cywise allows you to scan and secure your **internal network** (local servers, non-exposed equipment), whereas the SaaS version is limited to infrastructures accessible from the Internet.

This solution is ideal for companies wishing to strengthen the security of their complete infrastructure, including **segments isolated from the web**, while keeping total control over their data and environment.

# Features

Cywise integrates all essential features for SMEs.

## Protect what is accessible on the internet

### Vulnerability Scanner

:white_check_mark: self-hosted :white_check_mark: SaaS

**Proactive monitoring and automated remediation.** This module continuously analyzes your infrastructure to detect more than 50,000 vulnerabilities. Flaws are classified by criticality level, and detailed corrective actions are proposed. An automatic verification confirms the application of patches, ensuring optimal and up-to-date protection.

### Data Leak Monitoring

:x: self-hosted :white_check_mark: SaaS

**Prevention of attacks through active monitoring.** Anticipate risks by analyzing 10 million leaked credentials daily, with a history of 130 billion credentials (emails, passwords, domains, etc.). Detect compromised credentials and sites vulnerable to identity theft, then receive automatic alerts to act before any malicious exploitation.

### Intelligent Honeypots

:x: self-hosted :white_check_mark: SaaS

**Trap attackers before they strike.** Cywise deploys and maintains digital decoys designed to attract cybercriminals and reveal their attacks in real-time. These honeypots allow you to identify active threats, determine if your company is specifically targeted, and assess the risks incurred by your real infrastructure. A discreet solution to understand attacker tactics before they touch your critical systems.

## Protect the company's internal assets

### Hardening

:white_check_mark: self-hosted :white_check_mark: SaaS

**Strengthen the security of your Linux and Windows servers.** Optimize the configuration of your machines through a complete audit, the application of recognized standards, and the creation of custom rules. A mastered configuration reduces vulnerabilities and improves the resilience of your infrastructure against cyberattacks.

> [!NOTE]
> Machine configuration verification is performed using [OSSEC](https://www.ossec.net/) rules.

### Agents

:white_check_mark: self-hosted :white_check_mark: SaaS

**Monitor and detect suspicious activities in real-time.** Protect your Linux and Windows servers with proactive detection of abnormal behavior. Benefit from expert rules or create your own to identify threats as soon as they appear. Early detection limits the risk of compromise and strengthens the security of your environment.

> [!NOTE]
> Security event collection is performed with [Osquery](https://osquery.io/).

### Metrics

:white_check_mark: self-hosted :white_check_mark: SaaS

**Ensure the availability and performance of your servers.** Collect and analyze essential metrics (CPU, storage, resources) to guarantee the availability of your applications. Anticipate capacity needs and maintain a stable infrastructure, a key element for robust cybersecurity.

> [!NOTE]
> System metrics collection is performed with [Performa](https://github.com/jhuckaby/performa).

## Accompany users

### CyberBuddy

:white_check_mark: self-hosted :white_check_mark: SaaS

**Your cybersecurity expert, available 24/7.** With Cywise, ask all your questions about cybersecurity or your scan results. CyberBuddy, our virtual assistant, guides you in real-time based on verified knowledge bases and recognized best practices. Expertise accessible wherever you are.

> [!WARNING]
> To enable this feature in the self-hosted version, you will need to provide your own [DeepInfra](https://deepinfra.com/) API key.

### CyberScribe

:white_check_mark: self-hosted :white_check_mark: SaaS

**Assisted drafting of your cyber documents.** Need to create an IT Charter or an Information Systems Security Policy (ISSP)? CyberScribe, our intelligent editor, accompanies you step-by-step to write clear, compliant, and adapted documents, thanks to artificial intelligence.

> [!WARNING]
> To enable this feature in the self-hosted version, you will need to provide your own [DeepInfra](https://deepinfra.com/) API key.

### Telegram & WhatsApp

:white_check_mark: self-hosted :white_check_mark: SaaS

**Stay informed, wherever you are.** Cywise allows you to interact with the platform in natural language using [Telegram](https://telegram.org) and [WhatsApp](https://www.whatsapp.com/) messaging.

> [!WARNING]
> To enable this feature in the self-hosted version, you will need to provide your own [DeepInfra](https://deepinfra.com/) API key.

## Miscellaneous

### Document base

:white_check_mark: self-hosted :white_check_mark: SaaS

**Integrate your document base (IT Charter, ISSP, etc.) with CyberBuddy.** Provide your teams with instant and intuitive access to information. They just need to ask a question in natural language, like "CyberBuddy, what are the remote work rules in our ISSP?", to get a precise answer, extracted directly from your internal resources. A simple and effective way to disseminate your best practices and facilitate access to information on a daily basis.

### Single Sign-On (SSO)

:white_check_mark: self-hosted :white_check_mark: SaaS

**In 2026, SSO is no longer an option.** Cywise integrates a modern SSO module, compatible with market standards (OAuth 2.0, SAML, OpenID Connect), to allow you to control access in a unified manner.

# How it works

## CyberBuddy

CyberBuddy is at the heart of the Cywise experience. It acts as an intelligent orchestrator capable of understanding your requests in natural language and answering them by querying different data sources.

```text
   USER                  MESSAGING                CYWISE WEBHOOK                   CYBERBUDDY (AI)
   +----------+        +-------------+        +-------------------+      +--------------------------------+
   |  Mobile  | <----> | - Telegram  | <----> |  - Validation     | <--> |  - Orchestrator               |
   |   App    |        | - WhatsApp  |        |  - Thread Mapping |      |  - AgentSquad                  |
   +----------+        +-------------+        +-------------------+      |  - Thought/Action/Observation  |
                                                                         +---------------+----------------+
                                                                                         |
         +------------------------+------------------------+-----------------------------+--+
         |                        |                        |                                |
         v                        v                        v                                v
   [ STRUCTURED DATA ]        [ KNOWLEDGE BASE ]         [ LOCAL ACTIONS ]               [ REMOTE ACTIONS ]
   
   +-----------------------+  +-----------------------+  +----------------------------+  +---------------------------------+
   |   ANALYTICAL TABLES   |  |     HYBRID SEARCH     |  |   INTERNAL JSON-RPC APIS   |  |     EXTERNAL JSON-RPC APIS      |
   |      (ClickHouse)     |  |    (MariaDB / RAG)    |  |       (Annotations)        |  |          (HTTP Request)         |
   +-----------------------+  +-----------------------+  +-------------+--------------+  +---------------------------------+
   | - Data Tables         |  | - Memos               |                |                 | - URL & Headers                 |
   | - SQL Generation      |  | - Collections         |                v                 | - JSON Payload                  |
   | - TSV Results         |  | - Documents / Chunks  |  +----------------------------+  | - JSON Response                 |
   |                       |  | - Vectors (Cosine)    |  |    JSON-RPC PROCEDURES     |  +---------------------------------+
   +-----------------------+  +-----------------------+  +----------------------------+ 
                                                         | - #[RpcMethod]             | 
                                                         | - JsonRequest Payload      | 
                                                         | - Response array           |
                                                         +----------------------------+
```

The diagram above illustrates the request processing flow. For example, if you send "monitor www.example.com" in your messaging client, CyberBuddy:

- identifies your intent;
- chooses the appropriate action;
- calls the JSON-RPC procedure associated with this action with the provided domain;
- confirms the setup of monitoring after receiving the response from the procedure.

The key components of this architecture are:

- **CyberBuddy.** From your natural language request, plans the tools to call to achieve your goal. For example:
    - "What are my priorities this morning?"
- **Structured data.** Uses ClickHouse to perform complex analyses and statistical calculations on large volumes of technical data (data leaks or imports of your tabular data). For example:
    - "What are my leaked credentials?"
- **Knowledge base.** RAG (Retrieval Augmented Generation) system that searches through your documents (IT Charters, ISSP, PDF) and your personal notes (memos) by semantic search to return factual and sourced answers. For example:
    - "How to isolate a compromised server according to our procedures?"
- **Local actions.** Allow direct control of Cywise features via its JSON-RPC APIs. For example:
    - "Monitor www.example.com"
    - "Send me a security report every Wednesday at 11 AM."
- **Remote actions.** Allow Cywise to interact with third-party systems (SIEM, ticketing tools, Cloud) by consuming their JSON-RPC APIs. For example:
    - "Open a Jira ticket for this vulnerability"
    - "List my active instances on AWS."

## SOC Operator

The SOC Operator is an agent specialized in analyzing security events. It acts as a level 1 analyst capable of processing large volumes of technical events to extract weak signals and abnormal behavior.

```text
   USER SERVER                           SOC OPERATOR (AI)                           RESULTS
   +-----------------+      +--------------------------------------------+      +-------------------+
   |  - Events       |      |  - Collection & Compression                |      |  - Verdict        |
   |  - IoC          | ---> |  - Context (Memos)                         | ---> |  - Criticality    |
   +-----------------+      |  - Intent Analysis (AI)                    |      |  - Remediation    |
                            +--------------------------------------------+      +-------------------+
```

Unlike CyberBuddy which answers user questions, the SOC Operator works autonomously to:

- **Analyze security events.** It processes system logs collected by Osquery on your Linux and Windows servers.
- **Take context into account.** It uses your personal notes and internal procedures (memos) to adapt its analysis to your specific environment.
- **Qualify the threat.** It issues a verdict (NORMAL, SUSPECT, ABNORMAL) accompanied by a confidence score and a detailed justification.
- **Suggest actions.** In case of suspicious detection, it immediately recommends the first remediation steps.

## Memos

Memos are personal notes or specific procedures that you can create directly in the Cywise interface. They constitute the "local context" essential for transforming a generic AI into an assistant truly aware of your constraints.

Their importance is capital because they allow to:

- **Personalize the analysis.** By indicating that such a server is a test server, the SOC Operator can qualify certain behaviors as "normal" while they would be judged "suspicious" elsewhere.
- **Capitalize on your know-how.** By filling in your on-call procedures or your emergency contacts, CyberBuddy can restore them immediately in case of crisis.
- **Reduce false positives.** The more the AI knows about your environment (time slots, administration tools used), the more accurate its diagnosis is.

# Installation

## Prerequisites

- A computer running Linux
- Have [Docker](https://www.docker.com/) installed
- Have [git](https://git-scm.com/) installed

## Clone this repository

Retrieve our code repository using the command:

```bash
git clone https://github.com/computablefacts/cywise.git
```

Then go to the newly created directory:

```bash
cd cywise
```

> [!NOTE]
> All subsequent commands work if they are launched from this directory.

Make sure our stack management scripts are executable by running the command:

```bash
chmod +x ./stack*
```

## Startup

Our application consists of several Docker services assembled using a [docker compose](https://docs.docker.com/compose) stack.

You can start the stack with the command:

```bash
./stack-start.sh
```

> [!NOTE]
> This script will create a `.env` file with default parameters (mainly from `.env.example`) and then start the stack.
>
> Allow about 15 minutes for the first startup.

## Shutdown

You can stop the stack with the command:

```bash
./stack-stop.sh
```

## Deletion

You can delete the entire stack including all associated data with the command:

```bash
./stack-destroy.sh
```

## Docker Images used

| Service               | Local                                              | Image                                            | Open Source                      |
|:----------------------|:---------------------------------------------------|:-------------------------------------------------|:---------------------------------|
| app                   | [http://localhost:17801/](http://localhost:17801/) | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| scheduler             |                                                    | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| queue                 |                                                    | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| queue-low             |                                                    | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| queue-medium          |                                                    | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| queue-critical        |                                                    | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| queue-scout           |                                                    | :white_check_mark: [Public][cywise]              | :white_check_mark: Yes (AGPL v3) |
| mariadb               |                                                    | :white_check_mark: [Public][mariadb]             | :white_check_mark: Yes           |
| performa              | [http://localhost:17802/](http://localhost:17802/) | :white_check_mark: [Public][cywise-performa]     | :white_check_mark: Yes           |
| mailpit               | [http://localhost:17803/](http://localhost:17803/) | :white_check_mark: [Public][mailpit]             | :white_check_mark: Yes           |
| clickhouse-server     |                                                    | :white_check_mark: [Public][clickhouse-server]   | :white_check_mark: Yes           |
| sentinel-api          |                                                    | :white_check_mark: [Public][sentinel-api]        | :x: No (coming soon)             |
| sentinel-wrq          |                                                    | :white_check_mark: [Public][sentinel-wrq]        | :x: No (coming soon)             |
| sentinel-wrq-nuclei   |                                                    | :white_check_mark: [Public][sentinel-wrq]        | :x: No (coming soon)             |
| sentinel-rq-dashboard | [http://localhost:17804/](http://localhost:17804/) | :white_check_mark: [Public][rq-dashboard]        | :white_check_mark: Yes (MIT)     |
| sentinel-openobserve  | [http://localhost:17805/](http://localhost:17805/) | :white_check_mark: [Public][openobserve]         | :white_check_mark: Yes (AGPL v3) |
| sentinel-redis        |                                                    | :white_check_mark: [Public][redis]               | :white_check_mark: Yes (AGPL v3) |
| sentinel-mongodb      |                                                    | :white_check_mark: [Public][mongodb]             | :white_check_mark: Yes (SSPL)    |
| sentinel-wappalyzer   |                                                    | :white_check_mark: [Public][wappalyzer]          | :white_check_mark: Yes (GPL v3)  |
| sentinel-splash       |                                                    | :white_check_mark: [Public][splash]              | :white_check_mark: Yes (BSD)     |

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

## Activating certain features

### CyberBuddy and CyberScribe

To activate CyberBuddy and CyberScribe, you must have created an API key at [DeepInfra](https://deepinfra.com/).

You must then set up this key in Cywise.

1. Stop the stack with the command `./stack-stop.sh`.
2. Modify the `.env` file to add the key:

  ```env
  DEEPINFRA_API_KEY=<your_api_key>
  ```

3. Restart the stack with the command `./stack-start.sh`.

### Tables

In order to be able to import tables and query them in natural language in CyberBuddy, you must have 2 S3 buckets at AWS. A public bucket, for example `mydomain-cywise-public`, and a private bucket, for example `mydomain-cywise-private`.

1. Stop the stack with the command `./stack-stop.sh`.
2. Modify the `.env` file to add the keys:

  ```env
  # AWS Buckets
  AWS_ACCESS_KEY_ID=AKIA...6R # Your access key
  AWS_SECRET_ACCESS_KEY=TlX...FmA # Your secret key
  AWS_DEFAULT_REGION=eu-west-3 # The region where you've created the buckets
  AWS_USE_PATH_STYLE_ENDPOINT=false
  AWS_BUCKET_PUBLIC=mydomain-towerify-public
  AWS_BUCKET_PRIVATE=mydomain-towerify-private
  ```

3. Restart the stack with the command `./stack-start.sh`.

### Links and CyberBuddy

CyberBuddy is able to retrieve the content of the links you put in a conversation with him.

For this to work, you must provide your scrapfly **or** scraperapi API key in the `.env` file.

1. Stop the stack with the command `./stack-stop.sh`.
2. Modify the `.env` file to add the key:

For scrapfly:

  ```env
  SCRAPFLY_API_KEY=<your_scrapfly_api_key>
  ```

For scraperapi:

  ```env
  SCRAPERAPI_API_KEY=<your_scraperapi_api_key>
  ```

3. Restart the stack with the command `./stack-start.sh`.

## Work in progress

Several features are not usable with the stack at the moment. We are working on making them accessible. Notably:

- [ ] sending emails to CyberBuddy (IMAP integration)
- [ ] deploying the agent on a server (as the stack is accessible on localhost by default)
- [x] importing documents from **Data Management > Documents**

If you have difficulties making our stack work, [open an issue](https://github.com/computablefacts/cywise/issues/new).

## Usage

After starting the stack, you can access the interface using the parameters:

- URL: [http://localhost:17801](http://localhost:17801)
- login: demo@mydomain.com
- password: DemoPass2026

For information on using Cywise, refer to the documentation linked below.

# Useful links

- You can access the SaaS version of Cywise <a href="https://www.cywise.io" target="_blank">here</a>.
- You can access the changelog of the SaaS version of Cywise <a href="https://www.cywise.io/changelog" target="_blank">here</a>.
- You can access some demonstration videos <a href="https://www.youtube.com/playlist?list=PLu1f_CSMJyoIf6yx9CUI2oWLZQhi5P8QO" target="_blank">here</a>.
- You can access the user interface documentation <a href="https://computablefacts.notion.site/Guide-utilisateur-2160a1f68ecc80689497e7dd5c07a817?source=copy_link" target="_blank">here</a>.
- You can access the API documentation <a href="https://app.cywise.io/api/v2/private/docs" target="_blank">here</a>.
