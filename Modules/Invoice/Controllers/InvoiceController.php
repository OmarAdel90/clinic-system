<?php

namespace Modules\Invoice\Controllers;

use App\Http\Controllers\Controller;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Requests\IndexInvoiceRequest;
use Modules\Invoice\Requests\ShowInvoiceRequest;
use Modules\Invoice\Requests\InvoicePayRequest;
use Modules\Invoice\Requests\StoreInvoiceRequest;
use Modules\Invoice\Requests\UpdateInvoiceRequest;
use Modules\Invoice\Requests\DestroyInvoiceRequest;
use Modules\Invoice\Services\InvoiceService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $service) {}

    public function index(IndexInvoiceRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user()));
    }

    public function show(ShowInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        return response()->json($this->service->get($invoice->id, $request->user()));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        return response()->json($this->service->update(new Invoice(), $request->validated()), 201);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        return response()->json($this->service->update($invoice, $request->validated()));
    }

    public function destroy(DestroyInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->service->delete($invoice);
        return response()->json(null, 204);
    }

    public function pay(InvoicePayRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            return response()->json($this->service->recordPayment($invoice, $request->validated()['amount']));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
