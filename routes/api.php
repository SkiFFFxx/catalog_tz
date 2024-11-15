<?php

use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;


Route::post('/transfer', [WarehouseController::class, 'transfer']);
Route::get('/warehouses', [WarehouseController::class, 'getWarehouses']);
Route::get('/products', [WarehouseController::class, 'getProducts']);
Route::post('/warehouses', [WarehouseController::class, 'createWarehouse']);
Route::get('/warehouses/{id}/products', [WarehouseController::class, 'getProductsByWarehouse']);

Route::put('/products/{id}', [WarehouseController::class, 'updateProduct']);
Route::get('/history', [WarehouseController::class, 'getHistory']);

Route::get('/history/export/excel', [WarehouseController::class, 'exportHistoryToExcel']);
Route::get('/history/export/xml', [WarehouseController::class, 'exportHistoryToXml']);

