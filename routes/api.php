<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MoleculeController;
use App\Http\Controllers\API\ProductDraftController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\UserController;
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
        Route::get('/{id}', [MoleculeController::class, 'show']);
        Route::post('/', [MoleculeController::class, 'store']);
        Route::put('/{id}', [MoleculeController::class, 'update']);
        Route::delete('/{id}', [MoleculeController::class, 'destroy']);
        Route::post('/{id}/restore', [MoleculeController::class, 'restore']);
        Route::delete('/{id}/force', [MoleculeController::class, 'forceDelete']);
    });

    Route::prefix('product-drafts')->group(function () {
        
        Route::get('/', [ProductDraftController::class, 'index']);
        Route::get('/active', [ProductDraftController::class, 'getActive']);
        Route::get('/status/{status}', [ProductDraftController::class, 'getByStatus']);
        Route::get('/{productDraft}', [ProductDraftController::class, 'show']);
        Route::post('/', [ProductDraftController::class, 'store']);
        Route::put('/{productDraft}', [ProductDraftController::class, 'update']);
        Route::delete('/{productDraft}', [ProductDraftController::class, 'destroy']);
        Route::post('/{id}/restore', [ProductDraftController::class, 'restore']);
        Route::delete('/{productDraft}/force', [ProductDraftController::class, 'forceDelete']);
        Route::post('/{productDraft}/publish', [ProductDraftController::class, 'publish']);
    });

    Route::prefix('products')->group(function () {

        Route::get('/', [ProductController::class, 'getAll']);
        Route::get('/{id}', [ProductController::class, 'getById']);
        Route::delete('/{id}', [ProductController::class, 'delete']);
    });

   
    Route::prefix('users')->group(function () {

        Route::get('/', [UserController::class, 'getAll']);
        Route::get('/{id}', [UserController::class, 'getById']);
        Route::delete('/{id}', [UserController::class, 'delete']);
    });

});