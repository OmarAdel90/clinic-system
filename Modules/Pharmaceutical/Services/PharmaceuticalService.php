<?php
namespace Modules\Pharmaceutical\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Pharmaceutical\Models\Pharmaceutical;

class PharmaceuticalService
{
    public function getAll(): Collection
    {
        return Pharmaceutical::all();
    }

    public function get(string $sku): Pharmaceutical
    {
        $pharma = Pharmaceutical::where('SKU', $sku)->first();
        if (!$pharma) {
            throw new \Exception("Pharmaceutical with SKU '$sku' was not found", 404);
        }
        return $pharma;
    }

    public function create(array $data): Pharmaceutical
    {
        if (Pharmaceutical::where('SKU', $data['SKU'])->exists()) {
            throw new \Exception('Product already exists', 409);
        }
        return Pharmaceutical::create($data);
    }

    public function update(string $sku, array $data): Pharmaceutical
    {
        $pharma = Pharmaceutical::where('SKU', $sku)->first();
        if (!$pharma) {
            throw new \Exception("Pharmaceutical with SKU '$sku' was not found", 404);
        }
        $pharma->update($data);
        return $pharma->fresh();
    }

    public function delete(string $sku): void
    {
        $pharma = Pharmaceutical::where('SKU', $sku)->first();
        if (!$pharma) {
            throw new \Exception("Pharmaceutical with SKU '$sku' was not found", 404);
        }
        $pharma->delete();
    }
}