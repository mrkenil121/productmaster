<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products_draft', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable(); // Will be filled when published
            $table->string('name');
            $table->string('manufacturer');
            $table->decimal('mrp', 10, 2);
            $table->decimal('sales_price', 10, 2);
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->enum('publish_status', ['draft', 'published', 'unpublished'])->default('draft');
            $table->string('combination')->nullable;

            $table->boolean('is_banned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_discontinued')->default(false);
            $table->boolean('is_assured')->default(false);
            $table->boolean('is_refrigerated')->default(false);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            
            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('published_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_draft');
    }
};
