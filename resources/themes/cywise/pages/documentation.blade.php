<?php

use App\Http\Controllers\Iframes\DocumentationController;
use App\Http\Middleware\CheckPermissionsHttpRequest;
use App\Http\Middleware\LogHttpRequests;
use Illuminate\Http\Request;
use function Laravel\Folio\{middleware, name, render};

middleware([LogHttpRequests::class, 'auth', CheckPermissionsHttpRequest::class]);
name('documentation');
render(function (Request $request) {
  return app(DocumentationController::class)($request);
});
?>

<x-layouts.app>
  <div class="container-fluid">
    <div class="row pt-3">
      <div class="col">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">
              {{ __('API') }}
            </h6>
            <div class="card-text mb-3">
              La documentation de l'API est <a href="{{ route('v2.private.rpc.docs') }}" class="link" target="_blank">ici</a>.
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">
              {{ __('Data Management') }}
            </h6>
            <div class="card-text mb-3">
              La documentation de l'interface utilisateur est accessible <a
                  href="https://computablefacts.notion.site/Guide-utilisateur-2160a1f68ecc80689497e7dd5c07a817?source=copy_link"
                  class="link"
                  target="_blank">ici</a>.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-layouts.app>

