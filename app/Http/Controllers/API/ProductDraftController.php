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

            // Validate and generate combination if molecule IDs are provided
            if (isset($validatedData['molecule_ids'])) {
                // Validate molecule IDs
                if (!$this->validateMoleculeIds($validatedData['molecule_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive molecules provided'
                    ], 400);
                }

                // Generate combination string
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
                // Validate molecule IDs
                if (!$this->validateMoleculeIds($validatedData['molecule_ids'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or inactive molecules provided'
                    ], 400);
                }

                // Generate combination string
                $combination = $this->generateCombinationString($validatedData['molecule_ids']);
                
                if ($combination === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate molecule combination'
                    ], 400);
                }

                $validatedData['combination'] = $combination;

                // Sync molecules
                $productDraft->molecules()->sync($validatedData['molecule_ids']);
            }

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

    /**
     * Permanently delete a product draft.
     */
    public function forceDelete(ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if the user has permission to force delete
            // You might want to add more sophisticated authorization logic
            if (!Auth::user()->hasPermissionTo('force_delete_product_drafts')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to force delete product drafts'
                ], 403);
            }

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

    /**
     * Unpublish a published product draft.
     */
    public function unpublish(ProductDraft $productDraft): JsonResponse
    {
        try {
            DB::beginTransaction();

            if ($productDraft->publish_status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only published drafts can be unpublished'
                ], 400);
            }

            $productDraft->publish_status = 'unpublished';
            $productDraft->published_by = null;
            $productDraft->published_at = null;
            $productDraft->code = null; // Remove the generated code
            $productDraft->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product draft unpublished successfully',
                'data' => $productDraft
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish product draft',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk publish product drafts.
     */
    public function bulkPublish(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'draft_ids' => 'required|array',
            'draft_ids.*' => 'exists:products_draft,id'
        ]);

        $successIds = [];
        $failedIds = [];

        DB::beginTransaction();
        try {
            foreach ($validatedData['draft_ids'] as $draftId) {
                $productDraft = ProductDraft::findOrFail($draftId);
                
                // Generate unique code
                $productDraft->code = $this->generateUniqueProductCode();
                $productDraft->publish_status = 'published';
                $productDraft->published_by = Auth::id();
                $productDraft->published_at = now();
                $productDraft->save();

                $successIds[] = $draftId;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk publish completed',
                'data' => [
                    'success_ids' => $successIds,
                    'failed_ids' => $failedIds
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk publish failed',
                'error' => $e->getMessage(),
                'data' => [
                    'success_ids' => $successIds,
                    'failed_ids' => $failedIds
                ]
            ], 500);
        }
    }

    /**
     * Bulk delete product drafts.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'draft_ids' => 'required|array',
            'draft_ids.*' => 'exists:products_draft,id'
        ]);

        $successIds = [];
        $failedIds = [];

        DB::beginTransaction();
        try {
            foreach ($validatedData['draft_ids'] as $draftId) {
                $productDraft = ProductDraft::findOrFail($draftId);
                $productDraft->deleted_by = Auth::id();
                $productDraft->save();
                $productDraft->delete();

                $successIds[] = $draftId;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk delete completed',
                'data' => [
                    'success_ids' => $successIds,
                    'failed_ids' => $failedIds
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk delete failed',
                'error' => $e->getMessage(),
                'data' => [
                    'success_ids' => $successIds,
                    'failed_ids' => $failedIds
                ]
            ], 500);
        }
    }

    /**
     * Bulk restore soft-deleted product drafts.
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'draft_ids' => 'required|array',
            'draft_ids.*' => 'exists:products_draft,id'
        ]);

        $successIds = [];
        $failedIds = [];

        DB::beginTransaction();
        try {
            foreach ($validatedData['draft_ids'] as $draftId) {
                $productDraft = ProductDraft::withTrashed()->findOrFail($draftId);
                $productDraft->deleted_by = null;
                $productDraft->restore();

                $successIds[] = $draftId;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk restore completed',
                'data' => [
                    'success_ids' => $successIds,
                    'failed_ids' => $failedIds
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk restore failed',
                'error' => $e->getMessage(),
                'data' => [
                    'success_ids' => $successIds,
                    'failed_ids' => $failedIds
                ]
            ], 500);
        }
    }
}
