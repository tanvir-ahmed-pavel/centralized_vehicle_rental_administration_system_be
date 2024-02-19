<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all accounts with their balances
        $accounts = ChartOfAccount::with(['children', 'transactions'])->get();


        // Organize accounts into a tree structure
        $tree = [];
        $rootTree = [];
        foreach ($accounts as $account) {
            // Skip if the account has a parent
            if ($account->parent_id !== null) {
                continue;
            }

            // Build the tree recursively
            $returnedTree = self::buildTree($account);
            $tree[] = $returnedTree;

            if (is_null($returnedTree['parent_id'])){
                $rootTree[$returnedTree['name']] = $returnedTree['balance'];
            }
        }

        return response()->json([
            'message' => 'Accounts records retrieved successfully',
            'data' => [
                "tree" => $tree,
                "root_tree" => $rootTree,
                ],
        ], 200);
    }

    protected static function buildTree($account)
    {
        $balance = 0;
        if(
            $account->type === "Liability"
            || $account->type === "Equity"
            || $account->type === "Income"
        )
        {
            $balance = round($account->transactions->sum('credit') - $account->transactions->sum('debit'),2);
        } else{
            $balance = round($account->transactions->sum('debit') - $account->transactions->sum('credit'),2);

        }


        // Calculate balance including children recursively
        foreach ($account->children as $child) {
            $balance += self::buildTree($child)['balance'];
        }

        return [
            'id' => $account->id,
            'name' => $account->name,
            'parent_name' => $account->type,
            'parent_id' => $account->parent_id,
            'balance' => $balance,
            'children' => $account->children->map(function ($child) {
                return self::buildTree($child);
            }),
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount)
    {
        //
    }
}
