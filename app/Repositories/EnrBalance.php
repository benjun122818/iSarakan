<?php

namespace App\Repositories;

use App\Curricula;
use App\Fund;
use App\Preference;
use App\Scholarship;
use App\StudentInfo as Student;
use App\StudentRecord;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class EnrBalance
{
    public static function balance($code)
    {
        if ($code == 1)
        {
            return 'down';
        }
        elseif ($code == 2)
        {
            return 'second';
        }
        elseif ($code == 3)
        {
            return 'third';
        }
    }

    public function downPayList()
    {

    }
}