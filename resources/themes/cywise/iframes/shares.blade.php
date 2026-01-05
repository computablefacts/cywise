@extends('theme::iframes.app')

@section('content')
<div class="card mt-3 mb-3">
  @if($shares->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0">
    <table class="table mb-0">
      <thead>
      <tr>
        <th>{{ __('Shared to') }}</th>
        <th>{{ __('Tags') }}</th>
        <th class="text-end">{{ __('Number of Assets') }}</th>
        <th class="text-end">{{ __('Number of Vulnerabilities') }}</th>
        <th>{{ __('Shared by') }}</th>
        <th class="text-end">{{ __('Actions') }}</th>
      </tr>
      </thead>
      <tbody>
      @foreach($shares as $share)
      <tr>
        <td><span class="lozenge new">{{ $share['group'] }}</span></td>
        <td>
          @foreach($share['tags'] as $tag)
          <span class="lozenge information">{{ $tag }}</span>&nbsp;
          @endforeach
        </td>
        <td class="text-end">{{ $share['nb_assets'] }}</td>
        <td class="text-end">{{ $share['nb_vulnerabilities'] }}</td>
        <td>{{ $share['target'] }}</td>
        <td class="text-end">
          <a href="#" onclick="degroup('{{ $share['group'] }}')" class="text-decoration-none" style="color:red">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M4 7l16 0"/>
              <path d="M10 11l0 6"/>
              <path d="M14 11l0 6"/>
              <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
              <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
            </svg>
          </a>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
@endsection

@push('scripts')
<script>

  const degroup = (group) => {
    if (confirm("{{ __('Are you sure you want to remove this group?') }}")) {
      degroupApiCall(group, response => location.reload());
    }
  }

</script>
@endpush