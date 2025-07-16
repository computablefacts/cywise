<li class="timeline-item">
  <span class="timeline-item-hour">
    <span style="margin-left: -92px">{{ $time }}</span>
  </span>
  <span class="timeline-item-icon | faded-icon"
        style="background-color: var(--c-red) !important; color: white !important;">
    <span class="bp4-icon bp4-icon-person"></span>
  </span>
  <div class="timeline-item-wrapper">
    <div class="timeline-item-description">
      <span>
        {!! __('We have found <b>:count leaked or compromised</b> identifiers. If no action has been taken yet, ask the affected users to change their passwords.', [ 'count' => count(json_decode($leak->attributes()['credentials'])) ]) !!}
      </span>
    </div>
    <div class="comment p-0 mb-0">
      <table>
        <thead>
        <tr>
          <th>{{ __('Email') }}</th>
          <th>{{ __('Website') }}</th>
          <th>{{ __('Password') }}</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach(json_decode($leak->attributes()['credentials']) as $l)
        <tr>
          <td>{{ $l->email }}</td>
          <td>{{ empty($l->website) ? '-' : $l->website }}</td>
          <td>{{ empty($l->password) ? '-' : $l->password }}</td>
          <td>
          <span class="lozenge new" style="font-size: 0.8rem;">
            {{ empty($l->website) ? __('leak') : __('possible compromise') }}
          </span>
          </td>
        </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
</li>