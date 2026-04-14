<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('id')->get();

        return view('crm.categories.index', compact([
            'categories'
        ]));
    }

    public function getSubcategories($categoryId)
    {
        $subcategories = Subcategory::where('category_id', $categoryId)->get();
        return response()->json($subcategories);
    }


    public function create()
    {
        return view('crm.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        Category::create($request->all());

        return redirect()->back()->with('status', 'Категория добавлена!');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);

        return view('crm.categories.edit', compact([
            'category'
        ]));
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::findOrFail($id);

        $category->update($request->all());

        return redirect()->back()->with('status', 'Категория обновлена!');
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();

        return redirect()->route('categories.index')->with('status', 'Категория удалена!');
    }
}
