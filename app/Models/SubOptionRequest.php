<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubOptionRequest extends Model
{
    protected $table = 'sub_option_request';
    protected $fillable = ['certificate_id', 'sub_option_id'];


    public function details(): BelongsTo
    {
        return $this->belongsTo(SubOption::class, 'sub_option_id');
    }
}