<div class="w-full flex items-center justify-start md:justify-center">
  <ul
    class="inline-flex self-start md:self-center px-3 py-2 mt-5 md:mt-7 w-auto text-xs font-medium rounded-full border bg-blue-100 border-blue-200 text-blue-600 border-blue-100">
    <li class="mr-4 font-bold uppercase text-blue-800 md:block hidden">
      {{ __('Categories') }}&nbsp;:
    </li>
    <li class="@if(!isset($category)){{ 'text-blue-800' }}@endif">
      <a href="{{ route('blog') }}">
        {{ __('All') }}
      </a>
    </li>
    <li class="mx-2">&middot;</li>
    @foreach(\Wave\Category::all() as $cat)
    <li
      class="@if(isset($category) && isset($category->slug) && ($category->slug == $cat->slug)){{ 'text-blue-800' }}@endif">
      <a href="{{ route('blog.category', ['category' => $cat]) }}">
        {{ $cat->name }}
      </a>
    </li>
    @if(!$loop->last)
    <li class="mx-2">&middot;</li>
    @endif
    @endforeach
  </ul>
</div>