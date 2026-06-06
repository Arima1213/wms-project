<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'contact_person', 'phone', 'email',
        'address', 'city', 'province', 'postal_code', 'tax_id', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function inbounds(): HasMany { return $this->hasMany(Inbound::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}
