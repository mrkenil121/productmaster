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

    Route::prefix('product-drafts')->group(function () {
        
        // List all product drafts
        Route::get('/', [ProductDraftController::class, 'index']);
    
        // Get active product drafts
        Route::get('/active', [ProductDraftController::class, 'getActive']);
    
        // Get drafts by publish status
        Route::get('/status/{status}', [ProductDraftController::class, 'getByStatus']);
    
        // Get a single product draft by ID
        Route::get('/{productDraft}', [ProductDraftController::class, 'show']);
    
        // Create a new product draft
        Route::post('/', [ProductDraftController::class, 'store']);
    
        // Update an existing product draft
        Route::put('/{productDraft}', [ProductDraftController::class, 'update']);
    
        // Soft delete a product draft
        Route::delete('/{productDraft}', [ProductDraftController::class, 'destroy']);
    
        // Restore a soft-deleted product draft
        Route::post('/{id}/restore', [ProductDraftController::class, 'restore']);
    
        // Force delete a product draft (permanent deletion)
        Route::delete('/{productDraft}/force', [ProductDraftController::class, 'forceDelete']);
    
        // Publish a product draft
        Route::post('/{productDraft}/publish', [ProductDraftController::class, 'publish']);
    
        // Unpublish a published product draft
        Route::post('/{productDraft}/unpublish', [ProductDraftController::class, 'unpublish']);
    
        // Bulk operations
        Route::post('/bulk-publish', [ProductDraftController::class, 'bulkPublish']);
        Route::post('/bulk-delete', [ProductDraftController::class, 'bulkDelete']);
        Route::post('/bulk-restore', [ProductDraftController::class, 'bulkRestore']);
    });

});