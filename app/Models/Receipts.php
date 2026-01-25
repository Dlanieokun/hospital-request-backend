<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use function Illuminate\Queue\release;

class Receipts extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'reference', 'name', 'paid', 'mode', 'ref',
        'status', 'date', 'time', 'status_request', 'release_date', 'request_date',
    ];

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class, 'receipt_id');
    }
}