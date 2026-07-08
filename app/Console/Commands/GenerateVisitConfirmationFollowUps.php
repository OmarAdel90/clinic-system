<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Models\FollowUp;
use Modules\Visit\Models\Visit;

class GenerateVisitConfirmationFollowUps extends Command
{
    protected $signature = 'visits:generate-confirmation-followups';

    protected $description = 'Create follow-up tasks for agents to reconfirm tomorrow\'s scheduled visits.';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow();
        $startOfDay = $tomorrow->copy()->startOfDay();
        $endOfDay = $tomorrow->copy()->endOfDay();
        $created = 0;

        $visits = Visit::with('conversation')
            ->where('status', 'scheduled')
            ->whereNull('confirmed_at')
            ->whereBetween('scheduled_date', [$startOfDay, $endOfDay])
            ->get();

        foreach ($visits as $visit) {
            $conversation = $visit->conversation;

            if (! $conversation || ! $conversation->assigned_user_id) {
                continue;
            }

            $body = $this->followUpBody($visit->id, $visit->lead_id, $visit->scheduled_date);

            $exists = FollowUp::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $conversation->assigned_user_id)
                ->whereNull('completed_at')
                ->where('body', $body)
                ->exists();

            if ($exists) {
                continue;
            }

            FollowUp::create([
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->assigned_user_id,
                'due_at' => now(),
                'body' => $body,
            ]);

            $created++;
        }

        Log::info('Generated visit confirmation follow-ups.', [
            'date' => $tomorrow->toDateString(),
            'created' => $created,
        ]);

        $this->info("Created {$created} visit confirmation follow-up(s).");

        return self::SUCCESS;
    }

    protected function followUpBody(int $visitId, ?int $leadId, ?Carbon $scheduledDate): string
    {
        $dateLabel = $scheduledDate?->format('Y-m-d H:i') ?? 'unscheduled time';

        return "[visit-confirmation:{$visitId}] Reconfirm tomorrow's visit for lead #{$leadId} scheduled at {$dateLabel}.";
    }
}
