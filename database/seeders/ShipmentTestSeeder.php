<?php

namespace Database\Seeders;

use App\Models\Shipment;
use Illuminate\Database\Seeder;

class ShipmentTestSeeder extends Seeder
{
    public function run(): void
    {
        Shipment::create([
            'code' => 'SH001',
            'status' => 'طلب شحن',
            'branch' => 'الفرع الرئيسى',
            'city' => 'القاهرة',
            'shipping_date' => '2026-07-10',
        ]);

        Shipment::create([
            'code' => 'SH002',
            'status' => 'مشحون',
            'branch' => 'الفرع الرئيسى',
            'city' => 'الإسكندرية',
            'shipping_date' => '2026-07-12',
        ]);

        Shipment::create([
            'code' => 'SH003',
            'status' => 'مسلم',
            'branch' => 'Tas',
            'city' => 'الجيزة',
            'shipping_date' => '2026-07-14',
        ]);
    }
}
