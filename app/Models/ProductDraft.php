<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDraft extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products_draft';

    protected $fillable = [
        'code',
        'name',
        'manufacturer',
        'mrp',
        'sales_price',
        'category_id',
        'publish_status',
        'is_banned',
        'is_active',
        'is_discontinued',
        'is_assured',
        'is_refrigerated',
        'combination',
        'created_by',
        'updated_by'
    ];

    protected $dates = [
        'deleted_at',
        'published_at'
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'is_banned' => 'boolean',
        'is_active' => 'boolean',
        'is_discontinued' => 'boolean',
        'is_assured' => 'boolean',
        'is_refrigerated' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function molecules()
    {
        return $this->belongsToMany(Molecule::class, 'product_molecules', 'product_id', 'molecule_id');
    }

    // User relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
