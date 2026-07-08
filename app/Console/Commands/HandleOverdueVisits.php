<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Models\FollowUp;
use Modules\Visit\Models\Visit;
use Modules\Visit\Services\VisitFlowService;

class HandleOverdueVisits extends Command
{
    protected $signature = 'visits:handle-overdue';

    protected $description = 'Automatically handle overdue visits and create reschedule follow-up tasks.';

    public function __construct(
        protected VisitFlowService $visitFlowService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $handled = 0;

        $visits = Visit::with('conversation')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<', now())
            ->get();

        foreach ($visits as $visit) {
            try {
                if ($visit->status === 'confirmed') {
                    $this->visitFlowService->markMissed($visit->id);
                    $this->createRescheduleFollowUp($visit, 'missed');
                    $handled++;
                    continue;
                }

                if ($visit->status === 'scheduled') {
                    $this->visitFlowService->cancelVisit($visit->id);
                    $this->createRescheduleFollowUp($visit, 'cancelled');
                    $handled++;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to auto-handle overdue visit.', [
                    'visit_id' => $visit->id,
                    'status' => $visit->status,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Handled overdue visits.', ['handled' => $handled]);
        $this->info("Handled {$handled} overdue visit(s).");

        return self::SUCCESS;
    }

    protected function createRescheduleFollowUp(Visit $visit, string $finalStatus): void
    {
        $conversation = $visit->conversation;

        if (! $conversation || ! $conversation->assigned_user_id) {
            return;
        }

        $body = $this->followUpBody($visit->id, $visit->lead_id, $finalStatus);

        $exists = FollowUp::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $conversation->assigned_user_id)
            ->whereNull('completed_at')
            ->where('body', $body)
            ->exists();

        if ($exists) {
            return;
        }

        FollowUp::create([
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->assigned_user_id,
            'due_at' => now(),
            'body' => $body,
        ]);
    }

    protected function followUpBody(int $visitId, ?int $leadId, string $finalStatus): string
    {
        return "[visit-reschedule:{$visitId}] Visit for lead #{$leadId} was marked {$finalStatus}. Contact the patient to reschedule if appropriate.";
    }
}
