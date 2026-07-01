<?php

namespace Modules\Lead\Services;

use Modules\Auth\Models\User;
use Modules\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function getAll(?User $user = null): Collection
    {
        try {
            $query = Lead::with(['leadStatus', 'campaign', 'assignmentState.user', 'conversations']);

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

    public function get(int $id, ?User $user = null): Lead
    {
        try {
            $query = Lead::with(['leadStatus', 'campaign', 'assignmentState.user', 'conversations']);

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

    public function create(array $data): Lead
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

    public function update(Lead $lead, array $data): Lead
    {
        try {
            $lead->update($data);
            return $lead->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $lead->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Lead $lead): void
    {
        try {
            $lead->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $lead->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
