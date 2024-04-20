<?php

namespace App\Http\Controllers\Registrar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('registrar.home');
    }
}
