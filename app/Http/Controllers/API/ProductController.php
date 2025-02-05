<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductController extends Controller
{
    /**
     * Get all products with optional pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $products = Product::with(['creator', 'updater', 'publisher'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getById($id): JsonResponse
    {
        try {
            $product = Product::with(['creator', 'updater', 'publisher'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $product,
                'message' => 'Product retrieved successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException 
                    ? 'Product not found' 
                    : 'Failed to retrieve product',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Delete product by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            
            // Set deleted_by if you have access to authenticated user
            if (auth()->check()) {
                $product->deleted_by = auth()->id();
                $product->save();
            }
            
            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException 
                    ? 'Product not found' 
                    : 'Failed to delete product',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
