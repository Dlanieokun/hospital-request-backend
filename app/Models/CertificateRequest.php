<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateRequest extends Model
{
    protected $table = 'certificate_requests';
    protected $fillable = ['receipt_id', 'certificate_id'];

    public function details(): BelongsTo
    {
        // Use belongsTo because the 'certificate_id' is on THIS table.
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }

    public function subOptions()
    {
        return $this->hasMany(SubOptionRequest::class, 'certificate_id');
    }

    public function subQuestions()
    {
        return $this->hasMany(SubQuestionRequest::class, 'certificate_id');
    }


}