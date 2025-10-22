<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\AttributeValue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // All products with filters
    public function index(Request $request)
    {
        $products = Product::with(['category','images','variants','attributeValues.attribute'])
            ->when($request->category, fn($q) => $q->where('category_id', $request->category))
            ->when($request->price_min, fn($q) => $q->where('price','>=',$request->price_min))
            ->when($request->price_max, fn($q) => $q->where('price','<=',$request->price_max))
            ->paginate(20);

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    // Create Product
    public function store_OLD(Request $request)
    {
        $request->validate([
            'name'       => 'required',
            'price'      => 'required|numeric',
            'category_id'=> 'required|exists:categories,id',
            'images.*'   => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Product object
        $product = new Product();
        $product->name        = $request->name;
        // $product->slug        = Str::slug($request->name);
        $slug = Str::slug($request->name);
        $count = Product::where('slug', 'like', "$slug%")->count();
        $product->slug = $count ? "{$slug}-{$count}" : $slug;

        $product->description = $request->description;
        $product->price       = $request->price;
        $product->stock       = $request->stock ?? 0;
        $product->category_id = $request->category_id;
        $product->status      = $request->status ?? 1;

        // ডাটাবেজে save করো
        $product->save();

        // Save images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create(['image_path' => $path]);
            }
        }
        // Save attribute values
        if ($request->has('attributes')) {
            foreach ($request->attributes as $attr) {
                AttributeValue::create([
                    'product_id'   => $product->id,
                    'attribute_id' => $attr['attribute_id'],
                    'value'        => $attr['value'],
                ]);
            }
        }
        // return response()->json($product, 201);
        return response()->json($product->load('attributeValues.attribute'), 201);

    }
    //FOR DB- TRANSACTION
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required',
            'price'       => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'images.*'    => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $product = DB::transaction(function() use ($request) {
            // Slug uniqueness
            $slug = Str::slug($request->name);
            $count = Product::where('slug', 'like', "$slug%")->count();
            $finalSlug = $count ? "{$slug}-{$count}" : $slug;

            // Product save
            $product = Product::create([
                'name'        => $request->name,
                'slug'        => $finalSlug,
                'description' => $request->description,
                'price'       => $request->price,
                'stock'       => $request->stock ?? 0,
                'category_id' => $request->category_id,
                'status'      => $request->status ?? 1,
            ]);

            // Save images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create(['image_path' => $path]);
                }
            }

            // Save attributes
            if ($request->has('attributes')) {
                foreach ($request->attributes as $attr) {
                    AttributeValue::create([
                        'product_id'   => $product->id,
                        'attribute_id' => $attr['attribute_id'],
                        'value'        => $attr['value'],
                    ]);
                }
            }

            return $product;
        });

        return response()->json($product->load('attributeValues.attribute'), 201);
    }

    /**
     * Display the specified resource.
     */
     // Show single product
    public function show($id)
    {
        // return Product::with(['category','images','variants'])->findOrFail($id);
        return Product::with(['category','images','variants','attributeValues.attribute'])->findOrFail($id);

    }

    /**
     * Update the specified resource in storage.
     */
    // Update
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // $product->update($request->all());
        $product->update($request->only([
            'name', 'description', 'price', 'stock', 'category_id', 'status'
        ]));

        // Update attributes
        if ($request->has('attributes')) {
            // আগের attribute values মুছে দাও
            $product->attributeValues()->delete();

            foreach ($request->attributes as $attr) {
                AttributeValue::create([
                    'product_id'   => $product->id,
                    'attribute_id' => $attr['attribute_id'],
                    'value'        => $attr['value'],
                ]);
            }
        }

        // return response()->json($product);
        return response()->json($product->load('attributeValues.attribute'));

    }

    /**
     * Remove the specified resource from storage.
     */
    // Delete
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete related attribute values
        $product->attributeValues()->delete();

        // Delete product images (optional)
        // $product->images()->delete();
        foreach($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }


        // Delete product
        $product->delete();

        return response()->json(['message'=>'Product deleted successfully']);
    }

}
