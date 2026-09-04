@php
    $isEnglish = $locale === 'en';
    $homeRoute = $isEnglish ? 'website.en.home' : 'home';
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
    $languageUrl = $languageUrl ?: route($isEnglish ? 'home' : 'website.en.home');
@endphp

<header class="site-header sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid shell">
            <a class="site-brand-lockup" href="{{ route($homeRoute) }}">
                <img
                    alt=""
                    class="cw-picto"
                    src="{{ asset('cywise/website-v2/assets/cywise-picto-riso.png') }}"
                >
                <span class="cw-wordmark">Cywise</span>
            </a>

            <button
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="{{ $isEnglish ? 'Open navigation' : 'Ouvrir la navigation' }}"
                class="navbar-toggler brutal-icon"
                data-bs-target="#mainNav"
                data-bs-toggle="collapse"
                type="button"
            >
                <span>☰</span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto gap-lg-1">
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle"
                            data-bs-toggle="dropdown"
                            href="{{ route($routePrefix . 'solutions.index') }}"
                        >
                            Solutions
                        </a>
                        <ul class="dropdown-menu brutal-menu">
                            <li>
                                <a class="dropdown-item fw-bold" href="{{ route($routePrefix . 'solutions.index') }}">
                                    {{ $isEnglish ? 'All solutions' : 'Toutes les solutions' }}
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'solutions.attack-surface') }}">{{ $isEnglish ? 'Attack surface' : 'Surface d’attaque' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'solutions.vulnerability-management') }}">{{ $isEnglish ? 'Vulnerability management' : 'Gestion des vulnérabilités' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'solutions.credential-monitoring') }}">{{ $isEnglish ? 'Credential monitoring' : 'Surveillance des identifiants' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'solutions.cyberbuddy') }}">CyberBuddy</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'solutions.pssi') }}">{{ $isEnglish ? 'Security policy' : 'PSSI' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'solutions.pentest') }}">Pentest</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle"
                            data-bs-toggle="dropdown"
                            href="{{ route($routePrefix . 'audiences.index') }}"
                        >
                            {{ $isEnglish ? 'For whom' : 'Pour qui' }}
                        </a>
                        <ul class="dropdown-menu brutal-menu">
                            <li>
                                <a class="dropdown-item fw-bold" href="{{ route($routePrefix . 'audiences.index') }}">
                                    {{ $isEnglish ? 'All audiences' : 'Tous les profils' }}
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'audiences.smbs') }}">{{ $isEnglish ? 'SMBs' : 'PME' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'audiences.startups') }}">Startups</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'audiences.it-teams') }}">{{ $isEnglish ? 'IT teams' : 'Équipes IT' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'audiences.cisos') }}">{{ $isEnglish ? 'CISOs' : 'RSSI' }}</a></li>
                            <li><a class="dropdown-item" href="{{ route($routePrefix . 'audiences.msps') }}">MSP</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route($routePrefix . 'use-cases.index') }}">
                            {{ $isEnglish ? 'Use cases' : 'Cas d’usage' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route($isEnglish ? 'blog.en' : 'blog') }}">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route($isEnglish ? 'website.en.pricing' : 'pricing') }}">
                            {{ $isEnglish ? 'Pricing' : 'Tarifs' }}
                        </a>
                    </li>
                </ul>

                <div class="d-flex gap-2 align-items-center header-actions">
                    <div aria-label="{{ $isEnglish ? 'Language selector' : 'Sélecteur de langue' }}" class="lang-switcher">
                        <a class="lang-option {{ $isEnglish ? '' : 'active' }}" href="{{ $isEnglish ? $languageUrl : url()->current() }}" lang="fr">FR</a>
                        <span class="lang-sep">/</span>
                        <a class="lang-option {{ $isEnglish ? 'active' : '' }}" href="{{ $isEnglish ? url()->current() : $languageUrl }}" lang="en">EN</a>
                    </div>
                    <a class="btn btn-ghost-brutal" href="{{ route('login') }}">
                        {{ $isEnglish ? 'Sign in' : 'Se connecter' }}
                    </a>
                    <a class="btn btn-acid" href="{{ route('register') }}">
                        {{ $isEnglish ? 'GET STARTED →' : 'COMMENCER →' }}
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>
