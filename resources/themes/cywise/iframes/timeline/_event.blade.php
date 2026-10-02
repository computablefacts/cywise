{{-- System event row (score 0): what happened on the server. No severity colour. --}}
<li id="eid-{{ $event->id }}" data-severity="neutral"
    class="ui:flex ui:items-center ui:gap-4 ui:border-0 ui:border-t ui:border-solid ui:border-line ui:px-5 ui:py-3">
  <span class="ui:flex ui:min-w-0 ui:flex-1 ui:flex-col">
    <span class="ui:text-sm ui:text-ink">{{ $event->message() }}</span>
    <span class="ui:truncate ui:text-xs ui:text-slate-500">{{ $event->server_name }} ({{ $event->server_ip_address }})</span>
  </span>
  <span class="ui:hidden ui:w-28 ui:shrink-0 ui:text-right ui:text-xs ui:text-slate-400 ui:md:block">{{ $date }} {{ $time }}</span>
  <x-ui.icon-button icon="eye-slash" :title="__('Hide events like this for this server in the timeline.')" onclick="dismissEvent('{{ $event->id }}')"/>
</li>
