<?php

namespace Database\Seeders;

use App\Models\Shipment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShipmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shipments = [
            ['code' => 'SH001', 'status' => 'delayed', 'branch' => 'Cairo', 'city' => 'Cairo', 'shipping_date' => '2026-07-01'],
            ['code' => 'SH002', 'status' => 'delayed', 'branch' => 'Cairo', 'city' => 'Cairo', 'shipping_date' => '2026-07-02'],
            ['code' => 'SH003', 'status' => 'pending', 'branch' => 'Giza', 'city' => 'Giza', 'shipping_date' => '2026-07-10'],
            ['code' => 'SH004', 'status' => 'delivered', 'branch' => 'Alexandria', 'city' => 'Alexandria', 'shipping_date' => '2026-07-05'],
            ['code' => 'SH005', 'status' => 'pending', 'branch' => 'Cairo', 'city' => 'Cairo', 'shipping_date' => '2026-07-11'],
        ];

        foreach ($shipments as $shipment) {
            Shipment::create($shipment);
        }
    }
}
