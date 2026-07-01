<?php

namespace Modules\Visit\Events;

use Modules\Visit\Models\Report;
use Modules\Visit\Models\Visit;
use Illuminate\Foundation\Events\Dispatchable;

class ReportCompleted
{
    use Dispatchable;

    public function __construct(
        public Report $report,
        public Visit $visit,
    ) {}
}
