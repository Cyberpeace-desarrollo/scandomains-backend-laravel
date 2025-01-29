<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function domainCustomers()
    {
        return $this->hasMany(DomainCustomer::class, 'customer_id');
    }
    
    public function foundSuspiciousDomains()
    {
        return $this->hasMany(FoundSuspiciousDomain::class, 'customer_id');
    }
}
