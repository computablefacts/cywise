@php
    $isEnglish = $locale === 'en';
    $homeRoute = $isEnglish ? 'website.en.home' : 'home';
    $routePrefix = $isEnglish ? 'website.en.' : 'website.';
@endphp

<footer>
    <div class="container-fluid shell">
        <div class="footer-top">
            <a class="site-brand-lockup footer-brand-lockup" href="{{ route($homeRoute) }}">
                <img
                    alt=""
                    class="cw-picto"
                    src="{{ asset('cywise/website-v2/assets/cywise-picto-riso.png') }}"
                >
                <span class="cw-wordmark">Cywise</span>
            </a>
            <div class="mono">{{ $isEnglish ? 'CYBERSECURITY FOR EVERYONE.' : 'LA CYBERSÉCURITÉ POUR TOUS.' }}</div>
        </div>

        <div class="footer-grid">
            <div>
                <strong>{{ $isEnglish ? 'SOLUTIONS' : 'SOLUTIONS' }}</strong>
                <a href="{{ route($routePrefix . 'solutions.attack-surface') }}">{{ $isEnglish ? 'Attack surface' : 'Surface d’attaque' }}</a>
                <a href="{{ route($routePrefix . 'solutions.vulnerability-management') }}">{{ $isEnglish ? 'Vulnerabilities' : 'Vulnérabilités' }}</a>
                <a href="{{ route($routePrefix . 'solutions.cyberbuddy') }}">CyberBuddy</a>
                <a href="{{ route($routePrefix . 'solutions.pentest') }}">Pentest</a>
            </div>
            <div>
                <strong>{{ $isEnglish ? 'FOR WHOM' : 'POUR QUI' }}</strong>
                <a href="{{ route($routePrefix . 'audiences.smbs') }}">{{ $isEnglish ? 'SMBs' : 'PME' }}</a>
                <a href="{{ route($routePrefix . 'audiences.it-teams') }}">{{ $isEnglish ? 'IT teams' : 'Équipes IT' }}</a>
                <a href="{{ route($routePrefix . 'audiences.cisos') }}">{{ $isEnglish ? 'CISOs' : 'RSSI' }}</a>
                <a href="{{ route($routePrefix . 'audiences.msps') }}">MSP</a>
            </div>
            <div>
                <strong>{{ $isEnglish ? 'RESOURCES' : 'RESSOURCES' }}</strong>
                <a href="{{ route($isEnglish ? 'blog.en' : 'blog') }}">Blog</a>
                <a href="{{ route($routePrefix . 'use-cases.index') }}">{{ $isEnglish ? 'Use cases' : 'Cas d’usage' }}</a>
                <a href="{{ route($isEnglish ? 'website.en.pricing' : 'pricing') }}">{{ $isEnglish ? 'Pricing' : 'Tarifs' }}</a>
            </div>
            <div>
                <strong>CYWISE</strong>
                <a href="{{ route($homeRoute) }}">{{ $isEnglish ? 'Home' : 'Accueil' }}</a>
                <a href="{{ route($homeRoute) }}#cta">Contact</a>
                <a href="{{ route('privacy-policy') }}">{{ $isEnglish ? 'Privacy' : 'Confidentialité' }}</a>
            </div>
        </div>

        <div class="footer-bottom mono">
            <span>© {{ now()->year }} CYWISE</span>
            <span>CYWISE / {{ $isEnglish ? 'CYBERSECURITY FOR EVERYONE' : 'LA CYBERSÉCURITÉ POUR TOUS' }}</span>
        </div>
    </div>
</footer>
