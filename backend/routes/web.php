<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn() => response()->json(['name' => 'WMS Multi-Gudang API', 'version' => '1.0.0', 'status' => 'running']));
