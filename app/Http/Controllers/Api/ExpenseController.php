<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    //     $this->middleware('company.access')->except(['index', 'store']);
    // }

    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        
        $query = Expense::where('company_id', $companyId);
        
        // Apply search filters if provided
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Cache key based on query parameters
        $cacheKey = "expenses:{$companyId}:" . md5(json_encode($request->all()));
        
        // Get expenses with eager loading
        $expenses = Cache::remember($cacheKey, 600, function () use ($query) {
            return $query->with('user')->paginate(15);
        });
        
        return response()->json($expenses);
    }
    public function store(Request $request)
    {
        $user = $request->user();
        Log::info('Authenticated user trying to create a new user:', ['user_id' => $user->id, 'role' => $user->role]);
    
        if ($user->role !== 'Admin') {
            Log::warning('Unauthorized attempt to create user by:', ['user_id' => $user->id]);
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['Admin', 'Manager', 'Employee'])],
        ]);
    
        Log::info('Validated input:', $validated);
    
        try {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'company_id' => $user->company_id,
                'role' => $validated['role'],
            ]);
    
            Log::info('User created successfully:', ['new_user_id' => $newUser->id]);
            return response()->json($newUser->only(['id', 'name', 'email', 'role', 'company_id']), 201);
        } catch (\Exception $e) {
            Log::error('Failed to create user:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }
    

    public function show(Expense $expense)
    {
        $expense->load('user');
        return response()->json($expense);
    }

    public function update(Request $request, Expense $expense)
    {
        // $this->middleware('role:Admin,Manager');
        
        $user = $request->user();
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|string|max:255',
        ]);
        
        // Save original state for audit log
        $originalExpense = $expense->toArray();
        
        $expense->update($validated);
        
        // Create audit log
        AuditLog::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => 'expense.update',
            'changes' => [
                'old' => $originalExpense,
                'new' => $expense->toArray(),
            ],
        ]);
        
        // Clear cache for this company's expenses
        $this->clearExpenseCache($user->company_id);
        
        return response()->json($expense);
    }

    public function destroy(Request $request, Expense $expense)
    {
        // $this->middleware('role:Admin');
        
        $user = $request->user();
        
        // Save original state for audit log
        $originalExpense = $expense->toArray();
        
        $expense->delete();
        
        // Create audit log
        AuditLog::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'action' => 'expense.delete',
            'changes' => [
                'old' => $originalExpense,
                'new' => null,
            ],
        ]);
        
        // Clear cache for this company's expenses
        $this->clearExpenseCache($user->company_id);
        
        return response()->json(['message' => 'Expense deleted successfully']);
    }
    
    private function clearExpenseCache($companyId)
    {
        // Get all cache keys for this company's expenses
        $keys = Cache::get("expense_cache_keys:{$companyId}", []);
        
        // Delete each key
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear the list of keys
        Cache::forget("expense_cache_keys:{$companyId}");
    }
}