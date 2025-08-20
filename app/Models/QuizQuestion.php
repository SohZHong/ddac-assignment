<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuizQuestion extends Model
{
    use HasFactory;

    const MCQ   = 0;
    const TRUE_FALSE = 1;
    const TEXT = 2;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'type',
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