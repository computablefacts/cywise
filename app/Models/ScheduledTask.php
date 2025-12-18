<?php

namespace App\Models;

use App\Traits\HasTenant;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\CronTranslator\CronTranslator;

/**
 * @property int id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property ?string name
 * @property string cron
 * @property string trigger
 * @property string task
 * @property bool enabled
 * @property ?Carbon prev_run_date
 * @property ?Carbon next_run_date
 * @property int created_by
 */
class ScheduledTask extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'cb_scheduled_tasks';

    protected $fillable = [
        'name',
        'cron',
        'trigger',
        'task',
        'enabled',
        'prev_run_date',
        'next_run_date',
        'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'prev_run_date' => 'datetime',
        'next_run_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cron(): CronExpression
    {
        return new CronExpression($this->cron);
    }

    public function readableCron(): string
    {
        return CronTranslator::translate($this->cron);
    }
}
