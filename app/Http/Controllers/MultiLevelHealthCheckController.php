<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Spatie\Health\Health;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MultiLevelHealthCheckController extends Controller
{
    public function __construct(protected ResultStore $resultStore, protected Health $health) {}

    // -------------------------------------------------------------------------
    // Simple global result endpoints (compatible with Kubernetes probes)
    // Returns {"healthy": true} with 200, or throws 503 if any check failed
    // -------------------------------------------------------------------------

    public function critical(): Response
    {
        return $this->getSimpleResponse('critical', [
            'critical.DatabaseCheck',
            'critical.CacheCheck',
            'critical.ApiVulnerabilityScanner',
            'critical.QueueCritical',
            'critical.QueueMedium',
            'critical.QueueLow',
            'critical.QueueScout',
            'critical.QueueDefault',
            'critical.ScheduleCheck',
            'critical.UsedDiskSpaceCheck',
            'critical.cywise.ioAssetsDiscover',
        ]);
    }

    public function medium(): Response
    {
        return $this->getSimpleResponse('medium', [
            'medium.QueueCritical',
            'medium.QueueMedium',
            'medium.QueueLow',
            'medium.QueueScout',
            'medium.QueueDefault',
            'medium.ScheduleCheck',
            'medium.UsedDiskSpaceCheck',
            'medium.cywise.ioAssetsDiscover',
            'medium.DatabaseTableSizeCheck',
            'medium.DebugModeCheck',
            'medium.OptimizedAppCheck',
        ]);
    }

    public function info(): Response
    {
        return $this->getSimpleResponse('info', [
            'info.UsedDiskSpaceCheck',
            'info.DatabaseTableSizeCheck',
        ]);
    }

    // -------------------------------------------------------------------------
    // Detailed JSON result endpoints
    // -------------------------------------------------------------------------

    public function criticalJson(): JsonResponse
    {
        return $this->getJsonResponse('critical', [
            'critical.DatabaseCheck',
            'critical.CacheCheck',
            'critical.ApiVulnerabilityScanner',
            'critical.QueueCritical',
            'critical.QueueMedium',
            'critical.QueueLow',
            'critical.QueueScout',
            'critical.QueueDefault',
            'critical.ScheduleCheck',
            'critical.UsedDiskSpaceCheck',
            'critical.cywise.ioAssetsDiscover',
        ]);
    }

    public function mediumJson(): JsonResponse
    {
        return $this->getJsonResponse('medium', [
            'medium.QueueCritical',
            'medium.QueueMedium',
            'medium.QueueLow',
            'medium.QueueScout',
            'medium.QueueDefault',
            'medium.ScheduleCheck',
            'medium.UsedDiskSpaceCheck',
            'medium.cywise.ioAssetsDiscover',
            'medium.DatabaseTableSizeCheck',
            'medium.DebugModeCheck',
            'medium.OptimizedAppCheck',
        ]);
    }

    public function infoJson(): JsonResponse
    {
        return $this->getJsonResponse('info', [
            'info.UsedDiskSpaceCheck',
            'info.DatabaseTableSizeCheck',
        ]);
    }

    // -------------------------------------------------------------------------
    // HTML UI endpoints (same look as Spatie's /check-health/ui)
    // -------------------------------------------------------------------------

    public function criticalUi(): View
    {
        return $this->getUiResponse('critical', [
            'critical.DatabaseCheck',
            'critical.CacheCheck',
            'critical.ApiVulnerabilityScanner',
            'critical.QueueCritical',
            'critical.QueueMedium',
            'critical.QueueLow',
            'critical.QueueScout',
            'critical.QueueDefault',
            'critical.ScheduleCheck',
            'critical.UsedDiskSpaceCheck',
            'critical.cywise.ioAssetsDiscover',
        ]);
    }

    public function mediumUi(): View
    {
        return $this->getUiResponse('medium', [
            'medium.QueueCritical',
            'medium.QueueMedium',
            'medium.QueueLow',
            'medium.QueueScout',
            'medium.QueueDefault',
            'medium.ScheduleCheck',
            'medium.UsedDiskSpaceCheck',
            'medium.cywise.ioAssetsDiscover',
            'medium.DatabaseTableSizeCheck',
            'medium.DebugModeCheck',
            'medium.OptimizedAppCheck',
        ]);
    }

    public function infoUi(): View
    {
        return $this->getUiResponse('info', [
            'info.UsedDiskSpaceCheck',
            'info.DatabaseTableSizeCheck',
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    protected function getUiResponse(string $level, array $checkNames): View
    {
        $storedResults = $this->buildStoredCheckResults($level, $checkNames);

        $lastFailures = HealthCheckResultHistoryItem::query()
            ->whereIn('check_name', $checkNames)
            ->where('status', '!=', 'ok')
            ->orderByDesc('created_at')
            ->get()
            ->unique('check_name')
            ->keyBy('check_name');

        $intervalMinutes = config("health.level_refresh_intervals.{$level}", 5);

        return view('health::list', [
            'lastRanAt' => new Carbon($storedResults?->finishedAt),
            'checkResults' => $storedResults,
            'assets' => $this->health->assets(),
            'theme' => config('health.theme'),
            'lastFailures' => $lastFailures,
            'staleThresholdMinutes' => $intervalMinutes * 2,
        ]);
    }

    protected function buildStoredCheckResults(string $level, array $checkNames): ?StoredCheckResults
    {
        $cached = cache()->get("health:level_results:{$level}");

        if ($cached) {
            $items = collect($cached['checkResults'])
                ->filter(fn ($r) => in_array($r['name'], $checkNames))
                ->map(fn ($r) => new StoredCheckResult(
                    name: $r['name'],
                    label: $r['label'],
                    notificationMessage: $r['notificationMessage'],
                    shortSummary: $r['shortSummary'],
                    status: $r['status'],
                    meta: $r['meta'],
                ));

            return new StoredCheckResults(
                finishedAt: (new \DateTime)->setTimestamp($cached['finishedAt']),
                checkResults: $items,
            );
        }

        // Fallback: filter from the shared DB store
        $dbResults = $this->resultStore->latestResults();

        if (! $dbResults) {
            return null;
        }

        $filtered = $dbResults->storedCheckResults
            ->filter(fn ($r) => in_array($r->name, $checkNames));

        return new StoredCheckResults(
            finishedAt: $dbResults->finishedAt,
            checkResults: $filtered,
        );
    }

    protected function getSimpleResponse(string $level, array $checkNames): Response
    {
        $results = $this->loadResults($level, $checkNames);

        $hasFailures = $results->contains(fn ($r) => ($r['status'] ?? $r->status ?? null) === 'failed');

        if ($hasFailures) {
            throw new ServiceUnavailableHttpException(message: "Health check '{$level}' failed");
        }

        return response(['healthy' => true])
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    protected function getJsonResponse(string $level, array $checkNames): JsonResponse
    {
        $cached = cache()->get("health:level_results:{$level}");

        if ($cached) {
            return $this->buildResponseFromCache($level, $cached, $checkNames);
        }

        return $this->buildResponseFromStore($level, $checkNames);
    }

    /**
     * Load check results as a unified collection of arrays,
     * regardless of whether the source is the cache or the DB store.
     */
    protected function loadResults(string $level, array $checkNames): \Illuminate\Support\Collection
    {
        $cached = cache()->get("health:level_results:{$level}");

        if ($cached) {
            return collect($cached['checkResults'])
                ->filter(fn ($r) => in_array($r['name'], $checkNames));
        }

        $storedResults = $this->resultStore->latestResults();

        if (! $storedResults) {
            return collect();
        }

        return $storedResults->storedCheckResults
            ->filter(fn ($result) => in_array($result->name, $checkNames))
            ->map(fn ($result) => [
                'name' => $result->name,
                'status' => $result->status,
            ]);
    }

    protected function buildResponseFromCache(string $level, array $cached, array $checkNames): JsonResponse
    {
        $results = collect($cached['checkResults'])
            ->filter(fn ($r) => in_array($r['name'], $checkNames));

        $hasFailures = $results->contains(fn ($r) => $r['status'] === 'failed');
        $hasWarnings = $results->contains(fn ($r) => $r['status'] === 'warning');
        $overallStatus = $hasFailures ? 'failed' : ($hasWarnings ? 'warning' : 'ok');
        $statusCode = $hasFailures
            ? config('health.json_results_failure_status', 200)
            : 200;

        return response()->json([
            'finishedAt' => $cached['finishedAt'],
            'checkResults' => $results->values(),
        ], $statusCode);
    }

    protected function buildResponseFromStore(string $level, array $checkNames): JsonResponse
    {
        $storedResults = $this->resultStore->latestResults();

        if (! $storedResults) {
            return response()->json([
                'finishedAt' => null,
                'checkResults' => [],
            ], 200);
        }

        $filteredResults = $storedResults->storedCheckResults
            ->filter(fn ($result) => in_array($result->name, $checkNames));

        $hasFailures = $filteredResults->contains(fn ($result) => $result->status === 'failed');
        $statusCode = $hasFailures
            ? config('health.json_results_failure_status', 200)
            : 200;

        return response()->json([
            'finishedAt' => $storedResults->finishedAt->getTimestamp(),
            'checkResults' => $filteredResults->map(fn ($result) => [
                'name' => $result->name,
                'label' => $result->label,
                'notificationMessage' => $result->notificationMessage,
                'shortSummary' => $result->shortSummary,
                'status' => $result->status,
                'meta' => $result->meta,
            ])->values(),
        ], $statusCode);
    }
}
