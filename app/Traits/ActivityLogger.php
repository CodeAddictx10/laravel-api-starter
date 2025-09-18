<?php

declare(strict_types=1);

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait ActivityLogger
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->setDescriptionForEvent(fn (string $eventName) => "The '{$eventName}' event has been performed on this record.")->logUnguarded()->logOnlyDirty();
    }
}
