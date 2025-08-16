<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use hasFactory;

    // Day of week
    const SUNDAY = 0;
    const MONDAY = 1; 
    const TUESDAY = 2; 
    const WEDNESDAY = 3; 
    const THURSDAY = 4; 
    const FRIDAY = 5;
    const SATURDAY = 6; 

    protected $fillable = [
        'healthcare_id', 'day_of_week', 'start_time', 'end_time'
    ];

    public function healthcare()
    {
        return $this->belongsTo(User::class, 'healthcare_id');
    }
}
