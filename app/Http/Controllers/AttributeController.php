<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index()
    {
        return Attribute::with('values')->get();
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        return Attribute::create($request->all());
    }

    public function show(Attribute $attribute)
    {
        return $attribute->load('values');
    }

    public function update(Request $request, Attribute $attribute)
    {
        $attribute->update($request->all());
        return $attribute;
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return response()->noContent();
    }

}
