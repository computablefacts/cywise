<?php

namespace App\Enums;

enum DemoStagesEnum: string
{
    case EMPTY = 'empty'; // No data: dashboard shows onboarding
    case SCAN = 'scan'; // Assets, vulnerabilities and leaks
    case AGENT = 'agent'; // SCAN + a server with an agent and its events
}
