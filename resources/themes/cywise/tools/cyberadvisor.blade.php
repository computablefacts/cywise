<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name') }}</title>

  <!-- FastBootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/fastbootstrap@2.2.0/dist/css/fastbootstrap.min.css" rel="stylesheet"
        integrity="sha256-V6lu+OdYNKTKTsVFBuQsyIlDiRWiOmtC8VQ8Lzdm2i4=" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
          crossorigin="anonymous"></script>

  <style>

    :root {
      --color-cywise: #ffa500;
      --color-cywise-hover: #e08e00;
      --color-primary: #000000;
      --color-secondary: #47627F;
      --color-green: #4bd28f;
      --color-orange: #ffaa00;
      --color-red: #ff4d4d;
      --spacing-large: 20px;
      --spacing-medium: 10px;
      --font-size-large: 24px;
      --font-size-medium: 14px;
      --font-size-small: 12px;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: poppins, sans-serif;
      background-color: rgba(0, 0, 0, 0.05);
    }

    /** TEXT */

    h1 {
      font-weight: bold;
      font-size: var(--font-size-large);
      margin-top: var(--spacing-large);
    }

    h2 {
      font-weight: bold;
      font-size: var(--font-size-medium);
      margin-top: var(--spacing-medium);
    }

    p {
      font-weight: normal;
      font-size: var(--font-size-medium);
      color: var(--color-secondary);
    }

    /** INPUTS */

    input[type="text"], input[type="email"] {
      width: 100%;
      padding: var(--spacing-medium);
      margin-bottom: var(--spacing-large);
      border: 2px solid black;
    }

    input[type=checkbox] {
      appearance: none;
      width: 16px;
      height: 16px;
      border: 2px solid var(--color-cywise);
      border-radius: 2px;
      position: relative;
      cursor: pointer;
      padding: 6px;
    }

    input[type=checkbox]:checked {
      background-color: var(--color-cywise);
    }

    input[type=checkbox]:checked::after {
      content: '✓'; /* ou '✔' */
      position: absolute;
      color: white;
      font-size: 16px;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
    }

    /** BUTTONS */

    button {
      width: 100%;
      padding: var(--spacing-medium);
      background-color: var(--color-cywise);
      color: white;
      border: none;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover {
      background-color: var(--color-cywise-hover);
    }

    button.disabled {
      pointer-events: none;
    }

    .button-group {
      display: flex;
      justify-content: space-between;
    }

    .back-button {
      border: 2px solid var(--color-cywise);
      background-color: white;
      color: var(--color-cywise);
    }

    .back-button:hover {
      border: 2px solid var(--color-cywise-hover);
      background-color: var(--color-cywise-hover);
      color: white;
    }

    .next-button-300p {
      width: 300%;
      margin-left: var(--spacing-large);
    }

    .next-button-100p {
      margin-bottom: var(--spacing-medium);
    }

    /** LISTS */

    .list {
      color: var(--color-primary);
      margin-bottom: var(--spacing-large);
      max-height: 50vh;
      overflow: hidden;
      overflow-y: scroll;
    }

    .list-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--spacing-medium);
      border: 2px solid var(--color-secondary);
      margin-bottom: var(--spacing-medium);
      border-radius: 4px;
    }

    .list-item input[type="checkbox"] {
      margin-right: var(--spacing-medium);
      accent-color: var(--color-cywise);
    }

    .list-item label {
      flex-grow: 1;
    }

    .list-item a {
      text-decoration: none;
      align-items: center;
      display: flex;
    }

    /** WRAPPER */

    .outer-wrapper {
      padding: 30px;
      border: 1px solid lightgray;
      border-radius: 8px;
      box-shadow: 0 0 0 rgba(0, 0, 0, 0.5);
      background-color: white;
    }

    .inner-wrapper {
      width: 550px;
    }

  </style>
</head>
<body>
<div class="outer-wrapper">
  <div class="inner-wrapper">
    <h1 style="margin-top: var(--spacing-medium);margin-bottom: 1rem;">Découvrez CyberBuddy, votre partenaire
      intelligent en cybersécurité !</h1>
    <p>CyberBuddy est votre assistant dédié en cybersécurité, conçu pour vous accompagner au quotidien. Pré-packagé avec
      des référentiels de confiance tels que l'ANSSI, CyberBuddy vous offre un accès immédiat à des ressources fiables
      et constamment mises à jour.</p>
    <p>Vous pouvez <b>interroger CyberBuddy</b> en envoyant vos questions par email à <b
        style="color: var(--color-cywise)">cyberbuddy@cywise.io</b>, et obtenir des réponses précises et rapides.</p>
    <p>Pour <b>enrichir les connaissances de CyberBuddy</b>, vous avez la possibilité de stocker des informations liées
      à la cybersécurité dans sa mémoire. Il vous suffit d'envoyer un email à <b style="color: var(--color-cywise)">memex@cywise.io</b>
      contenant les informations à stocker, soit dans le corps de l'email soit en pièce-jointe au format PDF.</p>
    <p>Si vous voulez poursuivre vos échanges en mode chat, ajouter des référentiels, gérer les éléments que vous avez
      stocké via memex@cywise.io connectez-vous à Cywise.</p>
    <p>Avec CyberBuddy, bénéficiez d’un assistant intelligent et évolutif, toujours à vos côtés pour renforcer votre
      sécurité.</p>
  </div>
</div>
@include('theme::iframes._analytics')
</body>
</html>
