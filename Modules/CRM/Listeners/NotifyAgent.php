<?php

namespace Modules\CRM\Listeners;

use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\FollowUp;
use Modules\Visit\Events\ReportCompleted;

class NotifyAgent
{
    public function handle(ReportCompleted $event): void
    {
        $visit = $event->visit;
        $report = $event->report;

        $conversation = Conversation::where('visit_id', $visit->id)->first();

        if (!$conversation || !$conversation->assigned_user_id) {
            return;
        }

        FollowUp::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $conversation->assigned_user_id,
            'due_at'          => now()->addHour(),
            'body'            => "Report completed for lead #{$report->lead_id}. Please follow up on upcoming appointments.",
        ]);
    }
}
