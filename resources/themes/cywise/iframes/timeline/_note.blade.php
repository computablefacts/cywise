{{--
  Note row: [icon] [author / subject / body / scopes] [date] [delete].
  Also rendered by NotesProcedure on creation: inserted at the top of the list by iframes/_scripts.
--}}
@php
  $fields = $note->attributes();
  $scopes = collect(json_decode($fields['scopes'] ?? "[]"))->map(fn($scope) => $scope === 'CyberBuddy' ? 'General' : $scope);
@endphp

{{-- data-search: date and scopes, searchable (x-ui.search) --}}
<li id="nid-{{ $note->id }}" data-search="{{ $date }} {{ $scopes->join(' ') }}"
    class="ui:flex ui:items-start ui:gap-4 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
  <span class="ui:flex ui:size-8 ui:shrink-0 ui:items-center ui:justify-center ui:rounded-full ui:bg-brand-50 ui:text-brand-500">
    <x-phosphor-note-pencil class="ui:size-4"/>
  </span>
  <div class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col ui:gap-1">
    <span class="ui:text-xs ui:text-slate-500">
      {!! __('<b>:user</b> created a <b>note</b>', [ 'user' => $user->name ]) !!}
    </span>
    @if(!empty($fields['subject']))
      <span class="ui:text-sm ui:font-semibold ui:text-ink">{{ $fields['subject'] }}</span>
    @endif

    {{-- Markdown body: tame the generated HTML margins --}}
    <div class="ui:overflow-x-auto ui:text-sm ui:text-slate-700 ui:[&_p]:my-1 ui:[&_ul]:my-1 ui:[&_ul]:pl-5 ui:[&_ol]:my-1 ui:[&_ol]:pl-5 ui:[&_a]:text-brand-600!">
      {!! (new Parsedown)->text($fields['body'] ?? '') !!}
    </div>
    @if($scopes->isNotEmpty())
      <div class="ui:flex ui:flex-wrap ui:gap-1 ui:pt-1">
        @foreach($scopes as $scope)
          <x-ui.tag>{{ $scope }}</x-ui.tag>
        @endforeach
      </div>
    @endif
  </div>
  <span class="ui:hidden ui:w-28 ui:shrink-0 ui:pt-1.5 ui:text-right ui:text-xs ui:text-slate-400 ui:md:block">{{ $date }} {{ $time }}</span>
  <x-ui.icon-button icon="trash" :title="__('Delete')" onclick="deleteNote('{{ $note->id }}')"/>
</li>
