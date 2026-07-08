<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=0')->daily();
Schedule::command('visits:generate-confirmation-followups')->dailyAt('09:00');
