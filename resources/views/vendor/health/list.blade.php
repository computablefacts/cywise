<html lang="en" class="{{$theme == 'dark' ? 'dark' : ''}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('health::notifications.health_results') }}</title>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    {{$assets}}
    <style>
        .hc-tooltip-anchor { position: relative; display: inline-flex; }
        .hc-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            width: 18rem;
            background: #111827;
            color: #f9fafb;
            font-size: 0.75rem;
            line-height: 1.4;
            padding: 0.625rem 0.75rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,.35);
            pointer-events: none;
            transition: opacity .15s ease;
            z-index: 50;
            white-space: normal;
            word-break: break-word;
        }
        .hc-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #111827;
        }
        .hc-tooltip-anchor:hover .hc-tooltip {
            visibility: visible;
            opacity: 1;
        }
        .hc-tooltip-message { font-weight: 600; margin-bottom: 0.375rem; }
        .hc-tooltip-title { font-weight: 700; margin-bottom: 0.25rem; color: #fbbf24; }
        .hc-tooltip-date { font-size: 0.7rem; color: #9ca3af; margin-bottom: 0.375rem; }
        .hc-tooltip-meta { border-top: 1px solid #374151; padding-top: 0.375rem; margin-top: 0.375rem; }
        .hc-tooltip-meta-first { margin-top: 0; }
        .hc-tooltip-meta-row { display: flex; gap: 0.375rem; }
        .hc-tooltip-meta-key { color: #9ca3af; flex-shrink: 0; }
        /* dark mode */
        .dark .hc-tooltip { background: #374151; }
        .dark .hc-tooltip::after { border-top-color: #374151; }
        .dark .hc-tooltip-meta { border-top-color: #4b5563; }
    </style>
</head>

<body class="antialiased bg-gray-100 mt-7 md:mt-12 dark:bg-gray-900">
    <div class="mx-auto max-w-7xl lg:px-8 sm:px-6">
        <div class="flex flex-wrap justify-center space-y-3">
            <h4 class="w-full text-2xl font-bold text-center text-gray-900 dark:text-white">{{ __('health::notifications.laravel_health') }}</h4>
            <div class="flex justify-center w-full">
                <x-health-logo/>
            </div>
            @if ($lastRanAt)
                <div class="{{ $lastRanAt->diffInMinutes() > ($staleThresholdMinutes ?? 5) ? 'text-red-400' : 'text-gray-400 dark:text-gray-500' }} text-sm text-center font-medium">
                    {{ __('health::notifications.check_results_from') }} {{ $lastRanAt->diffForHumans() }}
                </div>
            @endif
        </div>
        <div class="px-2 my-6 md:mt-8 md:px-0">
            @if (count($checkResults?->storedCheckResults ?? []))
                <dl class=" grid grid-cols-1 gap-2.5 sm:gap-3 md:gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($checkResults->storedCheckResults as $result)
                        @php
                            $lastFailure = ($lastFailures ?? collect())->get($result->name);
                            $hasCurrentTooltip = $result->status !== 'ok'
                                && (!empty($result->notificationMessage) || !empty($result->meta));
                            $hasLastFailureTooltip = $result->status === 'ok' && $lastFailure;
                        @endphp
                        <div class="flex items-start px-4 space-x-2 py-5 text-opacity-0 transition transform bg-white shadow-md shadow-gray-200 dark:shadow-black/25 dark:shadow-md dark:bg-gray-800 rounded-xl sm:p-6 md:space-x-3 md:min-h-[130px] dark:border-t dark:border-gray-700">
                            @if ($hasCurrentTooltip || $hasLastFailureTooltip)
                                <div class="hc-tooltip-anchor">
                                    <x-health-status-indicator :result="$result" />
                                    <div class="hc-tooltip {{ $hasLastFailureTooltip ? 'hc-tooltip--history' : '' }}">
                                        @if ($hasLastFailureTooltip)
                                            <div class="hc-tooltip-title">⚠ Dernier problème détecté</div>
                                            <div class="hc-tooltip-date">{{ $lastFailure->created_at->diffForHumans() }} — {{ $lastFailure->created_at->format('d/m/Y H:i') }}</div>
                                            @if (!empty($lastFailure->notification_message))
                                                <div class="hc-tooltip-message hc-tooltip-meta">{{ $lastFailure->notification_message }}</div>
                                            @endif
                                            @if (!empty($lastFailure->meta))
                                                <div class="{{ !empty($lastFailure->notification_message) ? 'hc-tooltip-meta' : 'hc-tooltip-meta-first' }}">
                                                    @foreach ($lastFailure->meta as $key => $value)
                                                        <div class="hc-tooltip-meta-row">
                                                            <span class="hc-tooltip-meta-key">{{ $key }}:</span>
                                                            <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            @if (!empty($result->notificationMessage))
                                                <div class="hc-tooltip-message">{{ $result->notificationMessage }}</div>
                                            @endif
                                            @if (!empty($result->meta))
                                                <div class="{{ !empty($result->notificationMessage) ? 'hc-tooltip-meta' : '' }}">
                                                    @foreach ($result->meta as $key => $value)
                                                        <div class="hc-tooltip-meta-row">
                                                            <span class="hc-tooltip-meta-key">{{ $key }}:</span>
                                                            <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                        <div class="absolute left-4 top-full border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                    </div>
                                </div>
                            @else
                                <x-health-status-indicator :result="$result" />
                            @endif
                            <div>
                                <dd class="-mt-1 font-bold text-gray-900 dark:text-white md:mt-1 md:text-xl">
                                    {{ $result->label }}
                                </dd>
                                <dt class="mt-0 text-sm font-medium text-gray-600 dark:text-gray-300 md:mt-1">
                                    @if (!empty($result->notificationMessage))
                                        {{ $result->notificationMessage }}
                                    @else
                                        {{ $result->shortSummary }}
                                    @endif
                                </dt>
                            </div>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>
</body>
</html>
