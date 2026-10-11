<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with('category')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:9999999999.99',
            'quantity' => 'required|integer|min:0|max:4294967295',
        ]);
        return response()->json(Item::create($data)->load('category'), 201);
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:9999999999.99',
            'quantity' => 'required|integer|min:0|max:4294967295',
        ]);
        $item->update($data);
        return $item->load('category');
    }

    public function destroy(Item $item)
    {
        if ($item->purchaseItems()->exists() || $item->saleItems()->exists()) {
            return response()->json(['message' => 'Items in transaction history cannot be deleted.'], 422);
        }

        $item->delete();
        return response()->noContent();
    }
}