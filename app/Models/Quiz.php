<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'healthcare_id',
        'title',
        'description',
    ];

    /**
     * Quiz belongs to a healthcare professional
    */
    public function healthcare()
    {
        return $this->belongsTo(User::class, 'healthcare_id');
    }

    /**
     * Quiz has many questions
     */
    public function questions()
    {
        return $this->hasMany(QuizQuestion::class);
    }

    /**
     * Quiz has many responses
     */
    public function responses()
    {
        return $this->hasMany(QuizResponse::class);
    }
}
