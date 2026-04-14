<?php

namespace App\Http\Controllers;


use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::select([
            'id as productId',
            'name as productName',
            'description as productDescription',
            'price as productPrice',
            'image as productImage',
            'created_at as productCreated_at',
        ])->orderBy('created_at')->get();

        return view('crm.products.index', compact([
            'products'
        ]));
    }

    public function create()
    {
        $categories = Category::with('subcategories')->get();

        return view('crm.products.create', compact([
            'categories'
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'price' => 'required|numeric|between:0,99999999.99',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
        ]);

        $data = $request->only(['name', 'description', 'price', 'category_id', 'subcategory_id']);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('images', $filename, 'public');
            $data['image'] = $filePath;
        }

        Product::create($data);

        return redirect()->back()->with('status', 'Карточка добавлена!');
    }


    public function edit($id)
    {
        $product = Product::findOrFail($id);

        return view('crm.products.edit', compact([
            'product'
        ]));
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|text|max:255',
            'price' => 'required|decimal:2,2|max:10',
            'image' => 'dimensions:min_width=100,min_height=200'
        ]);

        $product = Product::findOrFail($id);

        $product->update($request->all());

        return redirect()->back()->with('status', 'Карточка обновлена!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', 'Карточка и изображение удалены!');
    }
}
