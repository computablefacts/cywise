<?php

use App\Http\Controllers\Iframes\SharesController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('shares');
render(function (Request $request) {
  return app(SharesController::class)($request);
});
?>

<x-layouts.app>
  <div class="ui:mx-auto ui:flex ui:w-full ui:max-w-7xl ui:flex-col ui:gap-6 ui:px-4 ui:py-8 ui:lg:px-8">

    <x-ui.page-header :title="__('Shares')" :subtitle="__('Assets shared with other teams, grouped by tags.')"/>

    <x-ui.card flush>
      @if($shares->isEmpty())
        <x-ui.empty icon="share-network">{{ __('None.') }}</x-ui.empty>
      @else
        <x-ui.table>
          <thead>
          <tr>
            <th>{{ __('Shared to') }}</th>
            <th>{{ __('Tags') }}</th>
            <th class="ui:text-right!">{{ __('Number of Assets') }}</th>
            <th class="ui:text-right!">{{ __('Number of Vulnerabilities') }}</th>
            <th>{{ __('Shared by') }}</th>
            <th class="ui:text-right!">{{ __('Actions') }}</th>
          </tr>
          </thead>
          <tbody>
          @foreach($shares as $share)
            <tr>
              <td><x-ui.badge level="info">{{ $share['group'] }}</x-ui.badge></td>
              <td>
                <div class="ui:flex ui:flex-wrap ui:gap-1">
                  @foreach($share['tags'] as $tag)
                    <x-ui.tag tone="auto">{{ $tag }}</x-ui.tag>
                  @endforeach
                </div>
              </td>
              <td class="ui:text-right ui:tabular-nums">{{ $share['nb_assets'] }}</td>
              <td class="ui:text-right ui:tabular-nums">{{ $share['nb_vulnerabilities'] }}</td>
              <td class="ui:text-slate-600">{{ $share['target'] }}</td>
              <td>
                <div class="ui:flex ui:justify-end">
                  <x-ui.icon-button icon="trash" :title="__('Delete')" class="ui:hover:text-red-600!"
                                    onclick="degroup(@js($share['group']))"/>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </x-ui.table>
      @endif
    </x-ui.card>
  </div>

  @push('scripts')
  <script>

    const degroup = (group) => {
      if (confirm("{{ __('Are you sure you want to remove this group?') }}")) {
        degroupApiCall(group, response => location.reload());
      }
    }

  </script>
  @endpush

</x-layouts.app>