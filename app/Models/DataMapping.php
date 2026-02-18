<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataMapping extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sync_data';
    protected $fillable = ['key', 'key_sync', 'certificate_id'];

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }
}
