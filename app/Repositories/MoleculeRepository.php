<?php

namespace App\Repositories;

use App\Models\Molecule;
use App\Repositories\Interfaces\MoleculeRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class MoleculeRepository implements MoleculeRepositoryInterface
{
    protected $model;

    public function __construct(Molecule $molecule)
    {
        $this->model = $molecule;
    }

    /**
     * Get all molecules
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->model->with(['creator', 'updater'])->get();
    }

    /**
     * Get molecule by ID
     * 
     * @param int $id
     * @return Molecule|null
     */
    public function getById($id)
    {
        return $this->model->with(['creator', 'updater', 'products'])->find($id);
    }

    /**
     * Create new molecule
     * 
     * @param array $data
     * @return Molecule
     */
    public function create(array $data)
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        
        return $this->model->create($data);
    }

    /**
     * Update molecule
     * 
     * @param int $id
     * @param array $data
     * @return Molecule|false
     */
    public function update($id, array $data)
    {
        $molecule = $this->model->find($id);
        
        if (!$molecule) {
            return false;
        }

        $data['updated_by'] = Auth::id();
        
        $molecule->update($data);

        return $molecule;
    }
    
    /**
     * Soft delete molecule
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $molecule = $this->model->find($id);
        
        if (!$molecule) {
            return false;
        }

        // Set deleted_by before soft deleting
        $molecule->deleted_by = Auth::id();
        $molecule->save();
        
        return $molecule->delete();
    }

    /**
     * Restore soft deleted molecule
     * 
     * @param int $id
     * @return bool
     */
    public function restore($id)
    {
        $molecule = $this->modelwithTrashed()->find($id);
        
        if (!$molecule) {
            return false;
        }

        // Clear deleted_by when restoring
        $molecule->deleted_by = null;
        $molecule->save();
        
        return $molecule->restore();
    }

    /**
     * Force delete molecule
     * 
     * @param int $id
     * @return bool
     */
    public function forceDelete($id)
    {
        $molecule = $this->model->withTrashed()->find($id);
        
        if (!$molecule) {
            return false;
        }
        
        return $molecule->forceDelete();
    }

    /**
     * Get active molecules
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get molecules with their products
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWithProducts()
    {
        return $this->model->with('products')->get();
    }
}