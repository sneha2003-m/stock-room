<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::withCount('items')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:categories,name']);
        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category->id)],
        ]);
        $category->update($data);
        return $category->loadCount('items');
    }

    public function destroy(Category $category)
    {
        if ($category->items()->exists()) {
            return response()->json(['message' => 'Categories with items cannot be deleted.'], 422);
        }

        $category->delete();
        return response()->noContent();
    }
}