<?php

namespace App\Http\Controllers\Scholarship;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Scholarship;
use App\ScholarshipDeduction;

class ScholarshipDeductionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index_sd()
    {
        return view('admins.scholarship.scholarship_deduct');
    }

    public function index()
    {
        $sd = ScholarshipDeduction::join('scholarships', 'scholarships.id', '=', 'scholarship_deductions.scholarship_id')
            ->select([
                'scholarship_deductions.*',
                'scholarships.scholarship'
            ])->get();

        return $sd;
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
    }
}
