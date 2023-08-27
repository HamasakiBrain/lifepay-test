<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AcquirerInfo
 * @package App\Models
 * @property int attempts
 * @property string acquirer @see Acquirer::ACQUIRERS
 * @property string acquirerNumber
 * */
class AcquirerInfo extends Model
{
    public $table = 'acquire_info';

    protected $fillable = [
        'booking_id',
        'acquirer',
        'acquirerNumber'
    ];
}
