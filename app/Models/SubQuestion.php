<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubQuestion extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $fillable = [
        'certificate_id',
        'question',
        'type',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}