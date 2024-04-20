<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IconFeather;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard');
    }
    public function user_type_index()
    {
        //$id = Auth::user()->id;
        $user_type = Auth::user();
        return $user_type;
    }

    public function get_feather_icons()
    {
        //$id = Auth::user()->id;
        $icons = IconFeather::all();
        return $icons;
    }
   
   
}
