<a href="{{ $href ?? '' }}" class="inline-flex items-center gap-x-2 px-4 py-2 text-xs font-medium rounded-full border bg-blue-100 border-blue-200 text-blue-600 transition hover:bg-blue-200 hover:text-blue-800 group">
    <svg class="h-4 w-4 transition-transform group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
    </svg>
    <span class="uppercase tracking-wider">{{ $text ?? '' }}</span>
</a>