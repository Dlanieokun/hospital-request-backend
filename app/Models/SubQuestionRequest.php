<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubQuestionRequest extends Model
{
    protected $table = 'sub_question_request';
    protected $fillable = ['certificate_id', 'sub_question_id', 'answer'];

    public function details(): BelongsTo
    {
        return $this->belongsTo(SubQuestion::class, 'sub_question_id');
    }
}