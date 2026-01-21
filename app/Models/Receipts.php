<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipts extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['reference', 'name', 'paid', 'status'];
    //
}
