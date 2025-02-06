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
     * Get all products with optional pagination and search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $searchBy = $request->query('search_by', 'name'); // Default to searching by name

            $query = Product::query();

            if ($search) {
                $query->where($searchBy, 'LIKE', "%{$search}%");
            }

            $products = $query->paginate(10);

            return response()->json($products);
        } catch (Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json(['error' => 'Error fetching products'], 500);
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
