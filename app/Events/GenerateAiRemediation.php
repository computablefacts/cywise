<?php

namespace App\Events;

use App\Models\Alert;
use App\Models\Port;
use App\Models\Scan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GenerateAiRemediation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $scanId;
    public int $portId;
    public int $alertId;

    public function __construct(Scan $scan, Port $port, Alert $alert)
    {
        $this->scanId = $scan->id;
        $this->portId = $port->id;
        $this->alertId = $alert->id;
    }

    public function scan(): ?Scan
    {
        return Scan::find($this->scanId);
    }

    public function port(): ?Port
    {
        return Port::find($this->portId);
    }
    
    public function alert(): ?Alert
    {
        return Alert::find($this->alertId);
    }
}
