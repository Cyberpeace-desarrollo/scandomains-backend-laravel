<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundSuspiciousDomain extends Model
{
    use HasFactory;
    protected $table = 'found_suspicious_domains';
    protected $fillable = [
        'suspicious_domain',
        'found_date',
        'customer_id'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
