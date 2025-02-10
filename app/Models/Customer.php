<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'photo_url', 
        'photo_url1', 
        'photo_url2', 
        'photo_url3', 
        'photo_url4', 
        'photo_url5'
    ];

    public function domainCustomers()
    {
        return $this->hasMany(DomainCustomer::class, 'customer_id');
    }
    
    public function foundSuspiciousDomains()
    {
        return $this->hasMany(FoundSuspiciousDomain::class, 'customer_id');
    }
}
