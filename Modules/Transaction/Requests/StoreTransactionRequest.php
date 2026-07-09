<?php

namespace Modules\Transaction\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_warehouse_supplier_transaction','web');
    }

    public function rules(): array
    {
        return [
            'warehouse_id'                  => 'required|integer|exists:warehouses,id',
            'supplier_id'                   => 'required|integer|exists:suppliers,id',
            'items_bought'                  => 'required|array',
            'items_bought.*.sku'            => 'required|string|max:100|exists:pharmaceuticals,SKU',
            'items_bought.*.name'           => 'required|string|max:255',
            'items_bought.*.arabic_name'    => 'nullable|string|max:255',
            'items_bought.*.quantity'       => 'required|integer|min:1',
            'items_bought.*.price'          => 'required|numeric|min:0',
            'transaction_date'              => 'required|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items_bought', []);
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
