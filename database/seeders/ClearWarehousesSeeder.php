<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearWarehousesSeeder extends Seeder
{
    public function run()
    {
        DB::table('warehouse_product')->delete();

        DB::table('warehouses')->delete();
    }
}
