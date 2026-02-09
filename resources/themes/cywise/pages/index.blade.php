<?php

use function Laravel\Folio\{name};

name('home');
?>

<x-layouts.website
    :seo="[
        'title'         => setting('site.title', 'Cywise'),
        'description'   => setting('site.description', 'Cybersecurity for the mortals'),
        'image'         => url('/cywise/img/screenshot.png'),
        'type'          => 'website'
    ]"
>
  <iframe src="{{ route('iframes.website') }}" class="w-full flex-1 min-h-0 border-0"></iframe>
</x-layouts.website>
