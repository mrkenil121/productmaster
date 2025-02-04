<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MoleculeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/signup', [AuthController::class, 'signup']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('molecules')->group(function () {
        Route::get('/', [MoleculeController::class, 'index']);
        Route::get('/active', [MoleculeController::class, 'getActive']);
        // Route::get('/with-products', [MoleculeController::class, 'getWithProducts']);
        Route::get('/{id}', [MoleculeController::class, 'show']);
        Route::post('/', [MoleculeController::class, 'store']);
        Route::put('/{id}', [MoleculeController::class, 'update']);
        Route::delete('/{id}', [MoleculeController::class, 'destroy']);
        Route::post('/{id}/restore', [MoleculeController::class, 'restore']);
        Route::delete('/{id}/force', [MoleculeController::class, 'forceDelete']);
    });

});