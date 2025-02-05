<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class UserController extends Controller
{
      /**
     * Get all users with pagination and optional filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $query = User::with(['creator', 'updater'])
                ->orderBy('created_at', 'desc');

            // Apply status filter if provided
            if ($request->has('is_active')) {
                $query = $request->is_active === true 
                    ? $query->active()
                    : $query->inactive();
            }

            $users = $query->paginate($request->query('per_page', 10));

            // Remove sensitive information from relationships
            $users->getCollection()->transform(function ($user) {
                if ($user->creator) {
                    $user->creator->makeHidden(['email', 'mobile_no', 'password', 'remember_token']);
                }
                if ($user->updater) {
                    $user->updater->makeHidden(['email', 'mobile_no', 'password', 'remember_token']);
                }
                return $user;
            });

            return response()->json([
                'status' => 'success',
                'data' => $users,
                'message' => 'Users retrieved successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getById($id): JsonResponse
    {
        try {
            // Prevent users from accessing other user details unless they're admin
            if (Auth::id() != $id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $user = User::with(['creator', 'updater'])
                ->findOrFail($id);

            // Remove sensitive information from relationships
            if ($user->creator) {
                $user->creator->makeHidden(['email', 'mobile_no', 'password', 'remember_token']);
            }
            if ($user->updater) {
                $user->updater->makeHidden(['email', 'mobile_no', 'password', 'remember_token']);
            }

            return response()->json([
                'status' => 'success',
                'data' => $user,
                'message' => 'User retrieved successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException 
                    ? 'User not found' 
                    : 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Delete user by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            // Prevent users from deleting other users unless they're admin
            if (Auth::id() != $id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $user = User::findOrFail($id);
            
            // Set deleted_by
            $user->deleted_by = Auth::id();
            $user->save();
            
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException 
                    ? 'User not found' 
                    : 'Failed to delete user',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}
