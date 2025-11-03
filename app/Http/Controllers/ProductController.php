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
        // 1. Validation
        $request->validate([
            'name'          => 'required',
            'price'         => 'required|numeric',
            'category_id'   => 'required|exists:categories,id',
            'images.*'      => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 2. Database Transaction
        $product = DB::transaction(function() use ($request, $id) {

            $product = Product::findOrFail($id);

            // A. Slug Uniqueness & Generation (Only update if the name has changed)
            $finalSlug = $product->slug; // Start with the existing slug
            if ($request->name !== $product->name) {
                $slug = Str::slug($request->name);
                // Count products with similar slugs, excluding the current product ID
                $count = Product::where('slug', 'like', "$slug%")
                                ->where('id', '!=', $product->id)
                                ->count();
                $finalSlug = $count > 0 ? "{$slug}-{$count}" : $slug;
            }

            // B. Product Update
            $product->update([
                'name'          => $request->name,
                'slug'          => $finalSlug, // Use the potentially new slug
                'description'   => $request->description,
                'price'         => $request->price,
                'stock'         => $request->stock ?? 0,
                'category_id'   => $request->category_id,
                'status'        => $request->status ?? 1,
            ]);

            // C. Handle Images (New uploads)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    // Store new image
                    $path = $image->store('products', 'public');
                    // Associate with the product
                    $product->images()->create(['image_path' => $path]);
                }
            }
            
            // D. Handle Deleted Images (You might need this logic)
            // This is a common requirement for updates, assuming the request sends
            // an array of IDs for images to be deleted.
            /*
            if ($request->has('deleted_image_ids')) {
                $product->images()
                        ->whereIn('id', $request->deleted_image_ids)
                        ->get() // Get the models to delete files
                        ->each(function($image) {
                            // Delete file from storage
                            Storage::disk('public')->delete($image->image_path); 
                            // Delete record from database
                            $image->delete();
                        });
            }
            */

            // E. Handle Attributes (Update/Create)
            // For an update function, it's often easiest to delete existing
            // attributes and re-create them, or specifically handle updates
            // and deletions. Here we'll take the delete-and-recreate approach for simplicity.

            // 1. Delete all existing attribute values for this product
            $product->attributeValues()->delete(); 

            // 2. Create the new/updated attribute values
            // if ($request->has('attributes')) {
            //     $attributeValuesToInsert = [];
            //     foreach ($request->attributes as $attr) {
            //         $attributeValuesToInsert[] = [
            //             'product_id'   => $product->id,
            //             'attribute_id' => $attr['attribute_id'],
            //             'value'        => $attr['value'],
            //             'created_at'   => now(), // Important for mass insertion
            //             'updated_at'   => now(),
            //         ];
            //     }
            //     // Use insert for efficiency if many attributes
            //     if (!empty($attributeValuesToInsert)) {
            //         AttributeValue::insert($attributeValuesToInsert);
            //     }
            // }
            if ($request->has('attributes')) {
    
                $attributeValueIds = [];
                foreach ($request->attributes as $attr) {
                    // রিকোয়েস্ট থেকে শুধুমাত্র attribute_value_id সংগ্রহ করুন।
                    // ধরে নিচ্ছি $attr['attribute_value_id'] হলো attribute_values টেবিলের ID.
                    // **নোট:** আপনার JSON বডিতে 'attribute_id' এর পরিবর্তে 'attribute_value_id' থাকা উচিত।
                    $attributeValueIds[] = $attr['attribute_value_id']; 
                }

                // sync() ফাংশনটি: 
                // 1. পিভট টেবিল attribute_product-এর সব পুরানো এন্ট্রি ডিলিট করে দেবে (যেগুলো $attributeValueIds এ নেই)।
                // 2. নতুন ID গুলিকে ইনসার্ট করবে।
                $product->attributeValues()->sync($attributeValueIds);

            } else {
                // যদি রিকোয়েস্টে কোনো অ্যাট্রিবিউট না থাকে, তাহলে সব পুরানো এন্ট্রি মুছে দিন।
                $product->attributeValues()->detach();
            }
            return $product;
        });

        // 3. Response
        // Load the necessary relations and return the updated product with a 200 OK status
        return response()->json($product->load('attributeValues.attribute'), 200);
        // return response()->json('Product updated');
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
