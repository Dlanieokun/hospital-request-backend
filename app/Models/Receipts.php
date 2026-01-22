<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipts extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'reference', 'name', 'paid', 'mode', 'ref',
        'status', 'date', 'time', 'status-request'
    ];

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class, 'receipt_id');
    }
}