<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'contact_person', 'phone', 'email',
        'address', 'city', 'province', 'postal_code', 'tax_id', 'customer_type', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function outbounds(): HasMany { return $this->hasMany(Outbound::class); }
}
