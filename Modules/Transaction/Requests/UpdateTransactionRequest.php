<?php

namespace Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update_warehouse_supplier_transaction','web');
    }

    public function rules(): array
    {
        return [
            'warehouse_id'                  => 'sometimes|integer|exists:warehouses,id',
            'supplier_id'                   => 'sometimes|integer|exists:suppliers,id',
            'items_bought'                  => 'sometimes|required|array',
            'items_bought.*.sku'            => 'required_with:items_bought|string|max:100|exists:pharmaceuticals,SKU',
            'items_bought.*.name'           => 'required_with:items_bought|string|max:255',
            'items_bought.*.arabic_name'    => 'nullable|string|max:255',
            'items_bought.*.quantity'       => 'required_with:items_bought|integer|min:1',
            'items_bought.*.price'          => 'required_with:items_bought|numeric|min:0',
            'transaction_date'              => 'sometimes|required|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items_bought', []);

            if (!$items) {
                return;
            }

            $skus = [];

            foreach ($items as $i => $item) {
                $sku = $item['sku'] ?? null;

                if ($sku && in_array($sku, $skus)) {
                    $validator->errors()->add(
                        "items_bought.{$i}.sku",
                        "Duplicate SKU '{$sku}' in the same batch."
                    );
                }

                $skus[] = $sku;
            }
        });
    }
}
