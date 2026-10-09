{{-- Flash message (error | warning | success | info), app severity colours. --}}
@php
$messageTypes = ['error', 'warning', 'success', 'info'];
$message = null;
$type = null;

foreach ($messageTypes as $messageType) {
    if (session()->has($messageType)) {
        $message = session($messageType);
        $type = $messageType;
        break;
    }
}
@endphp

@if($message)
    <div @class([
        'ui:mb-6 ui:rounded-xl ui:border ui:border-solid ui:p-4 ui:text-sm',
        'ui:border-red-200 ui:bg-critical-soft ui:text-red-800' => $type == 'error',
        'ui:border-amber-200 ui:bg-medium-soft ui:text-amber-800' => $type == 'warning',
        'ui:border-emerald-200 ui:bg-low-soft ui:text-emerald-800' => $type == 'success',
        'ui:border-blue-200 ui:bg-info-soft ui:text-blue-800' => $type == 'info',
    ]) role="alert">
        {{ $message }}
    </div>
@endif
