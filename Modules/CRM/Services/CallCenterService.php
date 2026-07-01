<?php

namespace Modules\CRM\Services;

use Modules\Auth\Models\User;
use Modules\CRM\Models\AssignmentState;
use Modules\CRM\Models\CallCenterQueueEntry;
use Modules\CRM\Models\Conversation;
use Modules\Lead\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class CallCenterService
{
    public function getNextInQueue(): ?User
    {
        try {
            $entry = CallCenterQueueEntry::active()->ordered()->first();

            return $entry?->user;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getQueue(): array
    {
        try {
            return CallCenterQueueEntry::with('user.metrics')
                ->active()
                ->ordered()
                ->get()
                ->values()
                ->toArray();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addToQueue(int $userId): CallCenterQueueEntry
    {
        try {
            $maxPosition = CallCenterQueueEntry::active()->max('position') ?? 0;

            return CallCenterQueueEntry::create([
                'user_id'   => $userId,
                'position'  => $maxPosition + 1,
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function removeFromQueue(int $userId): void
    {
        try {
            CallCenterQueueEntry::where('user_id', $userId)
                ->update(['is_active' => false]);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function moveToBack(int $userId): void
    {
        try {
            $maxPosition = CallCenterQueueEntry::active()->max('position') ?? 0;

            CallCenterQueueEntry::where('user_id', $userId)
                ->where('is_active', true)
                ->update(['position' => $maxPosition + 1]);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignNextLead(int $leadId): ?User
    {
        try {
            return DB::transaction(function () use ($leadId) {
                $lead = Lead::findOrFail($leadId);

                $entry = CallCenterQueueEntry::active()->ordered()->first();

                if (!$entry) {
                    return null;
                }

                $user = $entry->user;

                if (!$user) {
                    return null;
                }

                AssignmentState::updateOrCreate(
                    ['lead_id' => $lead->id],
                    ['user_id' => $user->id]
                );

                $conversation = Conversation::where('lead_id', $lead->id)->first();
                if ($conversation) {
                    $conversation->update([
                        'assigned_user_id'  => $user->id,
                        'last_message_time' => now(),
                    ]);
                }

                $this->moveToBack($user->id);

                return $user;
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['lead_id' => $leadId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'lead_id' => $leadId]);
            throw $e;
        }
    }

    public function assignToUser(int $leadId, int $userId): User
    {
        try {
            return DB::transaction(function () use ($leadId, $userId) {
                $lead = Lead::findOrFail($leadId);
                $user = User::findOrFail($userId);

                AssignmentState::updateOrCreate(
                    ['lead_id' => $lead->id],
                    ['user_id' => $user->id]
                );

                $conversation = Conversation::where('lead_id', $lead->id)->first();
                if ($conversation) {
                    $conversation->update([
                        'assigned_user_id'  => $user->id,
                        'last_message_time' => now(),
                    ]);
                }

                return $user;
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['lead_id' => $leadId, 'user_id' => $userId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'lead_id' => $leadId, 'user_id' => $userId]);
            throw $e;
        }
    }

    public function createLead(array $data): Lead
    {
        try {
            return Lead::create($data);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getAllLeads(?User $user = null)
    {
        try {
            $query = Lead::with(['leadStatus', 'assignmentState.user', 'conversations']);

            if ($user && !$user->can('view_any_lead')) {
                $leadIds = $user->assignedConversations()->pluck('lead_id');
                $query->whereIn('id', $leadIds);
            }

            return $query->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getLead(int $id, ?User $user = null): Lead
    {
        try {
            $query = Lead::with(['leadStatus', 'assignmentState.user', 'conversations']);

            if ($user && !$user->can('view_any_lead')) {
                $leadIds = $user->assignedConversations()->pluck('lead_id');
                $query->whereIn('id', $leadIds);
            }

            return $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $id]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }
}
