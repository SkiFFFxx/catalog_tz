<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        $fromWarehouseId = $validated['from_warehouse_id'];
        $toWarehouseId = $validated['to_warehouse_id'];
        $productId = $validated['product_id'];
        $quantity = $validated['quantity'];
        DB::beginTransaction();
        try {
            $fromWarehouseProduct = DB::table('warehouse_product')
                ->where('warehouse_id', $fromWarehouseId)
                ->where('product_id', $productId)
                ->first();
            if (!$fromWarehouseProduct || $fromWarehouseProduct->quantity < $quantity) {
                return response()->json(['error' => 'Недостаточно товара на складе A'], 400);
            }
            DB::table('warehouse_product')
                ->where('warehouse_id', $fromWarehouseId)
                ->where('product_id', $productId)
                ->update([
                    'quantity' => $fromWarehouseProduct->quantity - $quantity,
                ]);
            $toWarehouseProduct = DB::table('warehouse_product')
                ->where('warehouse_id', $toWarehouseId)
                ->where('product_id', $productId)
                ->first();
            if ($toWarehouseProduct) {
                // Увеличиваем количество товара на складе B
                DB::table('warehouse_product')
                    ->where('warehouse_id', $toWarehouseId)
                    ->where('product_id', $productId)
                    ->update([
                        'quantity' => $toWarehouseProduct->quantity + $quantity,
                    ]);
            } else {
                DB::table('warehouse_product')->insert([
                    'warehouse_id' => $toWarehouseId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('history')->insert([
                'product_id' => $productId,
                'from_warehouse' => $fromWarehouseId,
                'to_warehouse' => $toWarehouseId,
                'quantity' => $quantity,
                'action' => 'transfer',
                'details' => 'Перенос товара',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Перенос выполнен успешно'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ошибка при переносе: ' . $e->getMessage()], 500);
        }
    }


    public function getWarehouses()
    {
        $warehouses = DB::table('warehouses')->select('id', 'name', 'region')->get();

        return response()->json([
            'data' => $warehouses,
        ], 200);
    }


    public function getProducts()
    {
        try {
            $products = DB::table('products')->select('id', 'name', 'price')->get();

            return response()->json([
                'data' => $products,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching products', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }


    public function createWarehouse(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'required|string|max:255',
        ]);

        try {
            $warehouse = DB::table('warehouses')->insertGetId([
                'name' => $validated['name'],
                'region' => $validated['region'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Склад успешно создан',
                'warehouse_id' => $warehouse,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при создании склада',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function getProductsByWarehouse($id)
    {
        $warehouse = DB::table('warehouses')->find($id);

        if (!$warehouse) {
            return response()->json(['error' => 'Склад не найден'], 404);
        }

        $products = DB::table('warehouse_product')
            ->join('products', 'warehouse_product.product_id', '=', 'products.id')
            ->where('warehouse_product.warehouse_id', $id)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.price',
                'warehouse_product.quantity'
            )
            ->get();

        return response()->json([
            'warehouse_id' => $id,
            'warehouse_name' => $warehouse->name,
            'products' => $products,
        ], 200);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = DB::table('products')->find($id);

        if (!$product) {
            return response()->json(['error' => 'Товар не найден'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'manufacture_date' => 'sometimes|date',
            'expiration_date' => 'sometimes|date|after:manufacture_date',
            'weight' => 'sometimes|numeric|min:0',
            'additional_details' => 'sometimes|json',
        ]);
        try {
            // Обновление товара
            DB::table('products')
                ->where('id', $id)
                ->update(array_merge(
                    $validated,
                    ['updated_at' => now()]
                ));


            DB::table('history')->insert([
                'product_id' => $id,
                'from_warehouse' => null,
                'to_warehouse' => null,
                'quantity' => 0,
                'action' => 'update',
                'details' => json_encode($validated),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Товар успешно обновлён',
                'product_id' => $id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при обновлении товара',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function getHistory(Request $request)
    {
        try {
            $transfers = DB::table('history')
                ->where('action', 'transfer')
                ->join('products', 'history.product_id', '=', 'products.id')
                ->join('warehouses as from_warehouse', 'history.from_warehouse', '=', 'from_warehouse.id')
                ->join('warehouses as to_warehouse', 'history.to_warehouse', '=', 'to_warehouse.id')
                ->select(
                    'history.id as history_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'from_warehouse.name as from_warehouse_name',
                    'to_warehouse.name as to_warehouse_name',
                    'history.quantity',
                    'history.created_at as transfer_date'
                )
                ->get();

            $updates = DB::table('history')
                ->where('action', 'update')
                ->join('products', 'history.product_id', '=', 'products.id')
                ->select(
                    'history.id as history_id',
                    'products.id as product_id',
                    'products.name as product_name',
                    'history.details',
                    'history.created_at as update_date'
                )
                ->get();

            return response()->json([
                'transfers' => $transfers,
                'updates' => $updates,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при получении истории',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function exportHistoryToExcel()
    {
        $history = DB::table('history')
            ->join('products', 'history.product_id', '=', 'products.id')
            ->leftJoin('warehouses as from_warehouse', 'history.from_warehouse', '=', 'from_warehouse.id')
            ->leftJoin('warehouses as to_warehouse', 'history.to_warehouse', '=', 'to_warehouse.id')
            ->select(
                'history.id as history_id',
                'products.name as product_name',
                'from_warehouse.name as from_warehouse_name',
                'to_warehouse.name as to_warehouse_name',
                'history.quantity',
                'history.action',
                'history.details',
                'history.created_at as date'
            )
            ->get()
            ->toArray();

        return Excel::download(new class($history) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        }, 'history_report.xlsx');}


        public
        function exportHistoryToXml()
        {
            $history = DB::table('history')
                ->join('products', 'history.product_id', '=', 'products.id')
                ->leftJoin('warehouses as from_warehouse', 'history.from_warehouse', '=', 'from_warehouse.id')
                ->leftJoin('warehouses as to_warehouse', 'history.to_warehouse', '=', 'to_warehouse.id')
                ->select(
                    'history.id as history_id',
                    'products.name as product_name',
                    'from_warehouse.name as from_warehouse_name',
                    'to_warehouse.name as to_warehouse_name',
                    'history.quantity',
                    'history.action',
                    'history.details',
                    'history.created_at as date'
                )
                ->get();

            $xml = new \SimpleXMLElement('<history/>');

            foreach ($history as $item) {
                $entry = $xml->addChild('entry');
                $entry->addChild('history_id', $item->history_id);
                $entry->addChild('product_name', $item->product_name);
                $entry->addChild('from_warehouse_name', $item->from_warehouse_name);
                $entry->addChild('to_warehouse_name', $item->to_warehouse_name);
                $entry->addChild('quantity', $item->quantity);
                $entry->addChild('action', $item->action);
                $entry->addChild('details', $item->details);
                $entry->addChild('date', $item->date);
            }

            return response($xml->asXML(), 200)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="history_report.xml"');
        }


}
