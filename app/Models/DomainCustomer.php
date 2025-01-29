<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainCustomer extends Model
{
    use HasFactory;
    protected $table = 'domain_customer';
    protected $fillable = [
        'domain', 
        'customer_id'
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
