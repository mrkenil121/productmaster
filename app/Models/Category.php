<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'created_by', 'updated_by'];

    protected $dates = ['deleted_at'];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productsDraft()
    {
        return $this->hasMany(ProductDraft::class);
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
}
