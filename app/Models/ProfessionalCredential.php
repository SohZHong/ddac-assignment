<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalCredential extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'credential_type',
        'credential_number',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'document_path',
        'additional_info',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the user that owns the credential.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the credential is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null && now()->greaterThan($this->expiry_date);
    }

    /**
     * Check if the credential is about to expire (within 30 days)
     */
    public function isExpiringSoon(): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }
        
        $now = now();
        return $now->lessThan($this->expiry_date) && 
               $now->diffInDays($this->expiry_date) <= 30;
    }
}
