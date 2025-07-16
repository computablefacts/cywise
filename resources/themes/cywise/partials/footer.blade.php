<!-- Section 1 -->
<footer class="pt-10">
  <x-container>
    <!--
    @if(!Request::is('/'))
    <div class="flex flex-wrap items-start justify-between pb-20">
      <a href="#_"
         class="flex items-center w-auto mt-1 text-lg font-bold transition-all duration-300 ease-out brightness-0 md:w-1/6 hover:brightness-100">
        <x-logo class="flex-shrink-0 w-auto h-8"></x-logo>
      </a>
      <div class="grid w-full grid-cols-2 pt-2 mt-20 gap-y-16 sm:grid-cols-4 lg:gap-x-8 md:w-5/6 md:mt-0 md:pr-6">
        <div class="md:justify-self-end">
          <h3 class="font-semibold text-black">
            {{ __('Solutions') }}
          </h3>
          <ul class="mt-6 space-y-4 text-sm">
            <li>
              <a href="{{ route('tpe-pme') }}" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>Assistant Cyber</span>
              </a>
            </li>
            <li>
              <a href="#_" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>PSSI</span>
              </a>
            </li>
            <li>
              <a href="{{ route('pentest') }}" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>Pentest</span>
              </a>
            </li>
          </ul>
        </div>
        <div class="md:justify-self-end">
          <h3 class="font-semibold text-black">
            {{ __('Plans & pricing') }}
          </h3>
          <ul class="mt-6 space-y-4 text-sm">
            <li>
              <a href="{{ route('pricing') }}" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>{{ __('Pricing') }}</span>
              </a>
            </li>
          </ul>
        </div>
        <div class="md:justify-self-end">
          <h3 class="font-semibold text-black">
            {{ __('Company') }}
          </h3>
          <ul class="mt-6 space-y-4 text-sm">
            <li>
              <a href="{{ route('a-propos') }}" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>{{ __('About') }}</span>
              </a>
            </li>
          </ul>
        </div>
        <div class="md:justify-self-end">
          <h3 class="font-semibold text-black">
            {{ __('Resources') }}
          </h3>
          <ul class="mt-6 space-y-4 text-sm">
            <li>
              <a href="{{ route('blog') }}" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>Blog</span>
              </a>
            </li>
            <li>
              <a href="#_" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>NIS2</span>
              </a>
            </li>
            <li>
              <a href="#_" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>DORA</span>
              </a>
            </li>
            <li>
              <a href="#_" class="relative inline-block text-black group">
                <span
                  class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-1"></span>
                <span>Email</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    @endif
    -->
    <div class="flex flex-col items-center justify-between py-10 border-t border-solid lg:flex-row border-gray">
      <ul class="flex flex-wrap space-x-5 text-xs">
        <li class="mb-6 text-center flex-full lg:flex-none lg:mb-0">&copy; {{ date('Y') }} {{ setting('site.title') }}
        </li>
        <li class="lg:ml-6">
          <a href="{{ route('privacy-policy') }}" class="relative inline-block text-black group">
            <span
              class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-0"></span>
            <span>{{ __('Privacy Policy') }}</span>
          </a>
        </li>
        <li class="lg:ml-6">
          <a href="{{ route('terms') }}" class="relative inline-block text-black group">
            <span
              class="absolute bottom-0 w-full transition duration-150 ease-out transform -translate-y-1 border-b border-black opacity-0 group-hover:opacity-100 group-hover:translate-y-0"></span>
            <span>{{ __('Terms and Conditions') }}</span>
          </a>
        </li>
      </ul>
      <ul class="flex items-center mt-10 space-x-5 lg:mt-0">
        <li>
          <a href="{{ setting('site.facebook') }}" target="_blank"
             class="text-zinc-600 hover:text-zinc-900">
            <span class="sr-only">Facebook</span>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path fill-rule="evenodd"
                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                    clip-rule="evenodd"></path>
            </svg>
          </a>
        </li>
        <li>
          <a href="{{ setting('site.instagram') }}" class="text-zinc-600 hover:text-zinc-900" target="_blank">
            <span class="sr-only">Instagram</span>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path fill-rule="evenodd"
                    d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                    clip-rule="evenodd"></path>
            </svg>
          </a>
        </li>
        <li>
          <a href="{{ setting('site.linkedin') }}" class="text-zinc-600 hover:text-zinc-900" target="_blank">
            <span class="sr-only">LinkedIn</span>
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 382 382" aria-hidden="true">
              <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
              <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
              <g id="SVGRepo_iconCarrier">
                <path
                  d="M347.445,0H34.555C15.471,0,0,15.471,0,34.555v312.889C0,366.529,15.471,382,34.555,382h312.889 C366.529,382,382,366.529,382,347.444V34.555C382,15.471,366.529,0,347.445,0z M118.207,329.844c0,5.554-4.502,10.056-10.056,10.056 H65.345c-5.554,0-10.056-4.502-10.056-10.056V150.403c0-5.554,4.502-10.056,10.056-10.056h42.806 c5.554,0,10.056,4.502,10.056,10.056V329.844z M86.748,123.432c-22.459,0-40.666-18.207-40.666-40.666S64.289,42.1,86.748,42.1 s40.666,18.207,40.666,40.666S109.208,123.432,86.748,123.432z M341.91,330.654c0,5.106-4.14,9.246-9.246,9.246H286.73 c-5.106,0-9.246-4.14-9.246-9.246v-84.168c0-12.556,3.683-55.021-32.813-55.021c-28.309,0-34.051,29.066-35.204,42.11v97.079 c0,5.106-4.139,9.246-9.246,9.246h-44.426c-5.106,0-9.246-4.14-9.246-9.246V149.593c0-5.106,4.14-9.246,9.246-9.246h44.426 c5.106,0,9.246,4.14,9.246,9.246v15.655c10.497-15.753,26.097-27.912,59.312-27.912c73.552,0,73.131,68.716,73.131,106.472 L341.91,330.654L341.91,330.654z"></path>
              </g>
            </svg>
          </a>
        </li>
      </ul>
    </div>
  </x-container>
</footer>
