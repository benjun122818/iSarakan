<?php

namespace App\Http\Controllers\Scholarship;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Scholarship;

class ScholarshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index_scholar()
    {
        return view('admins.scholarship.index');
    }
    public function index()
    {
        $s = Scholarship::all();

        return $s;
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'scholarship_type' => 'required',
                'scholarship' => 'required',
                'discount' => 'required',
                'sem_charged' => 'required',
                'funded_by' => 'required',
                'chargedfull' => 'required'

            ]
        );

        $s = new Scholarship();

        $s->scholarship_type = $request->scholarship_type;
        $s->scholarship      = $request->scholarship;
        $s->discount         = $request->discount;
        $s->sem_charged      = $request->sem_charged;
        $s->funded_by        = $request->funded_by;
        $s->chargedfull      = $request->chargedfull;

        $s->save();

        return response()->json([
            'message'   => 'Successfully Recorded'
        ], 200);
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $s = Scholarship::find($id);

        return $s;
    }

    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'scholarship_type' => 'required',
                'scholarship' => 'required',
                'discount' => 'required',
                'sem_charged' => 'required',
                'funded_by' => 'required',
                'chargedfull' => 'required'

            ]
        );

        $s = Scholarship::find($id);

        $s->scholarship_type = $request->scholarship_type;
        $s->scholarship      = $request->scholarship;
        $s->discount         = $request->discount;
        $s->sem_charged      = $request->sem_charged;
        $s->funded_by        = $request->funded_by;
        $s->chargedfull      = $request->chargedfull;

        $s->save();

        return response()->json([
            'message'   => 'Successfully Recorded'
        ], 200);
    }


    public function destroy($id)
    {
        $sd = Scholarship::find($id);

        $sd->delete();

        return response()->json([
            'message' => 'Deleted successfully!'
        ], 200);
    }
}
