<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\MoleculeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\StoreMoleculeRequest;
use App\Http\Requests\UpdateMoleculeRequest;

class MoleculeController extends Controller
{
    protected $moleculeRepository;

    public function __construct(MoleculeRepositoryInterface $moleculeRepository)
    {
        $this->moleculeRepository = $moleculeRepository;
    }

    public function index()
    {
        $molecules = $this->moleculeRepository->getAll();
        return response()->json($molecules);
    }

    public function show($id)
    {
        $molecule = $this->moleculeRepository->getById($id);
        
        if (!$molecule) {
            return response()->json(['message' => 'Molecule not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($molecule);
    }

    public function store(StoreMoleculeRequest $request)
    {
        $molecule = $this->moleculeRepository->create($request->validated());
        return response()->json($molecule, Response::HTTP_CREATED);
    }

    public function update(UpdateMoleculeRequest $request, $id)
    {
        $molecule = $this->moleculeRepository->update($id, $request->validated());
        
        if (!$molecule) {
            return response()->json(['message' => 'Molecule not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($molecule);
    }

    public function destroy($id)
    {
        $result = $this->moleculeRepository->delete($id);
        
        if (!$result) {
            return response()->json(['message' => 'Molecule not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function restore($id)
    {
        $result = $this->moleculeRepository->restore($id);
        
        if (!$result) {
            return response()->json(['message' => 'Molecule not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['message' => 'Molecule restored successfully']);
    }

    public function forceDelete($id)
    {
        $result = $this->moleculeRepository->forceDelete($id);
        
        if (!$result) {
            return response()->json(['message' => 'Molecule not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getActive()
    {
        $molecules = $this->moleculeRepository->getActive();
        return response()->json($molecules);
    }
}
