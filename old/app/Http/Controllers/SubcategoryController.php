<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubcategoryController extends Controller
{
    public function index()
    {
        $subcategories = DB::table('categories')
            ->join('subcategories', 'category_id', '=', 'categories.id')
            ->select(
                'categories.name as categoryName',
                'subcategories.name as subcategoryName',
                'subcategories.id as subcategoryId')
            ->orderBy('subcategories.id')
            ->get();

        return view('crm.subcategories.index', compact([
            'subcategories'
        ]));
    }

    public function create()
    {
        $categories = Category::orderBy('id')->get();

        return view('crm.subcategories.create', compact('categories'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        Subcategory::create($request->all());

        return redirect()->back()->with('status', 'Доп. категория добавлена!');
    }

    public function edit($id)
    {
        $subcategory = Subcategory::findOrFail($id);

        $categories = Category::orderBy('name')->get();

        return view('crm.subcategories.edit', compact([
            'subcategory',
            'categories'
        ]));
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer',
        ]);

        $subcategory = Subcategory::findOrFail($id);

        $subcategory->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
        ]);

        return redirect()->back()->with('status', 'Доп. категория обновлена!');
    }

    public function destroy($id)
    {
        Subcategory::findOrFail($id)->delete();

        return redirect()->route('subcategories.index')->with('status', 'Доп. категория удалена!');
    }
}
