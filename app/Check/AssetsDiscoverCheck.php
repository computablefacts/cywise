<?php

namespace App\Check;

use App\Events\StartAssetsDiscover;
use Carbon\Carbon;
use Composer\InstalledVersions;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;

// See: https://spatie.be/docs/laravel-health/v1/basic-usage/creating-custom-checks
class AssetsDiscoverCheck extends Check
{
    protected string $rootCacheKey = 'health:checks:assetsDiscover';

    protected string $domain = 'cywise.io';
    protected int $failedDurationThresholdSeconds = 90;
    protected int $warningDurationThresholdSeconds = 45;

    public function cacheKey(string $cacheKey): self
    {
        $this->rootCacheKey = $cacheKey;

        return $this;
    }

    protected function getCacheKey(string $what): string
    {
        $domainKey = Str::camel(Str::replace('.', '-', $this->domain));

        return "{$this->rootCacheKey}:{$domainKey}:{$what}";
    }

    public function domain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getFailedDurationThresholdSeconds(): int
    {
        return $this->failedDurationThresholdSeconds;
    }

    public function getLastStart()
    {
        return cache()->get($this->getCacheKey('lastStart'));
    }

    public function setLastStart(?int $timestamp = null): void
    {
        if (is_null($timestamp)) {
            $timestamp = now()->timestamp;
        }

        cache()->set($this->getCacheKey('lastStart'), $timestamp);
    }

    public function getLastDuration(): int
    {
        return (int)cache()->get($this->getCacheKey('lastDuration'));
    }

    public function setLastDuration(float $durationInSeconds): void
    {
        $roundedDuration = (int)ceil($durationInSeconds);

        cache()->set($this->getCacheKey('lastDuration'), $roundedDuration);
    }

    public function getLastResponse(): array
    {
        return cache()->get($this->getCacheKey('lastResponse'), []);
    }

    public function setLastResponse(array $response)
    {
        cache()->set($this->getCacheKey('lastResponse'), $response);
    }

    protected function setLastStatus(Status $status)
    {
        cache()->set($this->getCacheKey('lastStatus'), $status);
    }

    protected function getLastStatus(): Status
    {
        return cache()->get($this->getCacheKey('lastStatus'), Status::ok());
    }

    public function run(): Result
    {
        if ($this->shouldStartAssetsDiscover()) {
            StartAssetsDiscover::dispatch($this);
        }

        return $this->lastAssetsDiscoverResult();
    }

    private function shouldStartAssetsDiscover(): bool
    {
        if ($this->lastStatusIs(Status::ok()) && $this->lastStartLessThanMinutes(15)) {
            return false;
        }
        if ($this->lastStatusIs(Status::warning()) && $this->lastStartLessThanMinutes(6)) {
            return false;
        }
        if ($this->lastStatusIs(Status::failed()) && $this->lastStartLessThanMinutes(3)) {
            return false;
        }
        return true;
    }

    private function lastAssetsDiscoverResult(): Result
    {
        if (!$this->getLastStart()) {
            return $this->getResult(Status::warning(), 'No check yet');
        }
        if (!$this->lastStartLessThanMinutes(120)) {
            return $this->getResult(Status::failed(), 'No check during the last 2 hours');
        }
        if (!$this->lastResponseIsCorrect()) {
            return $this->getResult(Status::failed(), 'Wrong response');
        }

        $duration = $this->getLastDuration();
        if ($duration > $this->failedDurationThresholdSeconds) {
            return $this->getResult(Status::failed(), "Failed: {$duration}s over {$this->failedDurationThresholdSeconds}s");
        }
        if ($duration > $this->warningDurationThresholdSeconds) {
            return $this->getResult(Status::warning(), "Warning: {$duration}s over {$this->warningDurationThresholdSeconds}s");
        }

        return $this->getResult(Status::ok(), "Ok: {$duration}s below {$this->warningDurationThresholdSeconds}s");
    }

    private function getResult(Status $status, string $message)
    {
        if ($timestamp = $this->getLastStart()) {
            $deltaHuman = Carbon::createFromTimestamp($timestamp)->diffForHumans();
            $message = "{$message} ({$deltaHuman})";
        }
        switch ($status) {
            case Status::failed():
                $this->setLastStatus($status);
                return Result::make()->failed($message);
            case Status::warning():
                $this->setLastStatus($status);
                return Result::make()->warning($message);
            case Status::ok():
                $this->setLastStatus($status);
                return Result::make()->ok($message);
            default:
                return Result::make()->failed('Unknown status');
        }
    }

    private function lastStatusIs(Status $status): bool
    {
        $lastStatus = $this->getLastStatus();

        return $lastStatus->equals($status);
    }

    private function lastResponseIsCorrect(): bool
    {
        $lastResponse = $this->getLastResponse();

        // TODO: add a check for 'error' => 0 ?
        return array_key_exists('subdomains', $lastResponse);
    }

    private function lastStartLessThanMinutes(int $minutes): bool
    {
        $lastStartTimestamp = $this->getLastStart();

        if (!$lastStartTimestamp) {
            return false;
        }

        $lastStartAt = Carbon::createFromTimestamp($lastStartTimestamp);
        $minutesAgo = $lastStartAt->diffInMinutes();

        $carbonVersion = InstalledVersions::getVersion('nesbot/carbon');
        if (version_compare($carbonVersion,
            '3.0.0', '<')) {
            $minutesAgo += 1;
        }

        return $minutesAgo < $minutes;
    }
}