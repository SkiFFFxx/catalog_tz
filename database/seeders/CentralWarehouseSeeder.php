<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentralWarehouseSeeder extends Seeder
{
    public function run()
    {
        // Добавляем склад, если его еще нет
        $centralWarehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'Центральный склад',
            'region' => 'Астана',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Получаем все товары
        $products = DB::table('products')->get();

        foreach ($products as $product) {
            DB::table('warehouse_product')->insert([
                'warehouse_id' => $centralWarehouseId,
                'product_id' => $product->id,
                'quantity' => 100, // 100 единиц товара
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

