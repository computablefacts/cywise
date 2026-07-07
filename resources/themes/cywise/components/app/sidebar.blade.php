@php
$user = \Auth::user();
@endphp
<div x-data="{ sidebarOpen: false }" @open-sidebar.window="sidebarOpen = true"
     x-init="
        $watch('sidebarOpen', function(value){
            if(value){ document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }
        });
    "
     class="position-relative z-index-1050 w-100 w-md-auto" x-cloak>

  {{-- Backdrop for mobile --}}
  <div x-show="sidebarOpen" @click="sidebarOpen=false"
       class="position-fixed top-0 end-0 z-index-1050 w-100 h-100 transition-all bg-dark bg-opacity-25"></div>

  {{-- Sidebar --}}
  <div :class="{ 'translate-x-n100': !sidebarOpen }"
       class="position-fixed top-0 start-0 d-flex align-items-stretch translate-x-n100 overflow-hidden translate-x-lg-0 z-index-1050 h-100 transition-all bg-light border-end" style="width: 260px;">
    <div class="d-flex flex-column justify-content-between w-100 overflow-auto h-100 pt-3 pb-2">
      <div class="position-relative d-flex flex-column">
        <button x-on:click="sidebarOpen=false"
                class="btn btn-link d-flex align-items-center justify-content-center flex-shrink-0 w-10 h-10 ms-3 rounded d-lg-none text-muted">
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
               stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
          </svg>
        </button>

        <div class="d-flex align-items-center px-4 gap-2">
          <a href="/" class="d-flex justify-content-center align-items-center py-3 ps-1 gap-1 fw-bold text-dark text-decoration-none">
            <x-logo style="height: 1.75rem; width: auto;"/>
          </a>
        </div>

        <div class="py-2 border-top border-bottom mb-2 mt-2">
          <div class="px-3">
            <input type="text"
                   name="tld"
                   id="sidebar_tld"
                   class="form-control form-control-sm"
                   placeholder="{{ __('Domain or IP...') }}"
                   aria-label="{{ __('Domain or IP') }}"
                   onkeydown="if(event.key === 'Enter') { event.preventDefault(); sidebarCreateAsset(); }">
            <button type="button"
                    onclick="sidebarCreateAsset()"
                    class="btn btn-primary btn-sm w-100 mt-2">
              {{ __('Monitor >') }}
            </button>
          </div>
        </div>
        <script>
          function sidebarCreateAsset() {
            const asset = document.querySelector('#sidebar_tld').value;
            if (asset) {
              createAssetApiCall(asset, true, () => {
                window.toaster.toastSuccess("{{ __('The monitoring started.') }}");
                document.querySelector('#sidebar_tld').value = '';
              });
            }
          }
        </script>

        <div
          class="d-flex flex-column justify-content-start align-items-center px-3 gap-1 w-100 h-100 text-muted">
          @if($user->canView('iframes.dashboard'))
          <x-app.sidebar-link href="{{ route('dashboard') }}"
                              icon="phosphor-house"
                              :active="Request::is('dashboard')">
            {{ __('Dashboard') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.vulnerabilities')
          || $user->canView('iframes.leaks')
          || $user->canView('iframes.ioc')
          || $user->canView('iframes.assets')
          || $user->canView('iframes.events')
          || $user->canView('iframes.conversations')
          || $user->canView('iframes.notes-and-memos'))
          <x-app.sidebar-dropdown text="{{ __('Timelines') }}"
                                  icon="phosphor-stack"
                                  id="timelines_dropdown"
                                  :active="false"
                                  :open="(
                          Request::is('vulnerabilities') ||
                          Request::is('leaks') ||
                          Request::is('ioc') ||
                          Request::is('assets') ||
                          Request::is('events') ||
                          Request::is('conversations') ||
                          Request::is('notes-and-memos')
                        ) ? '1' : '0'">
            @if($user->canView('iframes.vulnerabilities'))
            <x-app.sidebar-link
              href="{{ route('vulnerabilities') }}"
              icon="phosphor-warning-circle"
              :active="Request::is('vulnerabilities')">
              {{ __('Vulnerabilities') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.leaks'))
            <x-app.sidebar-link
              href="{{ route('leaks') }}"
              icon="phosphor-user"
              :active="Request::is('leaks')">
              {{ __('Leaks') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.ioc'))
            <x-app.sidebar-link
              href="{{ route('ioc') }}"
              icon="phosphor-magnifying-glass"
              :active="Request::is('ioc')">
              {{ __('Indicators of Compromise') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.events'))
            <x-app.sidebar-link
              href="{{ route('events') }}"
              icon="phosphor-flow-arrow"
              :active="Request::is('events')">
              {{ __('Events') }}
            </x-app.sidebar-link>
            @endif
            @if(isset(Auth::user()->performa_domain))
            <x-app.sidebar-link
              href="{{ request()->isSecure() ? 'https://' : 'http://' }}{{ Auth::user()->performa_domain }}"
              icon="phosphor-chart-line"
              target="_blank">
              {{ __('Metrics') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.assets'))
            <x-app.sidebar-link
              href="{{ route('assets') }}"
              icon="phosphor-globe"
              :active="Request::is('assets')">
              {{ __('Assets') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.conversations'))
            <x-app.sidebar-link
              href="{{ route('conversations') }}"
              icon="phosphor-chats"
              :active="Request::is('conversations')">
              {{ __('Conversations') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.notes-and-memos'))
            <x-app.sidebar-link
              href="{{ route('notes-and-memos') }}"
              icon="phosphor-pencil-simple"
              :active="Request::is('notes-and-memos')">
              {{ __('Notes & Memos') }}
            </x-app.sidebar-link>
            @endif
          </x-app.sidebar-dropdown>
          @endif
          @if($user->canView('iframes.cyberbuddy'))
          <x-app.sidebar-link href="{{ route('cyberbuddy') }}"
                              icon="phosphor-robot"
                              :active="Request::is('cyberbuddy')">
            {{ tenant_custom_text('CyberBuddy') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.cyberscribe'))
          <x-app.sidebar-link href="{{ route('cyberscribe') }}"
                              icon="phosphor-pencil-circle"
                              :active="Request::is('cyberscribe')">
            {{ __('CyberScribe') }}
          </x-app.sidebar-link>
          @endif
          <!--
          @if($user->canView('iframes.analyze'))
          <x-app.sidebar-link href="{{ route('analyze') }}"
                              icon="phosphor-chart-line"
                              :active="Request::is('analyze')">
            {{ __('Explore (bêta)') }}
          </x-app.sidebar-link>
          @endif
          -->
          @if($user->canView('iframes.sca')
          || $user->canView('iframes.rules'))
          <x-app.sidebar-dropdown text="{{ __('Agent') }}"
                                  icon="phosphor-cube"
                                  id="libraries_dropdown"
                                  :active="false"
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
                                icon="phosphor-flow-arrow"
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
                                  :active="false"
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
                                  :active="false"
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
        </div>
      </div>
      <div class="position-relative px-3 gap-1 text-muted">
        @if($user->canView('iframes.documentation'))
        <x-app.sidebar-link href="{{ route('documentation') }}"
                            icon="phosphor-book-bookmark-duotone"
                            :active="Request::is('documentation')">
          {{ __('Documentation') }}
        </x-app.sidebar-link>
        @endif
        <x-app.sidebar-link :href="route('changelogs')"
                            icon="phosphor-book-open-text-duotone"
                            :active="Request::is('changelog') || Request::is('changelog/*')">
          {{ __('Changelog') }}
        </x-app.sidebar-link>
        
        <div class="w-100 my-2 border-top"></div>
        <x-app.user-menu/>
      </div>
    </div>
  </div>

  @include('theme::components.app.freshdesk')

</div>
