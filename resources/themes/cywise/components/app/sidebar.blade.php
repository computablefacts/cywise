@php
$user = \Auth::user();
@endphp
<div x-data="{ sidebarOpen: false }" @open-sidebar.window="sidebarOpen = true"
     x-init="
        $watch('sidebarOpen', function(value){
            if(value){ document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }
        });
    "
     class="relative z-50 w-screen md:w-auto" x-cloak>

  {{-- Backdrop for mobile --}}
  <div x-show="sidebarOpen" @click="sidebarOpen=false"
       class="fixed top-0 right-0 z-50 w-screen h-screen duration-300 ease-out bg-black/20 dark:bg-white/10"></div>

  {{-- Sidebar --}}
  <div :class="{ '-translate-x-full': !sidebarOpen }"
       class="fixed top-0 left-0 flex items-stretch -translate-x-full overflow-hidden lg:translate-x-0 z-50 h-dvh md:h-screen transition-[width,transform] duration-150 ease-out bg-zinc-50 dark:bg-zinc-900 w-64 group @if(config('wave.dev_bar')){{ 'pb-10' }}@endif">
    <div class="flex flex-col justify-between w-full overflow-auto md:h-full h-svh pt-4 pb-2.5">
      <div class="relative flex flex-col">
        <button x-on:click="sidebarOpen=false"
                class="flex items-center justify-center flex-shrink-0 w-10 h-10 ml-4 rounded-md lg:hidden text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 dark:hover:bg-zinc-700/70 hover:bg-gray-200/70">
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
               stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
          </svg>
        </button>

        <div class="flex items-center px-5 space-x-2">
          <a href="/" class="flex justify-center items-center py-4 pl-0.5 space-x-1 font-bold text-zinc-900">
            <x-logo class="w-auto h-7"/>
          </a>
        </div>
        <!--
        <div class="flex items-center px-4 pt-1 pb-3">
            <div class="relative flex items-center w-full h-full rounded-lg">
                <x-phosphor-magnifying-glass class="absolute left-0 w-5 h-5 ml-2 text-gray-400 -translate-y-px" />
                <input type="text" class="w-full py-2 pl-8 text-sm border rounded-lg bg-zinc-200/70 focus:bg-white duration-50 dark:bg-zinc-950 ease border-zinc-200 dark:border-zinc-700/70 dark:ring-zinc-700/70 focus:ring dark:text-zinc-200 dark:focus:ring-zinc-700/70 dark:focus:border-zinc-700 focus:ring-zinc-200 focus:border-zinc-300 dark:placeholder-zinc-400" placeholder="Search">
            </div>
        </div>
        -->
        <div
          class="flex flex-col justify-start items-center px-4 space-y-1.5 w-full h-full text-slate-600 dark:text-zinc-400">
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
              href="https://{{ Auth::user()->performa_domain }}"
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
            {{ __('CyberBuddy') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.cyberscribe'))
          <x-app.sidebar-link href="{{ route('cyberscribe') }}"
                              icon="phosphor-pencil-circle"
                              :active="Request::is('cyberscribe')">
            {{ __('CyberScribe') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.analyze'))
          <x-app.sidebar-link href="{{ route('analyze') }}"
                              icon="phosphor-chart-line"
                              :active="Request::is('analyze')">
            {{ __('Optimize (bêta)') }}
          </x-app.sidebar-link>
          @endif
          @if($user->canView('iframes.frameworks')
          || $user->canView('iframes.sca')
          || $user->canView('iframes.rules'))
          <x-app.sidebar-dropdown text="{{ __('Libraries') }}"
                                  icon="phosphor-books"
                                  id="libraries_dropdown"
                                  :active="false"
                                  :open="(
                          Request::is('frameworks') ||
                          Request::is('sca') ||
                          Request::is('rules')
                        ) ? '1' : '0'">
            @if($user->canView('iframes.frameworks'))
            <x-app.sidebar-link href="{{ route('frameworks') }}"
                                icon="phosphor-cube"
                                :active="Request::is('frameworks')">
              {{ __('Frameworks') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.rules'))
            <x-app.sidebar-link href="{{ route('rules') }}"
                                icon="phosphor-cube"
                                :active="Request::is('rules')">
              {{ __('Security Rules') }}
            </x-app.sidebar-link>
            @endif
            @if($user->canView('iframes.sca'))
            <x-app.sidebar-link href="{{ route('sca') }}"
                                icon="phosphor-cube"
                                :active="Request::is('sca')">
              {{ __('Security Checks Automation') }}
            </x-app.sidebar-link>
            @endif
          </x-app.sidebar-dropdown>
          @endif
          @if($user->canView('iframes.prompts')
          || $user->canView('iframes.tables')
          || $user->canView('iframes.collections')
          || $user->canView('iframes.documents')
          || $user->canView('iframes.chunks'))
          <x-app.sidebar-dropdown text="{{ __('Data Management') }}"
                                  icon="phosphor-database"
                                  id="datamanagement_dropdown"
                                  :active="false"
                                  :open="(
                          Request::is('prompts') ||
                          Request::is('tables') ||
                          Request::is('collections') ||
                          Request::is('documents') ||
                          Request::is('chunks')
                        ) ? '1' : '0'">
            @if($user->canView('iframes.prompts'))
            <x-app.sidebar-link href="{{ route('prompts') }}"
                                icon="phosphor-notepad"
                                :active="Request::is('prompts')">
              {{ __('Prompts') }}
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
          @if($user->canView('iframes.users')
          || $user->canView('iframes.roles-and-permissions')
          || $user->canView('iframes.traces'))
          <x-app.sidebar-dropdown text="{{ __('Administration') }}"
                                  icon="phosphor-gear"
                                  id="admin_dropdown"
                                  :active="false"
                                  :open="(
                          Request::is('users') ||
                          Request::is('roles-and-permissions') ||
                          Request::is('traces')
                        ) ? '1' : '0'">
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
      <div class="relative px-2.5 space-y-1.5 text-zinc-700 dark:text-zinc-400">
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
        <!--
        <div x-show="sidebarTip"
             x-data="{ sidebarTip: $persist(true) }"
             class="px-1 py-3"
             x-collapse
             x-cloak>
            <div
                class="relative w-full px-4 py-3 space-y-1 border rounded-lg bg-zinc-50 text-zinc-700 dark:text-zinc-100 dark:bg-zinc-800 border-zinc-200/60 dark:border-zinc-700">
                <button @click="sidebarTip=false"
                        class="absolute top-0 right-0 z-50 p-1.5 mt-2.5 mr-2.5 rounded-full opacity-80 cursor-pointer hover:opacity-100 hover:bg-zinc-100 hover:dark:bg-zinc-700 hover:dark:text-zinc-300 text-zinc-500 dark:text-zinc-400">
                    <x-phosphor-x-bold class="w-3 h-3"/>
                </button>
                <h5 class="pb-1 text-sm font-bold -translate-y-0.5">
                    Edit This Section
                </h5>
                <p class="block pb-1 text-xs opacity-80 text-balance">
                    You can edit any aspect of your user dashboard. This section can be found inside your theme
                    component/app/sidebar file.
                </p>
            </div>
        </div>
        -->
        <div class="w-full h-px my-2 bg-slate-100 dark:bg-zinc-700"></div>
        <x-app.user-menu/>
      </div>
    </div>
  </div>

  @include('theme::components.app.freshdesk')

</div>
