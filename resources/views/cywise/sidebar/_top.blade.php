<x-app.sidebar-link href="{{ route('dashboard') }}"
                    icon="phosphor-house"
                    :active="Request::is('dashboard')">
  {{ __('Dashboard') }}
</x-app.sidebar-link>
<x-app.sidebar-dropdown text="{{ __('Timelines') }}"
                        icon="phosphor-stack"
                        id="timelines_dropdown"
                        :active="false"
                        :open="(
                          Request::is('private/vulnerabilities') ||
                          Request::is('private/leaks') ||
                          Request::is('private/ioc') ||
                          Request::is('private/assets') ||
                          Request::is('private/events') ||
                          Request::is('private/conversations') ||
                          Request::is('private/notes-and-memos')
                        ) ? '1' : '0'">
  <x-app.sidebar-link
    href="{{ route('vulnerabilities') }}"
    icon="phosphor-warning-circle"
    :active="Request::is('private/vulnerabilities')">
    {{ __('Vulnerabilities') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link
    href="{{ route('leaks') }}"
    icon="phosphor-user"
    :active="Request::is('private/leaks')">
    {{ __('Leaks') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link
    href="{{ route('ioc') }}"
    icon="phosphor-magnifying-glass"
    :active="Request::is('private/ioc')">
    {{ __('Indicators of Compromise') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link
    href="{{ route('events') }}"
    icon="phosphor-flow-arrow"
    :active="Request::is('private/events')">
    {{ __('Events') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link
    href="{{ route('assets') }}"
    icon="phosphor-globe"
    :active="Request::is('private/assets')">
    {{ __('Assets') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link
    href="{{ route('conversations') }}"
    icon="phosphor-chats"
    :active="Request::is('private/conversations')">
    {{ __('Conversations') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link
    href="{{ route('notes-and-memos') }}"
    icon="phosphor-pencil-simple"
    :active="Request::is('private/notes-and-memos')">
    {{ __('Notes & Memos') }}
  </x-app.sidebar-link>
</x-app.sidebar-dropdown>
<x-app.sidebar-link href="{{ route('cyberbuddy') }}"
                    icon="phosphor-robot"
                    :active="Request::is('private/cyberbuddy')">
  {{ __('CyberBuddy') }}
</x-app.sidebar-link>
<x-app.sidebar-link href="{{ route('cyberscribe') }}"
                    icon="phosphor-pencil-circle"
                    :active="Request::is('private/cyberscribe')">
  {{ __('CyberScribe') }}
</x-app.sidebar-link>
<x-app.sidebar-dropdown text="{{ __('Libraries') }}"
                        icon="phosphor-books"
                        id="libraries_dropdown"
                        :active="false"
                        :open="(
                          Request::is('private/frameworks') ||
                          Request::is('private/sca') ||
                          Request::is('private/rules')
                        ) ? '1' : '0'">
  <x-app.sidebar-link href="{{ route('frameworks') }}"
                      icon="phosphor-cube"
                      :active="Request::is('private/frameworks')">
    {{ __('Frameworks') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link href="{{ route('rules') }}"
                      icon="phosphor-cube"
                      :active="Request::is('private/rules')">
    {{ __('Security Rules') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link href="{{ route('sca') }}"
                      icon="phosphor-cube"
                      :active="Request::is('private/sca')">
    {{ __('Security Checks Automation') }}
  </x-app.sidebar-link>
</x-app.sidebar-dropdown>
<x-app.sidebar-dropdown text="{{ __('Data Management') }}"
                        icon="phosphor-database"
                        id="datamanagement_dropdown"
                        :active="false"
                        :open="(
                          Request::is('private/prompts') ||
                          Request::is('private/tables') ||
                          Request::is('private/collections') ||
                          Request::is('private/documents') ||
                          Request::is('private/chunks')
                        ) ? '1' : '0'">
  <x-app.sidebar-link href="{{ route('prompts') }}"
                      icon="phosphor-notepad"
                      :active="Request::is('private/prompts')">
    {{ __('Prompts') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link href="{{ route('tables') }}"
                      icon="phosphor-table"
                      :active="Request::is('private/tables')">
    {{ __('Tables') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link href="{{ route('collections') }}"
                      icon="phosphor-folders"
                      :active="Request::is('private/collections')">
    {{ __('Collections') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link href="{{ route('documents') }}"
                      icon="phosphor-files"
                      :active="Request::is('private/documents')">
    {{ __('Documents') }}
  </x-app.sidebar-link>
  <x-app.sidebar-link href="{{ route('chunks') }}"
                      icon="phosphor-grid-four"
                      :active="Request::is('private/chunks')">
    {{ __('Chunks') }}
  </x-app.sidebar-link>
</x-app.sidebar-dropdown>
<x-app.sidebar-dropdown text="{{ __('Administration') }}"
                        icon="phosphor-gear"
                        id="admin_dropdown"
                        :active="false"
                        :open="(
                          Request::is('private/users')
                        ) ? '1' : '0'">
  <x-app.sidebar-link href="{{ route('users') }}"
                      icon="phosphor-users"
                      :active="Request::is('private/users')">
    {{ __('Users') }}
  </x-app.sidebar-link>
</x-app.sidebar-dropdown>