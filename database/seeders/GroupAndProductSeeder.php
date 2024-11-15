<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Product;
use Carbon\Carbon;

class GroupAndProductSeeder extends Seeder
{
    public function run()
    {
        $groups = [
            ['name' => 'Молочные продукты', 'description' => 'Товары из молока'],
            ['name' => 'Мясные продукты', 'description' => 'Колбасы, мясо, сосиски'],
            ['name' => 'Овощи', 'description' => 'Свежие овощи и зелень'],
        ];

        foreach ($groups as $groupData) {
            $group = Group::create($groupData);

            for ($i = 1; $i <= 4; $i++) {
                Product::create([
                    'name' => "{$group->name} Продукт {$i}",
                    'price' => rand(100, 1000) / 10,
                    'group_id' => $group->id,
                    'manufacture_date' => Carbon::now()->subDays(rand(1, 30)),
                    'expiration_date' => Carbon::now()->addDays(rand(30, 365)),
                    'weight' => rand(100, 1000) / 10,
                    'additional_details' => json_encode(['note' => 'Пример данных']),
                ]);
            }
        }
    }
}

