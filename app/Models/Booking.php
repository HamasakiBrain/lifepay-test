<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['name', 'status', 'amount', 'description', 'method', 'customer_phone', 'email', 'orderId'];
}
