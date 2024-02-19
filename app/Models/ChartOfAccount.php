<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'short_name',
        'type',
        'parent_id',
    ];

    // Define relationship with parent account
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    // Define relationship with child accounts
    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the tree-like structure of accounts with their respective balances.
     *
     * @return array
     */
    public static function treeWithBalance()
    {
        // Get all accounts with their balances
        $accounts = ChartOfAccount::with('children')->get();

        // Organize accounts into a tree structure
        $tree = [];
        foreach ($accounts as $account) {
            // Skip if the account has a parent
            if ($account->parent_id !== null) {
                continue;
            }

            // Build the tree recursively
            $tree[] = self::buildTree($account);
        }

        return $tree;
    }

    /**
     * Build a tree-like structure recursively.
     *
     * @param  ChartOfAccount  $account
     * @return array
     */
    protected static function buildTree($account)
    {
        $balance = $account->current_balance;

        // Calculate balance including children recursively
        foreach ($account->children as $child) {
            $balance += self::buildTree($child)['balance'];
        }

        return [
            'id' => $account->id,
            'name' => $account->name,
            'balance' => $balance,
            'children' => $account->children->map(function ($child) {
                return self::buildTree($child);
            }),
        ];
    }


}
