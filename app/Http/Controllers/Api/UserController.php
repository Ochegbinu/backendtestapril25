<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
 

    public function index(Request $request)
    {
        if ($request->user()->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $user = $request->user();
        $users = User::where('company_id', $user->company_id)->get();
        
        return response()->json($users);
    }


    public function store(Request $request)
    {
        $authUser = $request->user();

        // Only Admins can create users
        if ($authUser->role !== 'Admin') {
            return response()->json(['error' => 'Only Admins can create users.'], 403);
        }
    
        // Make sure the admin has a company_id
        if (!$authUser->company_id) {
            return response()->json(['error' => 'Admin is not associated with a company.'], 400);
        }
    
        // Validate input (only Manager or Employee can be created)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string',
            'role' => ['required', Rule::in(['Manager', 'Employee'])], 
        ]);
    
        try {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'company_id' => $authUser->company_id,
                'role' => $validated['role'],
            ]);
    
            return response()->json($newUser, 201);
    
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }
    

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($user->id)
            ],
            'role' => ['sometimes', Rule::in(['Admin', 'Manager', 'Employee'])],
        ]);
        
        if ($request->has('password')) {
            $validated['password'] = Hash::make($request->password);
        }
        
        $user->update($validated);
        
        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        // Admin cannot delete themselves
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }
}