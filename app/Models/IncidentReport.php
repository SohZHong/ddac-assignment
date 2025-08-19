<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncidentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'description', 'reported_by', 'status', 'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}


