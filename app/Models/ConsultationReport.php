<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsultationReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uploaded_by',
        'report_url',
        'notes',
    ];

    // The patient
    public function patient()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // The doctor who uploaded
    public function doctor()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
