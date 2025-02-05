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
use App\Models\Molecule;
Use App\Jobs\PublishProduct;

trait ProductDraftCombinationTrait
{
    /**
     * Generate combination string from molecule IDs.
     *
     * @param array $moleculeIds
     * @return string|null
     */
    private function generateCombinationString(array $moleculeIds): ?string
    {
        // Remove duplicate and null/empty values
        $moleculeIds = array_unique(array_filter($moleculeIds));

        if (empty($moleculeIds)) {
            return null;
        }

        // Validate all molecules exist and are active
        $validMolecules = Molecule::whereIn('id', $moleculeIds)
            ->where('is_active', true)
            ->get();

        // Check if all input molecules are valid and active
        if ($validMolecules->count() !== count($moleculeIds)) {
            return null;
        }

        // Sort molecule names to ensure consistent combination string
        $moleculeNames = $validMolecules->pluck('name')->sort()->values();

        // Join molecule names with '+'
        return $moleculeNames->join('+');
    }

    /**
     * Validate molecule IDs during product draft creation/update.
     *
     * @param array $moleculeIds
     * @return bool
     */
    private function validateMoleculeIds(array $moleculeIds): bool
    {
        $validMolecules = Molecule::whereIn('id', $moleculeIds)
            ->where('is_active', true)
            ->count();

        return $validMolecules === count($moleculeIds);
    }
}


class ProductDraftController extends Controller
{

    use ProductDraftCombinationTrait;

    /**
     * Display a listing of product drafts.
     */
    public function index(): JsonResponse
    {
        try {
            // Use Cache::remember to cache the product drafts for 6000 seconds (100 minutes)
            $productDrafts = Cache::remember('product_drafts', 6000, function () {
                return ProductDraft::with(['category', 'creator', 'updater'])
                    ->latest()
                    ->paginate(10);
            });

            return response()->json([
                'success' => true,
                'data' => $productDrafts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    private function updateProductDraftCache()
    {
        // Fetch all product drafts again and update cache
        $productDrafts = ProductDraft::with(['category', 'creator', 'updater'])->latest()->get();
        
        // Store the updated product drafts in cache
        Cache::put('product_drafts', $productDrafts, now()->addMinutes(100));
    }

    /**
     * Store a newly created product draft.
     */
    public function store(StoreProductDraftRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            // Validate and generate combination if molecule IDs are provided
            if (isset($validatedData['molecule_ids'])) {
                if (!$this->validateMoleculeIds($validatedData['molecule_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive molecules provided'
                    ], 400);
                }

                $combination = $this->generateCombinationString($validatedData['molecule_ids']);
                
                if ($combination === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate molecule combination'
                    ], 400);
                }

                $validatedData['combination'] = $combination;
            }

            $validatedData['created_by'] = Auth::id();

            $productDraft = ProductDraft::create($validatedData);

            // Attach molecules if provided
            if (isset($validatedData['molecule_ids'])) {
                $productDraft->molecules()->sync($validatedData['molecule_ids']);
            }

            DB::commit();

            // **Update the cache after storing**
            $this->updateProductDraftCache();

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


    // Similar modifications for update method
    public function update(UpdateProductDraftRequest $request, ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            // Validate and generate combination if molecule IDs are provided
            if (isset($validatedData['molecule_ids'])) {
                if (!$this->validateMoleculeIds($validatedData['molecule_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive molecules provided'
                    ], 400);
                }

                $combination = $this->generateCombinationString($validatedData['molecule_ids']);
                
                if ($combination === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate molecule combination'
                    ], 400);
                }

                $validatedData['combination'] = $combination;
                $productDraft->molecules()->sync($validatedData['molecule_ids']);
            }

            $validatedData['updated_by'] = Auth::id();

            if ($productDraft->publish_status !== 'draft') {
                $validatedData['publish_status'] = 'unpublished';
            }
            
            $productDraft->update($validatedData);

            DB::commit();

            // **Update the cache after updating**
            $this->updateProductDraftCache();

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
     * Display the specified product draft.
     */
    public function show($id): JsonResponse
    {
        try {
            // Retrieve all product drafts from cache
            $productDrafts = Cache::get('product_drafts');

            if ($productDrafts) {
                // Search for the product by ID within the cached collection
                $productDraft = collect($productDrafts)->firstWhere('id', $id);

                if ($productDraft) {
                    return response()->json([
                        'success' => true,
                        'data' => $productDraft
                    ]);
                }
            }

            // If not found in cache, fetch from database
            $productDraft = ProductDraft::with(['category', 'creator', 'updater', 'publisher'])->find($id);

            if ($productDraft) {
                return response()->json([
                    'success' => true,
                    'data' => $productDraft
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  Soft delete the specified product draft.
     */
    public function destroy(ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            $productDraft->deleted_by = Auth::id();
            $productDraft->save();
            $productDraft->delete();

            DB::commit();

            // **Remove deleted product from cache**
            $this->updateProductDraftCache();

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
            if ($productDraft->publish_status != 'published') {
                if ($productDraft->code === null) {
                    $productDraft->code = $this->generateUniqueProductCode();
                }

                $productDraft->publish_status = 'published';
                $productDraft->published_by = Auth::id();
                $productDraft->published_at = now();
                $productDraft->save();

                PublishProduct::dispatch($productDraft);

                DB::commit();

                // **Update cache after publishing**
                $this->updateProductDraftCache();

                return response()->json([
                    'success' => true,
                    'message' => 'Product draft published successfully',
                    'data' => $productDraft
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is already published.',
                    'data' => $productDraft
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue product draft for publishing',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Generate a unique product code.
     */
    private function generateUniqueProductCode(): string
    {
        // Fetch the latest published product code
        $latestProduct = ProductDraft::whereNotNull('code')
            ->orderByDesc('code')
            ->first();

        if ($latestProduct && is_numeric($latestProduct->code)) {
            // Increment the latest code by 1
            $newCode = str_pad($latestProduct->code + 1, 6, '0', STR_PAD_LEFT);
        } else {
            // If no existing product, start from "000001"
            $newCode = '000001';
        }

        return $newCode;
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

    // Additional methods to add to the existing ProductDraftController

    /**
     * Get active product drafts.
     */
    public function getActive(): JsonResponse
    {
        $activeProductDrafts = ProductDraft::where('is_active', true)
            ->with(['category', 'creator'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $activeProductDrafts
        ]);
    }

    /**
     * Get product drafts by publish status.
     */
    public function getByStatus(string $status): JsonResponse
    {
        $validStatuses = ['draft', 'published', 'unpublished'];
        
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status'
            ], 400);
        }

        $productDrafts = ProductDraft::where('publish_status', $status)
            ->with(['category', 'creator'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $productDrafts
        ]);
    }

    public function forceDelete(ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            $productDraft->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product draft permanently deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to force delete product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
