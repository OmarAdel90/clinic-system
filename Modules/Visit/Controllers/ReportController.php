<?php

namespace Modules\Visit\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Visit\Models\Report;
use Modules\Visit\Requests\IndexReportRequest;
use Modules\Visit\Requests\ShowReportRequest;
use Modules\Visit\Requests\UpdateReportRequest;
use Modules\Visit\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(protected ReportService $service) {}

    public function index(IndexReportRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user(), $request->validated()));
    }

    public function show(ShowReportRequest $request, Report $report): JsonResponse
    {
        return response()->json($this->service->get($report->id, $request->user()));
    }

    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        return response()->json($this->service->update($report, $request->validated(), $request->user()));
    }
}
