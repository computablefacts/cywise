@once
<link rel="stylesheet" type="text/css" href="/cyber_buddy/botman/chat_2.css">
@endonce
<div class="card">
  @if($conversations->isEmpty())
  <div class="card-body">
    <div class="row">
      <div class="col">
        {{ __('None.') }}
      </div>
    </div>
  </div>
  @else
  <div class="card-body p-0">
    <table class="table table-hover no-bottom-margin">
      <thead>
      <tr>
        <th>{{ __('Thread Id') }}</th>
        <th>{{ __('Created At') }}</th>
        <th>{{ __('Updated At') }}</th>
        <th>{{ __('Created By') }}</th>
        <th>{{ __('Description') }}</th>
        <th></th>
      </tr>
      </thead>
      <tbody>
      @foreach($conversations as $conversation)
      <tr style="border-bottom-color:white">
        <td>
          <span class="lozenge new">{{ $conversation->thread_id }}</span>
        </td>
        <td>{{ $conversation->created_at->format('Y-m-d H:i') }}</td>
        <td>{{ $conversation->updated_at->format('Y-m-d H:i') }}</td>
        <td>{{ $conversation->createdBy()?->name }}</td>
        <td>{{ $conversation->description ?? '' }}</td>
        <td class="text-end">
          <a href="#" onclick="deleteConversation({{ $conversation->id }})" class="text-decoration-none"
             style="color:red">
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
          @if($conversation->format === \App\Models\Conversation::FORMAT_V0)
          &nbsp;&nbsp;&nbsp;&nbsp;
          <a data-bs-toggle="collapse" href="#conversation{{ $conversation->id }}" class="text-decoration-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M9 6l6 6l-6 6"/>
            </svg>
          </a>
          @elseif($conversation->format === \App\Models\Conversation::FORMAT_V1)
          &nbsp;&nbsp;&nbsp;&nbsp;
          <a href="{{ route('home', ['tab' => 'ama2', 'conversation_id' => $conversation->id]) }}"
             class="text-decoration-none"
             target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M9 6l6 6l-6 6"/>
            </svg>
          </a>
          @endif
        </td>
      </tr>
      @if($conversation->format === \App\Models\Conversation::FORMAT_V0)
      <tr class="collapse" id="conversation{{ $conversation->id }}">
        <td colspan="6" class="cb-conversation">
          {!! $conversation->dom !!}
        </td>
      </tr>
      @endif
      @endforeach
      </tbody>
    </table>
    <div class="row">
      <div class="col">
        <ul class="pagination justify-content-center mt-3 mb-3">
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('home', ['tab' => 'conversations', 'page' => 1]) }}">
              <span>&laquo;&nbsp;{{ __('First') }}</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('home', ['tab' => 'conversations', 'page' => $currentPage <= 1 ? 1 : $currentPage - 1]) }}">
              <span>&lt;&nbsp;{{ __('Previous') }}</span>
            </a>
          </li>
          <!--
          @if($currentPage > 1)
          <li class="page-item">
            <a class="page-link" href="{{ route('home', ['tab' => 'conversations', 'page' => $currentPage - 1]) }}">
              {{ $currentPage - 1 }}
            </a>
          </li>
          @endif
          -->
          <li class="page-item">
            <a class="page-link active"
               href="{{ route('home', ['tab' => 'conversations', 'page' => $currentPage]) }}">
              {{ $currentPage }}
            </a>
          </li>
          <!--
          @if($currentPage < $nbPages)
          <li class="page-item">
            <a class="page-link" href="{{ route('home', ['tab' => 'conversations', 'page' => $currentPage + 1]) }}">
              {{ $currentPage + 1 }}
            </a>
          </li>
          @endif
          -->
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('home', ['tab' => 'conversations', 'page' => $currentPage >= $nbPages ? $nbPages : $currentPage + 1])}}">
              <span>{{ __('Next') }}&nbsp;&gt;</span>
            </a>
          </li>
          <li class="page-item {{ $currentPage >= $nbPages ? 'disabled' : '' }}">
            <a class="page-link"
               href="{{ route('home', ['tab' => 'conversations', 'page' => $nbPages]) }}">
              <span>{{ __('Last') }}&nbsp;&raquo;</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
  @endif
</div>
<script>

  function deleteConversation(conversationId) {
    const response = confirm("{{ __('Are you sure you want to delete this conversation?') }}");
    if (response) {
      deleteConversationApiCall(conversationId, (response) => toaster.toastSuccess(response.msg));
    }
  }

</script>