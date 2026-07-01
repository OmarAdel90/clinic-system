<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Modules\Lead\Requests\AssignLeadRequest;
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
