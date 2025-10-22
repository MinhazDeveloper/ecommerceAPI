<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name','slug','description','price','stock','category_id','brand_id','status'];

    public function category() {
        return $this->belongsTo(Category::class);
    }
    public function images() {
        return $this->hasMany(ProductImage::class);
    }
    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_product');
    }


}
