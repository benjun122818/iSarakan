<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityMuni extends Model
{
    protected $connection = 'dbcommon';

    protected $table = 'refcitymun';
}
