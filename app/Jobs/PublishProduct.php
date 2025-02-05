<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class PublishProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productDraft;
    
    public function __construct(ProductDraft $productDraft)
    {
        $this->productDraft = $productDraft;
    }

    public function handle()
    {
        DB::beginTransaction();
        try {
            $product = Product::where('code', $this->productDraft->code)->first();

            if ($product) {
                try {
                    // First attempt: Try to update
                    $updated = $product->update([
                        'name' => $this->productDraft->name,
                        'manufacturer' => $this->productDraft->manufacturer,
                        'mrp' => $this->productDraft->mrp,
                        'sales_price' => $this->productDraft->sales_price,
                        'combination' => $this->productDraft->combination,
                        'is_banned' => false,
                        'is_active' => true,
                        'is_discontinued' => false,
                        'is_assured' => false,
                        'is_refrigerated' => false,
                        'created_by' => $this->productDraft->created_by,
                        'published_by' => $this->productDraft->published_by,
                        'published_at' => $this->productDraft->published_at,
                    ]);

                    if (!$updated) {
                        throw new Exception('Failed to update product');
                    }

                } catch (Exception $e) {
                    Log::warning("Update failed for product {$product->code}. Attempting delete and create.", [
                        'error' => $e->getMessage()
                    ]);

                    // Force delete the existing product
                    $product->forceDelete();

                    // Verify the product is actually deleted
                    if (Product::where('code', $this->productDraft->code)->exists()) {
                        throw new Exception('Failed to delete existing product');
                    }

                    // Create new product
                    $this->createNewProduct();
                }
            } else {
                // Double check no product exists before creating
                if (!Product::where('code', $this->productDraft->code)->exists()) {
                    $this->createNewProduct();
                } else {
                    // If product appeared between our first check and now, start over
                    throw new Exception('Product appeared during processing, retrying...');
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to publish product", [
                'code' => $this->productDraft->code,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    protected function createNewProduct()
    {
        return Product::create([
            'code' => $this->productDraft->code,
            'name' => $this->productDraft->name,
            'manufacturer' => $this->productDraft->manufacturer,
            'mrp' => $this->productDraft->mrp,
            'sales_price' => $this->productDraft->sales_price,
            'combination' => $this->productDraft->combination,
            'is_banned' => false,
            'is_active' => true,
            'is_discontinued' => false,
            'is_assured' => false,
            'is_refrigerated' => false,
            'created_by' => $this->productDraft->created_by,
            'published_by' => $this->productDraft->published_by,
            'published_at' => $this->productDraft->published_at,
        ]);
    }
}