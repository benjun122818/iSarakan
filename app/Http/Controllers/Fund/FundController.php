<?php

namespace App\Http\Controllers\Fund;

use App\Fund;
use App\Fee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FundController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fund = Fund::with(['fees'])->orderBy('fund_desc')->get();

        return $fund;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                //'fee' => 'required',
                'fund' => 'required',
                'fund_desc' => 'required'

            ]
        );

        $bayad = $request->fee;

        $fund = new Fund();

        $fund->fund            = $request->fund;
        $fund->part            = $request->part == null ? 2 : $request->part;
        $fund->fund_desc       = $request->fund_desc;
        $fund->opt             = $request->opt;
        $fund->course_type_id  = $request->course_type_id;
        $fund->college_id      = $request->college_id;
        $fund->degree_id       = $request->degree_id;
        $fund->standing        = $request->standing;
        $fund->degree_type     = $request->degree_type;
        $fund->free_educ       = $request->free_educ;
        $fund->nef             = $request->nef;
        $fund->type            = $request->type;

        $fund->save();

        if (!is_null($bayad)) {
            $fee = new Fee();

            $fee->fee_sched_id = 1;
            $fee->fund_id = $fund->fund_id;
            $fee->amount = $bayad;

            $fee->save();
        }
        return response()->json([

            'message'   => 'Successfully Recorded'
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $funds = Fund::where('fund_id', $id)->with(['fees'])->first();

        return $funds;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'fees' => 'required',
                'fund_code' => 'required',
                'fund_desc' => 'required'

            ]
        );

        $update = Fund::where('fund_id', $id)->first();

        $update->fund           = $request->fund_code;
        $update->fund_desc      = $request->fund_desc;
        $update->course_type_id = $request->course_type_id;
        $update->degree_id      = $request->degree_id;
        $update->degree_type    = $request->degree_type;
        $update->college_id     = $request->college_id;
        $update->opt            = $request->opt;
        $update->part           = $request->part;
        $update->standing       = $request->standing;
        $update->free_educ      = $request->fund_free;
        $update->nef            = $request->fund_nef;
        $update->type            = $request->type;
        $update->save();

        $updatefee = Fee::where('fund_id', $id)->first();

        if (is_null($updatefee)) {
            $insertfees = new Fee();
            $insertfees->fee_sched_id = 1;
            $insertfees->fund_id = $id;
            $insertfees->amount = $request->fees;

            $insertfees->save();
        } else {
            $updatefee->fee_sched_id = 1;
            $updatefee->amount = $request->fees;

            $updatefee->save();
        }


        return response()->json([
            'message' => 'Record updated successfully!'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $funddel = Fund::where('fund_id', $id)->first();

        if (!is_null($funddel->fees)) {
            $feedel = Fee::where('fund_id', $id)->first();
            $feedel->delete();
        }


        $funddel->delete();


        return response()->json([
            'message' => 'fund deleted successfully!'
        ], 200);
    }
}
