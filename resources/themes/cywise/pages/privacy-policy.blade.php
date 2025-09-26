<?php

use function Laravel\Folio\{name};

name('privacy-policy');

$file = file_exists(public_path('/cywise/markdown/privacy-policy.' . app()->getLocale() . '.md'))
    ? public_path('/cywise/markdown/privacy-policy.' . app()->getLocale() . '.md')
    : public_path('/cywise/markdown/privacy-policy.md');

$html = (new Parsedown)->text(file_get_contents($file));

// use a dynamic layout based on whether or not the user is authenticated
$layout = ((auth()->guest()) ? 'layouts.marketing' : 'layouts.app');
?>

<x-dynamic-component :component="$layout" :title="__('Privacy Policy')">
  <x-app.container>
    <x-card class="lg:p-10">
      <x-app.heading
        title="{{ __('Privacy Policy') }}"
        description=""
      />
      <div class="max-w-full mt-8 prose-sm prose dark:prose-invert">
        <div class="flex flex-col items-start space-y-3 lg:flex-row lg:space-y-0 lg:space-x-5">
          <div class="relative">
            <div class="mx-auto mt-5 prose-sm prose text-zinc-600 dark:text-zinc-300">
              {!! $html !!}
            </div>
          </div>
        </div>
      </div>
    </x-card>
  </x-app.container>
</x-dynamic-component>

