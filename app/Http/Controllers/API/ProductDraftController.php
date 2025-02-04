<?php

namespace App\Http\Controllers\API;

use App\Models\ProductDraft;
use App\Http\Requests\StoreProductDraftRequest;
use App\Http\Requests\UpdateProductDraftRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductDraftController extends Controller
{
    /**
     * Display a listing of product drafts.
     */
    public function index(): JsonResponse
    {
        $productDrafts = ProductDraft::with(['category', 'creator', 'updater'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $productDrafts
        ]);
    }

    /**
     * Store a newly created product draft.
     */
    public function store(StoreProductDraftRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $validatedData['created_by'] = Auth::id();

            // Generate a unique combination if not provided
            if (!isset($validatedData['combination'])) {
                $validatedData['combination'] = $this->generateUniqueCombination();
            }

            $productDraft = ProductDraft::create($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product draft created successfully',
                'data' => $productDraft
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product draft.
     */
    public function show(ProductDraft $productDraft): JsonResponse
    {
        $productDraft->load(['category', 'creator', 'updater', 'publisher']);

        return response()->json([
            'success' => true,
            'data' => $productDraft
        ]);
    }

    /**
     * Update the specified product draft.
     */
    public function update(UpdateProductDraftRequest $request, ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $validatedData['updated_by'] = Auth::id();

            $productDraft->update($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product draft updated successfully',
                'data' => $productDraft
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete the specified product draft.
     */
    public function destroy(ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            $productDraft->deleted_by = Auth::id();
            $productDraft->save();
            $productDraft->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product draft soft deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to soft delete product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a product draft.
     */
    public function publish(ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate unique code when publishing
            $productDraft->code = $this->generateUniqueProductCode();
            $productDraft->publish_status = 'published';
            $productDraft->published_by = Auth::id();
            $productDraft->published_at = now();
            $productDraft->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product draft published successfully',
                'data' => $productDraft
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a unique product combination.
     */
    private function generateUniqueCombination(): string
    {
        do {
            $combination = 'DRAFT-' . strtoupper(bin2hex(random_bytes(4)));
        } while (ProductDraft::where('combination', $combination)->exists());

        return $combination;
    }

    /**
     * Generate a unique product code.
     */
    private function generateUniqueProductCode(): string
    {
        do {
            $code = 'PRD-' . strtoupper(bin2hex(random_bytes(4)));
        } while (ProductDraft::where('code', $code)->exists());

        return $code;
    }

    /**
     * Restore a soft-deleted product draft.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $productDraft = ProductDraft::withTrashed()->findOrFail($id);

            // Clear the deleted_by and reset other relevant fields
            $productDraft->deleted_by = null;
            $productDraft->restore();

            return response()->json([
                'success' => true,
                'message' => 'Product draft restored successfully',
                'data' => $productDraft
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
