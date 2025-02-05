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
        Schema::create('product_molecules', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('molecule_id');
            
            $table->primary(['product_id', 'molecule_id']);
            
            // Change this line to reference products_draft instead of products
            $table->foreign('product_id')->references('id')->on('products_draft')->onDelete('cascade');
            $table->foreign('molecule_id')->references('id')->on('molecules')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_molecules');
    }
};
