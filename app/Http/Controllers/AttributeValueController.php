<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'value' => 'required|string'
        ]);

        return AttributeValue::create($request->all());
    }

}
