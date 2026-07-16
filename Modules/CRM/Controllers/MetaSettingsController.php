<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CRM\Requests\UpdateMetaFacebookSettingsRequest;
use Modules\CRM\Requests\UpdateMetaWhatsappSettingsRequest;
use Modules\CRM\Services\MetaSettingsService;

class MetaSettingsController extends Controller
{
    public function __construct(protected MetaSettingsService $service) {}

    public function show(): JsonResponse
    {
        return response()->json($this->service->getSettings());
    }

    public function updateFacebookInstagram(UpdateMetaFacebookSettingsRequest $request): JsonResponse
    {
        return response()->json($this->service->updateFacebookInstagram($request->validated()));
    }

    public function updateWhatsapp(UpdateMetaWhatsappSettingsRequest $request): JsonResponse
    {
        return response()->json($this->service->updateWhatsapp($request->validated()));
    }
}
