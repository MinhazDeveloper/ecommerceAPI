<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 

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
    // public function attributeValues()
    // {
    //     return $this->belongsToMany(AttributeValue::class, 'attribute_product');
    // }
    public function attributeValues()
    {
        return $this->belongsToMany(
            AttributeValue::class, 
            'attribute_product', 
            'product_id', 
            'attribute_value_id' 
        );
    }


}
