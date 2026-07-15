<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\Log;

class ShipmentSearchService
{
    public function search(array $filters = []): array
    {
        Log::info('Shipment search started', ['filters' => $filters]);

        $query = Shipment::query();

        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query->where($key, 'like', "%$value%");
            }
        }

        $results = $query->get()->toArray();

        Log::info('Shipment search finished', ['count' => count($results)]);

        return $results;
    }
}
