<?php

namespace App\Repositories\Interfaces;

interface MoleculeRepositoryInterface
{
    public function getAll();
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function restore($id);
    public function forceDelete($id);
    public function getActive();
    public function getWithProducts();
}