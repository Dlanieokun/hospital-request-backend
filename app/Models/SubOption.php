<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubOption extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $fillable = [
        'certificate_id',
        'name',
    ];

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }
}
