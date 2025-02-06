<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->code) {
                DB::transaction(function () use ($model) {
                    $latestId = DB::table('products')->max('id') ?? 0;
                    $model->code = str_pad($latestId + 1, 6, '0', STR_PAD_LEFT);
                });
            }
        });
    }

    protected $fillable = [
        'code',
        'name',
        'manufacturer',
        'mrp',
        'sales_price',
        'combination',
        'is_banned',
        'is_active',
        'is_discontinued',
        'is_assured',
        'is_refrigerated',
        'created_by',
        'updated_by',
        'deleted_by',
        'published_by'
    ];

    protected $dates = ['deleted_at', 'published_at'];

    protected $casts = [
        'mrp' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'is_banned' => 'boolean',
        'is_active' => 'boolean',
        'is_discontinued' => 'boolean',
        'is_assured' => 'boolean',
        'is_refrigerated' => 'boolean',
        'deleted_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function searchableAs()
    {
        return 'published_product_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'manufacturer' => $this->manufacturer,
            'combination_string' => $this->combination,
            'mrp' => $this->mrp,
            'sales_price' => $this->sales_price,
            'is_active' => $this->is_active,
            'is_discontinued' => $this->is_discontinued,
            'is_assured' => $this->is_assured,
            'is_refrigerated' => $this->is_refrigerated,
        ];
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
