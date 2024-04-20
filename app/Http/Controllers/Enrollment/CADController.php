<?php

namespace App\Http\Controllers\Enrollment;

use App\CAD;
use App\EnrollmentDetail;
use App\OrLog;
use App\OrRecord;
use App\Payment;
use App\Preference;
use App\Remark;
use App\Repositories\CADAssess;
use App\Repositories\NumberConverter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CADController extends Controller
{
    public function summary(Request $request, CADAssess $cad)
    {
        $id = $request->cad_id;

        $assess = $cad->assess($id);
        //dd($assess);

        $cad = CAD::find($id);
        $sRecord = $cad->record;
        $sInfo = $sRecord->info;

        $enrollment = $sRecord->enrollments()->where('pref_id', $cad->pref_id)->first();

        $studentNumber = $sInfo->student_number;
        $studentName = $sInfo->firstname . ' ' . $sInfo->surname;
        $college = $sInfo->degree->college->collegeabbr;
        $degree = $sInfo->degree->abbr;
        $scholarship = !is_null($enrollment->scholarship) ? $enrollment->scholarship->scholarship : '----';
        $yearAndSection = $enrollment->standing . $enrollment->section;
        $scholarshipChargeStatus = is_null($enrollment->scholarship) ? null : $enrollment->scholarship->chargedfull;

        $feesList = collect($assess->feesList);
        //  dd($feesList);

        $orRecord = null;

        $myORLog = OrLog::where('user', Auth::user()->id)->first();

        return  compact(
            'assess',
            'studentNumber',
            'college',
            'studentName',
            'degree',
            'scholarship',
            'yearAndSection',
            'feesList',
            'orRecord',
            'enrollment',
            'scholarshipChargeStatus',
            'sRecord',
            'cad',
            'myORLog'
        );
    }

    public function pay(Request $request, CADAssess $cadAssess, NumberConverter $numberConverter)
    {
        #dd($request->all());
        $this->validate(
            $request,
            [
                'or' => ['required'],
                'cash' => ['required']
            ]
        );

        $id = $request->cadid;

        $or = $request->or;

        $cad = CAD::find($id);
        $cad_details = $cad->cad_details; #dd($cad_details);

        $valor = OrRecord::where('or_number', $or)->where('branch', Auth::user()->branch)->first();

        if (!is_null($valor)) {
            return response()->json([
                'status' => 0,
                'message' => 'OR NUMBER ALREADY EXIST!',
                'footer' => 'Check entries!'
            ], 200);
            //return back()->with('err', 'OR NUMBER ALREADY EXIST!');
        }


        $assess = (object) $cadAssess->assess($id);
        //dd($assess);
        $payments = $assess->feesList; #dd($payments);
        $amtPaid = $assess->totalAmount;

        $student_record = $cad->record;
        $studentInfo = $student_record->info;
        $enrollment = $student_record->enrollments()->where('pref_id', $cad->pref_id)->first(); #dd($enrollment->id);

        $enlistment = $student_record->enlisted()->where('pref_id', $cad->pref_id)->first();

        if ($request->cash < $amtPaid) {
            return response()->json([
                'status' => 0,
                'message' => 'Amount Tendered not accepted!',
                'footer' => 'Check entries!'
            ], 200);
        }

        DB::beginTransaction();
        try {
            if (is_null($student_record->college_id)) {
                $degreeDetail = $student_record->degree;

                $student_record->college_id = $degreeDetail->college_id;
                $student_record->save();
            }

            if ($cad_details->count() > 0) {
                foreach ($cad_details as $cadDetail) {
                    switch ($cadDetail->type) {
                        case 1: // Drop
                            {
                                $enrDetail = EnrollmentDetail::find($cadDetail->original);

                                if (!is_null($enrDetail)) {
                                    $enrDetail->delete();
                                }

                                break;
                            }
                        case 2: // Change
                            {
                                $enrDetail = EnrollmentDetail::find($cadDetail->original);

                                if (!is_null($enrDetail)) {
                                    $enrDetail->sched_id = $cadDetail->sched_id;
                                    $enrDetail->curricula_detail_id = $cadDetail->curricula_detail_id;
                                    $enrDetail->course_id = $cadDetail->course_id;
                                    $enrDetail->section = $cadDetail->section;
                                    $enrDetail->deleted_at = null;
                                    $enrDetail->save();
                                }

                                break;
                            }
                        case 3: // Add
                            {
                                $enrDetail = new EnrollmentDetail();
                                $enrDetail->enrollment_id = $enrollment->id;
                                $enrDetail->sched_id = $cadDetail->sched_id;
                                $enrDetail->curricula_detail_id = $cadDetail->curricula_detail_id;
                                $enrDetail->course_id = $cadDetail->course_id;
                                $enrDetail->section = $cadDetail->section;
                                $enrDetail->deleted_at = null;
                                $enrDetail->save();

                                break;
                            }
                    }
                }

                // Update CAD dac_status
                $cad->dac_status = 5;
                $cad->save();

                // create OR record
                $orRecord = new OrRecord();
                $orRecord->enrollment_id = $enrollment->id;
                $orRecord->student_id = $student_record->student_id;
                $orRecord->pref_id = $cad->pref_id;
                $orRecord->or_number = $or;
                $orRecord->full = $amtPaid;
                $orRecord->down = 0;
                $orRecord->second = 0;
                $orRecord->third = 0;
                $orRecord->cash = $request->cash;
                $orRecord->amount = $amtPaid;
                $orRecord->balance = 0;
                $orRecord->payment_type = 1;
                $orRecord->currency  =  ($assess->studentType == 3 ? 2 : 1);
                $orRecord->status = 1;
                $orRecord->user = Auth::user()->id;
                $orRecord->branch = Auth::user()->branch;
                $orRecord->transaction_type = 2;
                $orRecord->save();


                foreach ($payments as $payment) {
                    $myPay = new Payment();
                    $myPay->or_id = $orRecord->id;
                    $myPay->fund = $payment['fund_id'];
                    $myPay->amount = $payment['amount'];
                    $myPay->college = $student_record->college_id;
                    $myPay->degree = $student_record->degree_id;
                    $myPay->save();
                }


                $remark = new Remark();
                $remark->enl_id = $enlistment->id;
                $remark->activity = "Paid ACD with the amount of " . $amtPaid . ($assess->studentType == 3 ? " dollars." : " pesos.");
                $remark->actor = 1;
                $remark->save();
                // Update User OR - [Increment]
                $orLog = OrLog::where('user', Auth::user()->id)->first();
                if (!is_null($orLog)) {
                    $orLog->last_or = $or + 1;
                    $orLog->save();
                } else {
                    $orLog = new OrLog();
                    $orLog->last_or = $or + 1;
                    $orLog->user = Auth::user()->id;
                    $orLog->save();
                }

                //$currency = 'Pesos';

                $currency = $orRecord->currency == 1 ? 'Pesos' : 'US Dollars';

                $converted = $numberConverter->toText($amtPaid, $currency);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }

        return compact(
            'or',
            //'studentInfo',
            //'student_record',
            //'payments',
            'amtPaid',
            'converted'
        );
    }
    public function print_charge(Request $request, CADAssess $cadAssess, NumberConverter $numberConverter)
    {

        $id = $request->cadid;

        $or = $request->or;

        $amtPaid = $request->amount;
        $converted = $request->converted;

        $cad = CAD::find($id);
        $cad_details = $cad->cad_details; #dd($cad_details);

        $assess = (object) $cadAssess->assess($id);
        //dd($assess);
        $payments = $assess->feesList; #dd($payments);
        $amtPaid = $assess->totalAmount;

        $student_record = $cad->record;
        $studentInfo = $student_record->info;
        $enrollment = $student_record->enrollments()->where('pref_id', $cad->pref_id)->first(); #dd($enrollment->id);

        //$enlistment = $student_record->enlisted()->where('pref_id', $cad->pref_id)->first();

        return view('enrollment.cad.receipt', compact(
            'or',
            'studentInfo',
            'student_record',
            'payments',
            'amtPaid',
            'converted'
        ));
    }
    public function scholarPay($cadid, CADAssess $cadAssess)
    {
        $id = $cadid;

        $cad = CAD::find($id);
        $cad_details = $cad->cad_details;

        $assess = $cadAssess->getAssessment($id);
        $payments = $assess->feesList;
        $amtPaid = $assess->totalAmount;

        $student_record = $cad->record;
        $enrollment = $student_record->enrollments()->where('pref_id', $cad->pref_id)->first();

        if (is_null($student_record->college_id)) {
            $degreeDetail = $student_record->degree;

            $student_record->college_id = $degreeDetail->college_id;
            $student_record->save();
        }

        if ($cad_details->count() > 0) {
            foreach ($cad_details as $cadDetail) {
                switch ($cadDetail->type) {
                    case 1: // Drop
                        {
                            $enrDetail = EnrollmentDetail::find($cadDetail->original);

                            if (!is_null($enrDetail)) {
                                $enrDetail->delete();
                            }

                            break;
                        }
                    case 2: // Change
                        {
                            $enrDetail = EnrollmentDetail::find($cadDetail->original);

                            if (!is_null($enrDetail)) {
                                $enrDetail->sched_id = $cadDetail->sched_id;
                                $enrDetail->curricula_detail_id = $cadDetail->curricula_detail_id;
                                $enrDetail->course_id = $cadDetail->course_id;
                                $enrDetail->section = $cadDetail->section;
                                $enrDetail->deleted_at = null;
                                $enrDetail->save();
                            }

                            break;
                        }
                    case 3: // Add
                        {
                            $enrDetail = new EnrollmentDetail();
                            $enrDetail->enrollment_id = $enrollment->id;
                            $enrDetail->sched_id = $cadDetail->sched_id;
                            $enrDetail->curricula_detail_id = $cadDetail->curricula_detail_id;
                            $enrDetail->course_id = $cadDetail->course_id;
                            $enrDetail->section = $cadDetail->section;
                            $enrDetail->deleted_at = null;
                            $enrDetail->save();

                            break;
                        }
                }
            }

            // Update CAD dac_status
            $cad->dac_status = 5;
            $cad->save();

            // create OR record
            $orRecord = new OrRecord();
            $orRecord->enrollment_id = $enrollment->id;
            $orRecord->student_id = $student_record->student_id;
            $orRecord->pref_id = $cad->pref_id;
            $orRecord->or_number = 'scholar';
            $orRecord->full = $amtPaid;
            $orRecord->down = 0;
            $orRecord->second = 0;
            $orRecord->third = 0;
            $orRecord->cash = null;
            $orRecord->amount = $amtPaid;
            $orRecord->balance = 0;
            $orRecord->payment_type = 1;
            $orRecord->scholar_charge = 100;
            $orRecord->scholarship = $enrollment->scholarship_id;
            $orRecord->status = 1;
            $orRecord->user = Auth::user()->id;
            $orRecord->branch = Auth::user()->branch;
            $orRecord->transaction_type = 2;
            $orRecord->save();

            foreach ($payments as $payment) {
                $myPay = new Payment();
                $myPay->or_id = $orRecord->id;
                $myPay->fund = $payment->fund_id;
                $myPay->amount = $payment->amount;
                $myPay->college = $student_record->college_id;
                $myPay->degree = $student_record->degree_id;
                $myPay->save();
            }

            return redirect()->route('paymentEnrollmentIndex')->with('message', 'Scholarship charged');
        }
    }
}