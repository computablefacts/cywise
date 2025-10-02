<?php

namespace App\Listeners;

use App\Events\SendAuditReport;
use App\Mail\AuditReport;
use App\Mail\MailCoachAuditReport;

class SendAuditReportListener extends AbstractListener
{
    public function viaQueue(): string
    {
        return self::CRITICAL;
    }

    protected function handle2($event)
    {
        if (!($event instanceof SendAuditReport)) {
            throw new \Exception('Invalid event type!');
        }

        $user = $event->user;
        $user->actAs(); // otherwise the tenant will not be properly set
        $report = AuditReport::create();

        if (!$report['is_empty']) {
            MailCoachAuditReport::sendEmail($report['report']);
        }
    }
}
