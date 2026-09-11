<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  @include('theme::partials.head', ['seo' => ($seo ?? null) ])

  <!-- FastBootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/fastbootstrap@2.2.0/dist/css/fastbootstrap.min.css"
        rel="stylesheet"
        integrity="sha256-V6lu+OdYNKTKTsVFBuQsyIlDiRWiOmtC8VQ8Lzdm2i4="
        crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
          crossorigin="anonymous"></script>

  <!-- app-specific styles -->
  <link href="{{ asset('cywise/css/app.css') }}" rel="stylesheet">

</head>
<body x-data class="flex flex-col overflow-x-hidden @if($bodyClass ?? false){{ $bodyClass }}@endif" x-cloak style="min-height:100vh;">
    <x-app.container>
      <x-card class="lg:p-10">
        <x-app.heading
            title="{{ __('Changelog') }}"
            description="{{ __('Latest updates and improvements.') }}"
        />
        <div class="max-w-full mt-8 prose-sm prose dark:prose-invert">
          @foreach($logs as $changelog)
          @php \Illuminate\Support\Facades\Log::error($changelog); @endphp
          <div class="flex flex-col items-start space-y-3 lg:flex-row lg:space-y-0 lg:space-x-5">
            <div class="flex-shrink-0 px-2 py-1 text-xs translate-y-1 rounded-full bg-zinc-100 dark:bg-zinc-600">
              <time datetime="{{ Carbon\Carbon::parse($changelog->created_at)->toIso8601String() }}" class="ml-1">{{ Carbon\Carbon::parse($changelog->created_at)->toFormattedDateString() }}</time>
            </div>
            <div class="relative">
              <a href="{{ route('changelog', ['changelog' => $changelog->id]) }}" class="text-xl no-underline hover:underline" wire:navigate>{{ $changelog->title }}</a>
              <div class="mx-auto mt-5 prose-sm prose text-zinc-600 dark:text-zinc-300">
                {!! $changelog->body !!}
              </div>
              @if(!$loop->last)
              <hr class="block my-10 border-dashed">
              @endif
            </div>
          </div>
          @endforeach
        </div>
      </x-card>
    </x-app.container>
</main>

@livewire('notifications')
@include('theme::partials.footer-scripts')
{{ $javascript ?? '' }}

</body>
</html>
