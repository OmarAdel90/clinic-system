<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Modules\Lead\Models\Lead;
use Modules\Lead\Requests\IndexLeadRequest;
use Modules\Lead\Requests\ShowLeadRequest;
use Modules\Lead\Requests\DestroyLeadRequest;
use Modules\Lead\Requests\AssignLeadRequest;
use Modules\Lead\Requests\StoreLeadRequest;
use Modules\Lead\Requests\UpdateLeadRequest;
use Modules\CRM\Services\CallCenterService;
use Illuminate\Http\JsonResponse;

class CallCenterController extends Controller
{
    public function __construct(protected CallCenterService $service) {}

    public function queue(): JsonResponse
    {
        return response()->json($this->service->getQueue());
    }

    public function nextInQueue(): JsonResponse
    {
        $user = $this->service->getNextInQueue();

        if (!$user) {
            return response()->json(['message' => 'Queue is empty.'], 404);
        }

        return response()->json($user);
    }

    public function assignNext(int $leadId): JsonResponse
    {
        try {
            $user = $this->service->assignNextLead($leadId);

            if (!$user) {
                return response()->json(['message' => 'Queue is empty. No user to assign to.'], 404);
            }

            return response()->json([
                'message' => 'Lead assigned successfully.',
                'user'    => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function assignToUser(AssignLeadRequest $request): JsonResponse
    {
        try {
            $user = $this->service->assignToUser(
                $request->validated()['lead_id'],
                $request->validated()['user_id']
            );

            return response()->json([
                'message' => 'Lead assigned successfully.',
                'user'    => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function leads(IndexLeadRequest $request): JsonResponse
    {
        return response()->json($this->service->getAllLeads($request->user()));
    }

    public function lead(ShowLeadRequest $request, int $id): JsonResponse
    {
        try {
            return response()->json($this->service->getLead($id, $request->user()));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lead not found.'], 404);
        }
    }

    public function storeLead(StoreLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->service->createLead($request->validated());
            return response()->json($lead, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateLead(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead->update($request->validated());
        return response()->json($lead->fresh());
    }

    public function destroyLead(DestroyLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead->delete();
        return response()->json(null, 204);
    }

    public function users(): JsonResponse
    {
        return response()->json($this->service->getCallCenterUsers());
    }

    public function addToQueue(int $userId): JsonResponse
    {
        try {
            $entry = $this->service->addToQueue($userId);
            return response()->json($entry, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function removeFromQueue(int $userId): JsonResponse
    {
        $this->service->removeFromQueue($userId);
        return response()->json(null, 204);
    }
}
