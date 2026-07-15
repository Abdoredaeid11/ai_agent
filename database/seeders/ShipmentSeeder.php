<?php

namespace Database\Seeders;

use App\Models\Shipment;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        Shipment::create([
            'code'=>'SH001',
            'status'=>'delayed',
            'branch'=>'Cairo',
            'city'=>'Cairo',
            'shipping_date'=>now()->subDays(7)
        ]);


        Shipment::create([
            'code'=>'SH002',
            'status'=>'delivered',
            'branch'=>'Giza',
            'city'=>'Giza',
            'shipping_date'=>now()->subDays(2)
        ]);


        Shipment::create([
            'code'=>'SH003',
            'status'=>'delayed',
            'branch'=>'Cairo',
            'city'=>'Cairo',
            'shipping_date'=>now()->subDays(10)
        ]);
    }
}