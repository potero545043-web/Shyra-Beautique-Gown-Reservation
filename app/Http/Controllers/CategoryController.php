<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display all categories.
     */
    public function index(Request $request)
    {
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $categories = Category::withCount('gowns')->latest()->paginate($perPage)->withQueryString();

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', 'Category added successfully.');
    }

    /**
     * Display a specific category.
     */
    public function show(Request $request, Category $category)
    {
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $gowns = $category->gowns()->latest()->paginate($perPage)->withQueryString();

        return view('categories.show', compact('category', 'gowns'));
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ];

        $category->update($data);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Deactivate a category.
     */
    public function destroy(Category $category)
    {
        $category->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', 'Category deactivated successfully.');
    }

    public function toggleStatus(Category $category)
    {
        $category->update([
            'is_active' => !$category->is_active,
        ]);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', $category->is_active
                ? 'Category activated successfully.'
                : 'Category deactivated successfully.');
    }
}
