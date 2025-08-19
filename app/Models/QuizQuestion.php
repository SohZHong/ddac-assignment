<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'options', // JSON for multiple choice
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Question belongs to a quiz
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}