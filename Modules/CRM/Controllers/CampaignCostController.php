<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\CampaignCost;
use Modules\CRM\Requests\IndexCampaignCostRequest;
use Modules\CRM\Requests\ShowCampaignCostRequest;
use Modules\CRM\Requests\StoreCampaignCostRequest;
use Modules\CRM\Requests\UpdateCampaignCostRequest;
use Modules\CRM\Requests\DestroyCampaignCostRequest;
use Modules\CRM\Services\CampaignCostService;
use Illuminate\Http\JsonResponse;

class CampaignCostController extends Controller
{
    public function __construct(protected CampaignCostService $service) {}

    public function index(IndexCampaignCostRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(ShowCampaignCostRequest $request, CampaignCost $campaignCost): JsonResponse
    {
        return response()->json($this->service->get($campaignCost->id));
    }

    public function store(StoreCampaignCostRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function update(UpdateCampaignCostRequest $request, CampaignCost $campaignCost): JsonResponse
    {
        return response()->json($this->service->update($campaignCost, $request->validated()));
    }

    public function destroy(DestroyCampaignCostRequest $request, CampaignCost $campaignCost): JsonResponse
    {
        $this->service->delete($campaignCost);
        return response()->json(null, 204);
    }
}
