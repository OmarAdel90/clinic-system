<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Requests\IndexCampaignRequest;
use Modules\CRM\Requests\ShowCampaignRequest;
use Modules\CRM\Requests\StoreCampaignRequest;
use Modules\CRM\Requests\UpdateCampaignRequest;
use Modules\CRM\Requests\DestroyCampaignRequest;
use Modules\CRM\Services\CampaignService;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(protected CampaignService $service) {}

    public function index(IndexCampaignRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(ShowCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        return response()->json($this->service->get($campaign->id));
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        return response()->json($this->service->update($campaign, $request->validated()));
    }

    public function destroy(DestroyCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->service->delete($campaign);
        return response()->json(null, 204);
    }
}
