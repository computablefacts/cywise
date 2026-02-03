@extends('layouts.app')

@section('content')
<style>
  .nav-link.active {
    border-bottom: 2px solid #becdcf;
  }
</style>
<div class="container">
  <ul class="nav justify-content-end mb-4">
    <li class="nav-item">
      <a class="nav-link {{ !$tab || $tab === 'events' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=events">
        {{ __('Events') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ !$tab || $tab === 'settings' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=settings">
        {{ __('Settings') }}
      </a>
    </li>
    @if($server->isYunoHost())
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'backups' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=backups">
        {{ __('Backups') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'domains' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=domains">
        {{ __('Domains') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'applications' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=applications">
        {{ __('Applications') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'traces' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=traces">
        {{ __('Traces') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'interdependencies' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=interdependencies">
        {{ __('Interdependencies') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'users' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=users">
        {{ __('Users') }}
      </a>
    </li>
    @if(Auth::user()->canManageServers())
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'shell' ? 'active' : '' }}"
         href="{{ route('ynh.servers.edit', $server) }}?tab=shell">
        {{ __('Shell') }}
      </a>
    </li>
    @endif
    @endif
  </ul>
  @if(!$tab || $tab === 'settings')
  <x-server :server="$server"/>
  @endif
  @if($tab === 'backups')
  <x-backups :server="$server"/>
  @endif
  @if($tab === 'traces')
  <div class="row mb-2">
    <div class="col-12">
      <x-pending-actions :server="$server"/>
    </div>
  </div>
  <div class="row">
    <div class="col-12">
      <x-traces :server="$server"/>
    </div>
  </div>
  <script>
    setTimeout(() => window.location.reload(), 15000);
  </script>
  @endif
  @if(Auth::user()->canManageServers() && $tab === 'shell')
  <x-shell :server="$server"/>
  @endif
  @if($tab === 'domains')
  <x-domains :server="$server"/>
  @endif
  @if($tab === 'applications')
  <div class="row">
    <div class="col-12">
      <x-applications-ready-to-be-deployed :server="$server"/>
    </div>
  </div>
  @endif
  @if($tab === 'interdependencies')
  <x-interdependencies :server="$server"/>
  @endif
</div>
@endsection