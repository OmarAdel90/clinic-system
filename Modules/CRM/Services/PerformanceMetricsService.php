<?php

namespace Modules\CRM\Services;

use Modules\Auth\Models\User;
use Modules\CRM\Models\CallCenterPerformanceMetrics;
use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\FollowUp;
use Modules\CRM\Models\Message;
use Modules\Visit\Models\Visit;

class PerformanceMetricsService
{
    public function getForUser(User $user): array
    {
        $conversationIds = Conversation::query()
            ->where('assigned_user_id', $user->id)
            ->pluck('id');

        $assignedLeads = Conversation::query()
            ->where('assigned_user_id', $user->id)
            ->distinct('lead_id')
            ->count('lead_id');

        $convertedLeads = Conversation::query()
            ->where('assigned_user_id', $user->id)
            ->whereNotNull('converted_at')
            ->distinct('lead_id')
            ->count('lead_id');

        $totalReminders = FollowUp::query()
            ->where('user_id', $user->id)
            ->count();

        $completedReminders = FollowUp::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        $customerAttendance = Visit::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $averageResponseTime = $this->calculateAverageResponseTimeInMinutes($conversationIds->all(), $user->id);

        $snapshot = CallCenterPerformanceMetrics::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => now()->toDateString(),
            ],
            [
                'average_response_time' => $averageResponseTime,
                'total_number_of_leads' => $assignedLeads,
                'total_converted_leads' => $convertedLeads,
                'total_reminders' => $totalReminders,
                'total_customer_attendance' => $customerAttendance,
            ]
        );

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'average_response_time' => $averageResponseTime,
            'total_number_of_leads' => $assignedLeads,
            'total_converted_leads' => $convertedLeads,
            'total_reminders' => $totalReminders,
            'completed_reminders' => $completedReminders,
            'pending_reminders' => max($totalReminders - $completedReminders, 0),
            'total_customer_attendance' => $customerAttendance,
            'snapshot_date' => optional($snapshot->date)->toDateString(),
        ];
    }

    protected function calculateAverageResponseTimeInMinutes(array $conversationIds, int $userId): ?float
    {
        if (empty($conversationIds)) {
            return null;
        }

        $cutoff = now()->subDays(30);
        $totalMinutes = 0.0;
        $samples = 0;

        foreach ($conversationIds as $conversationId) {
            $recentInbound = Message::query()
                ->where('conversation_id', $conversationId)
                ->where('direction', 'inbound')
                ->where(function ($query) use ($cutoff) {
                    $query->where('sent_at', '>=', $cutoff)
                        ->orWhere('created_at', '>=', $cutoff);
                })
                ->orderByDesc('sent_at')
                ->orderByDesc('created_at')
                ->first();

            if (! $recentInbound) {
                continue;
            }

            $inboundAt = $recentInbound->sent_at ?? $recentInbound->created_at;
            if (! $inboundAt) {
                continue;
            }

            $firstOutbound = Message::query()
                ->where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->where('direction', 'outbound')
                ->where(function ($query) use ($inboundAt) {
                    $query->where('sent_at', '>=', $inboundAt)
                        ->orWhere('created_at', '>=', $inboundAt);
                })
                ->orderBy('sent_at')
                ->orderBy('created_at')
                ->first();

            if (! $firstOutbound) {
                continue;
            }

            $outboundAt = $firstOutbound->sent_at ?? $firstOutbound->created_at;

            if (! $inboundAt || ! $outboundAt || $outboundAt->lt($inboundAt)) {
                continue;
            }

            $totalMinutes += $inboundAt->diffInSeconds($outboundAt) / 60;
            $samples++;
        }

        if ($samples === 0) {
            return null;
        }

        return round($totalMinutes / $samples, 2);
    }
}
