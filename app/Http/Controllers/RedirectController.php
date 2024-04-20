<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    public function redirect()
    {
        if(Auth::check())
        {
            if (Auth::user()->access_id == 6)
            {
                return redirect()->route('registrarHomeIndex');
            }
            elseif (Auth::user()->access_id == 5)
            {
                return redirect('home');
            }
        }

        return redirect('/login');
    }
}
