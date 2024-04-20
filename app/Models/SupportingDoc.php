<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportingDoc extends Model
{
    use HasFactory;
    protected $table = 'dorm_business_file';
    public $timestamps = false;
}
