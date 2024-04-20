<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DormType extends Model
{
    use HasFactory;
    protected $table = 'dorm_types';
    public $timestamps = false;
}
