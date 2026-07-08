<?php

namespace Modules\Lead\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Lead\Models\LeadStatus;
use Modules\Lead\Requests\DestroyLeadStatusRequest;
use Modules\Lead\Requests\IndexLeadStatusRequest;
use Modules\Lead\Requests\ShowLeadStatusRequest;
use Modules\Lead\Requests\StoreLeadStatusRequest;
use Modules\Lead\Requests\UpdateLeadStatusRequest;

class LeadStatusController extends Controller
{
    public function index(IndexLeadStatusRequest $request): JsonResponse
    {
        $statuses = LeadStatus::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return response()->json($statuses);
    }

    public function show(ShowLeadStatusRequest $request, LeadStatus $leadStatus): JsonResponse
    {
        return response()->json($leadStatus);
    }

    public function store(StoreLeadStatusRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['key'] = $payload['key'] ?? Str::slug($payload['label']);

        $status = LeadStatus::create($payload);

        return response()->json($status, 201);
    }

    public function update(UpdateLeadStatusRequest $request, LeadStatus $leadStatus): JsonResponse
    {
        $payload = $request->validated();

        if (array_key_exists('label', $payload) && ! array_key_exists('key', $payload)) {
            $payload['key'] = $leadStatus->key ?: Str::slug($payload['label']);
        }

        $leadStatus->update($payload);

        return response()->json($leadStatus->fresh());
    }

    public function destroy(DestroyLeadStatusRequest $request, LeadStatus $leadStatus): JsonResponse
    {
        if ($leadStatus->leads()->exists()) {
            return response()->json([
                'message' => 'This status is currently assigned to one or more leads and cannot be deleted.',
            ], 422);
        }

        $leadStatus->delete();

        return response()->json(null, 204);
    }
}
