<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // table default = "categories", jadi ga perlu override lagi

    protected $fillable = [
        'name', 
    ];

    
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }
}
