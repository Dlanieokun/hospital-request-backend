<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    protected $connection = 'mysql';

    protected $fillable = ['name', 'fee', 'days'];

    public function subQuestions()
    {
        return $this->hasMany(SubQuestion::class);
    }

    public function subOptions()
    {
        return $this->hasMany(SubOption::class);
    }
}
