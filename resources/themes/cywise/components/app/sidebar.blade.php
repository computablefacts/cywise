@php
$user = \Auth::user();
@endphp
{{--
  App navigation, grouped by user intent:

    dashboard, inventory, vulnerabilities, leaks, events, notes, metrics
    CONFIGURATION  agent, data management, administration
    ─────────────
    documentation, changelog

  CyberBuddy is not listed: it has a highlighted button in the topbar.
  Sections collapse (x-app.sidebar-section) so the menu fits without scrolling.
  Off-canvas below the lg breakpoint (opened by the topbar "open-sidebar" event).
--}}
<div x-data="{ sidebarOpen: false }" @open-sidebar.window="sidebarOpen = true" @keydown.escape.window="sidebarOpen = false"
     x-init="
        $watch('sidebarOpen', function(value){
            if(value){ document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }
        });
    ">

  {{-- Backdrop for mobile --}}
  <div x-show="sidebarOpen" @click="sidebarOpen=false" x-transition.opacity x-cloak
       class="ui:fixed ui:inset-0 ui:z-40 ui:bg-ink/30 ui:lg:hidden"></div>

  {{-- Sidebar --}}
  <aside :class="{ 'ui:-translate-x-full': !sidebarOpen }"
         class="ui:fixed ui:inset-y-0 ui:left-0 ui:z-50 ui:flex ui:w-64 ui:-translate-x-full ui:flex-col ui:border-0 ui:border-r ui:border-solid ui:border-line ui:bg-white ui:transition-transform ui:lg:translate-x-0">

    <div class="ui:flex ui:h-16 ui:shrink-0 ui:items-center ui:justify-between ui:px-5">
      <a href="/" class="ui:flex ui:items-center ui:no-underline!">
        <x-logo style="height: 1.75rem; width: auto;"/>
      </a>
      <button type="button" @click="sidebarOpen=false"
              class="ui:flex ui:size-9 ui:items-center ui:justify-center ui:rounded-lg ui:border-0 ui:bg-transparent ui:text-slate-500 ui:hover:bg-slate-100 ui:lg:hidden"
              aria-label="{{ __('Close') }}">
        <x-phosphor-x class="ui:size-5"/>
      </button>
    </div>

    <nav class="ui:flex ui:flex-1 ui:flex-col ui:gap-3 ui:overflow-y-auto ui:px-3 ui:py-4">

      <div class="ui:flex ui:flex-col ui:gap-0.5">
        @if($user->canView('iframes.dashboard'))
        <x-app.sidebar-link href="{{ route('dashboard') }}"
                            icon="phosphor-house"
                            :active="Request::is('dashboard')">
          {{ __('Dashboard') }}
        </x-app.sidebar-link>
        @endif
        @if($user->canView('iframes.assets'))
        <x-app.sidebar-link href="{{ route('assets') }}"
                            icon="phosphor-globe"
                            :active="Request::is('assets')">
          {{ __('Inventory') }}
        </x-app.sidebar-link>
        @endif
        @if($user->canView('iframes.vulnerabilities'))
        <x-app.sidebar-link href="{{ route('vulnerabilities') }}"
                            icon="phosphor-shield-warning"
                            :active="Request::is('vulnerabilities')">
          {{ __('Vulnerabilities') }}
        </x-app.sidebar-link>
        @endif
        @if($user->canView('iframes.leaks'))
        <x-app.sidebar-link href="{{ route('leaks') }}"
                            icon="phosphor-user"
                            :active="Request::is('leaks')">
          {{ __('Leaks') }}
        </x-app.sidebar-link>
        @endif
        @if($user->canView('iframes.events'))
        <x-app.sidebar-link href="{{ route('events') }}"
                            icon="phosphor-flow-arrow"
                            :active="Request::is('events')">
          {{ __('Events') }}
        </x-app.sidebar-link>
        @endif
        @if($user->canView('iframes.notes-and-memos'))
        <x-app.sidebar-link href="{{ route('notes-and-memos') }}"
                            icon="phosphor-pencil-simple"
                            :active="Request::is('notes-and-memos')">
          {{ __('Notes') }}
        </x-app.sidebar-link>
        @endif
        @if(isset($user->performa_domain))
        <x-app.sidebar-link href="{{ request()->isSecure() ? 'https://' : 'http://' }}{{ $user->performa_domain }}"
                            icon="phosphor-chart-line"
                            target="_blank">
          {{ __('Metrics') }}
        </x-app.sidebar-link>
        @endif
      </div>

      @if($user->canView('iframes.sca')
      || $user->canView('iframes.rules')
      || $user->canView('iframes.frameworks')
      || $user->canView('iframes.tables')
      || $user->canView('iframes.collections')
      || $user->canView('iframes.documents')
      || $user->canView('iframes.chunks')
      || $user->canView('iframes.prompts')
      || $user->canView('iframes.users')
      || $user->canView('iframes.shares')
      || $user->canView('iframes.roles-and-permissions')
      || $user->canView('iframes.traces')
      || $user->canView('iframes.scheduled-tasks')
      || $user->canView('iframes.actions')
      || $user->isCywiseAdmin())
      <x-app.sidebar-section id="configuration" :title="__('Configuration')" :state="(Request::is('sca') || Request::is('rules') || Request::is('frameworks') || Request::is('tables') || Request::is('collections') || Request::is('documents') || Request::is('chunks') || Request::is('prompts') || Request::is('users') || Request::is('shares') || Request::is('roles-and-permissions') || Request::is('scheduled-tasks') || Request::is('traces') || Request::is('actions')) ? 'active' : 'closed'">
        @if($user->canView('iframes.sca')
        || $user->canView('iframes.rules'))
        <x-app.sidebar-dropdown text="{{ __('Agent') }}"
                                icon="phosphor-cube"
                                id="libraries_dropdown"
                                :open="(
                        Request::is('sca') ||
                        Request::is('rules')
                      ) ? '1' : '0'">
          @if($user->canView('iframes.rules'))
          <x-app.sidebar-link href="{{ route('rules') }}"
                              icon="phosphor-magnifying-glass"
                              :active="Request::is('rules')">
            {{ __('Security Rules') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.sca'))
          <x-app.sidebar-link href="{{ route('sca') }}"
                              icon="phosphor-list-checks"
                              :active="Request::is('sca')">
            {{ __('Security Checks Automation') }}
          </x-app.sidebar-link>
          @endif
        </x-app.sidebar-dropdown>
        @endif
        @if($user->canView('iframes.frameworks')
        || $user->canView('iframes.tables')
        || $user->canView('iframes.collections')
        || $user->canView('iframes.documents')
        || $user->canView('iframes.chunks'))
        <x-app.sidebar-dropdown text="{{ __('Data Management') }}"
                                icon="phosphor-database"
                                id="datamanagement_dropdown"
                                :open="(
                        Request::is('frameworks') ||
                        Request::is('tables') ||
                        Request::is('collections') ||
                        Request::is('documents') ||
                        Request::is('chunks')
                      ) ? '1' : '0'">
          @if($user->canView('iframes.frameworks'))
          <x-app.sidebar-link href="{{ route('frameworks') }}"
                              icon="phosphor-books"
                              :active="Request::is('frameworks')">
            {{ __('Frameworks') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.tables'))
          <x-app.sidebar-link href="{{ route('tables') }}"
                              icon="phosphor-table"
                              :active="Request::is('tables')">
            {{ __('Tables') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.collections'))
          <x-app.sidebar-link href="{{ route('collections') }}"
                              icon="phosphor-folders"
                              :active="Request::is('collections')">
            {{ __('Collections') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.documents'))
          <x-app.sidebar-link href="{{ route('documents') }}"
                              icon="phosphor-files"
                              :active="Request::is('documents')">
            {{ __('Documents') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.chunks'))
          <x-app.sidebar-link href="{{ route('chunks') }}"
                              icon="phosphor-grid-four"
                              :active="Request::is('chunks')">
            {{ __('Chunks') }}
          </x-app.sidebar-link>
          @endif
        </x-app.sidebar-dropdown>
        @endif
        @if($user->canView('iframes.prompts')
        || $user->canView('iframes.users')
        || $user->canView('iframes.shares')
        || $user->canView('iframes.roles-and-permissions')
        || $user->canView('iframes.traces')
        || $user->canView('iframes.scheduled-tasks')
        || $user->canView('iframes.actions')
        || $user->isCywiseAdmin())
        <x-app.sidebar-dropdown text="{{ __('Administration') }}"
                                icon="phosphor-gear"
                                id="admin_dropdown"
                                :open="(
                        Request::is('prompts') ||
                        Request::is('users') ||
                        Request::is('shares') ||
                        Request::is('roles-and-permissions') ||
                        Request::is('scheduled-tasks') ||
                        Request::is('traces') ||
                        Request::is('actions')
                      ) ? '1' : '0'">
          @if($user->canView('iframes.scheduled-tasks'))
          <x-app.sidebar-link href="{{ route('scheduled-tasks') }}"
                              icon="phosphor-clock"
                              :active="Request::is('scheduled-tasks')">
            {{ __('Scheduled Tasks') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.prompts'))
          <x-app.sidebar-link href="{{ route('prompts') }}"
                              icon="phosphor-notepad"
                              :active="Request::is('prompts')">
            {{ __('Prompts') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.actions'))
          <x-app.sidebar-link href="{{ route('actions') }}"
                              icon="phosphor-wrench"
                              :active="Request::is('actions')">
            {{ __('Actions') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.shares'))
          <x-app.sidebar-link href="{{ route('shares') }}"
                              icon="phosphor-share-network"
                              :active="Request::is('shares')">
            {{ __('Shares') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.users'))
          <x-app.sidebar-link href="{{ route('users') }}"
                              icon="phosphor-users"
                              :active="Request::is('users')">
            {{ __('Users') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.roles-and-permissions'))
          <x-app.sidebar-link href="{{ route('roles-and-permissions') }}"
                              icon="phosphor-shield-check"
                              :active="Request::is('roles-and-permissions')">
            {{ __('Roles & Permissions') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.traces'))
          <x-app.sidebar-link href="{{ route('traces') }}"
                              icon="phosphor-list-dashes"
                              :active="Request::is('traces')">
            {{ __('Traces') }}
          </x-app.sidebar-link>
          @endif
        </x-app.sidebar-dropdown>
        @endif
      </x-app.sidebar-section>
      @endif
    </nav>

    <div class="ui:flex ui:shrink-0 ui:flex-col ui:gap-0.5 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-3 ui:py-3">
      @if($user->canView('iframes.documentation'))
      <x-app.sidebar-link href="{{ route('documentation') }}"
                          icon="phosphor-book-bookmark"
                          :active="Request::is('documentation')">
        {{ __('Documentation') }}
      </x-app.sidebar-link>
      @endif
      <x-app.sidebar-link :href="route('changelogs')"
                          icon="phosphor-book-open-text"
                          :active="Request::is('changelog') || Request::is('changelog/*')">
        {{ __('Changelog') }}
      </x-app.sidebar-link>
    </div>
  </aside>

  @include('theme::components.app.freshdesk')

</div>
