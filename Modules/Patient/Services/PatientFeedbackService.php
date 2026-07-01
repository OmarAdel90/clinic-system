<?php

namespace Modules\Patient\Services;

use Modules\Auth\Models\User;
use Modules\Patient\Models\PatientFeedback;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PatientFeedbackService
{
    public function getAll(?User $user = null): Collection
    {
        try {
            $query = PatientFeedback::with(['lead', 'user', 'clinic']);

            if ($user && !$user->can('view_any_patient_feedback')) {
                $query->where('user_id', $user->id);
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

    public function get(int $id, ?User $user = null): PatientFeedback
    {
        try {
            $query = PatientFeedback::with(['lead', 'user', 'clinic']);

            if ($user && !$user->can('view_any_patient_feedback')) {
                $query->where('user_id', $user->id);
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

    public function getByLead(int $leadId): Collection
    {
        try {
            return PatientFeedback::with(['user', 'clinic'])
                ->where('lead_id', $leadId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): PatientFeedback
    {
        try {
            $data['user_id'] = auth()->id();

            return PatientFeedback::create($data);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(PatientFeedback $feedback, array $data): PatientFeedback
    {
        try {
            $feedback->update($data);
            return $feedback->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $feedback->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(PatientFeedback $feedback): void
    {
        try {
            $feedback->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $feedback->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
