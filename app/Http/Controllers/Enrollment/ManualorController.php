<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Course;
use App\EnlistmentDetail;
use App\Enrollment;
use App\EnrollmentDetail;
use App\OrLog;
use App\OrRecord;
use App\Payment;
use App\Preference;
use App\Assessmentmaual;
use App\Repositories\Assessment;
use App\CAD;
use App\Repositories\CADAssess;
use App\Repositories\num2Words;
use App\Repositories\NumberConverter;
use App\Sresu;
use App\StudentRecord;
use App\StudentInfo;
use App\PayControl;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NumberToWords\NumberToWords;

class ManualorController extends Controller
{

    public function manualpref()
    {

        $preferences = Preference::with(['cys'])->get(['id', 'cy_id', 'sem', 'status', 'enlistment']);

        // foreach (Preference::where('id', '>=', 19)->get() as $key => $pref) {
        //     $sem = "";
        //     if ($pref->sem == 1) {
        //         $sem = "{$pref->cys->cy} | First Semester";
        //     } else if ($pref->sem == 2) {
        //         $sem = "{$pref->cys->cy} | Second Semester";
        //     } else if ($pref->sem == 3) {
        //         $sem = "{$pref->cys->cy} | Mid-year";
        //     }
        //     $preferences[$pref->id] = $sem;
        // }

        return $preferences;

        // return view('enrollment.payment.index', compact('preferences'));
    }

    public function checkpayemnt(Request $request, Assessment $assessment, CADAssess $cad)
    {

        $this->validate(
            $request,
            [
                'studnumber' => 'required',
                'pref_id' => 'required',

            ],
            [
                'studnumber.required' => 'Type Student number',
                'pref_id.required' => 'Select Semester'
            ]
        );

        $student = Sresu::where('student_number', $request->studnumber)->first();

        $student_record = $student->record;

        $checkpayment = OrRecord::where('student_id', $student->id)->where('pref_id', $request->pref_id)->where('payment_type', 1)->where('transaction_type', 1)->first();

        $enrollment = $student_record->enrollment()->where('pref_id', $request->pref_id)->orderBy('created_at', 'desc')->first();


        $assess = 0;


        //    if (!is_null($cadget))
        //    {
        //        $assess = $cad->getAssessment($cadget->id); //dd($assess->totalAmount);
        // if ($cad->dac_status != 3)
        // {
        //     return back()->with('err', 'Student is already enrolled but CAD is not yet approved by enrolling adviser or has not been submitted yet for approval!');
        // }
        // else
        // {
        //     return redirect()->route('cadSummary', ['id'=>$cad->id]);
        //  }
        //    }

        $enlisted = $student_record->enlistment()->where('pref_id', $request->pref_id)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
        // return $enlisted; 
        if (is_null($enlisted)) {
            return response()->json([
                'err'  => 2,
                'message' => "Student is not yet enlisted or enlistment is not yet approved by the student adviser."
            ], 200);
        }

        $getcad = $enrollment->cad()->where('pref_id', $request->pref_id)->where('dac_status', 3)->first();
        //return $student->record;

        // $getor = OrRecord::where('student_id', $student->id)

        if (is_null($student)) {
            return response()->json([
                'err'  => 1,
                'message' => 'STUDENT NOT FOUND'
            ], 200);
        }

        if (!is_null($getcad)) {
            //$cadget = $student_record->cad()->where('pref_id', $request->pref_id)->where('dac_status', 3)->first(); #dd($cad);
            $assess = $cad->getAssessment($getcad->id)->totalAmount; #dd($assess);
        } else {
            if (!is_null($checkpayment)) {
                return response()->json([
                    'err'  => 0,
                    'message' => 'ALREADY MADE PAYMENT'
                ], 200);
            }
        }

        $student_record = $student->record;
        $student_info = $student->info;



        //$enrollment = $student_record->enrollment()->where('pref_id', 22)->orderBy('created_at', 'desc')->first();

        $resultDecode = $assessment->assess($student_record->id, $enlisted->pref_id); //dd($resultDecode);

        if (is_null($resultDecode)) {
            return response()->json([
                'err'  => 3,
                'message' => 'ASSESMENT NOT FOUND FROM API'
            ], 200);
        }


        // if (!is_null($enrollment))
        // {

        // }
        // else
        // {

        // }
        $college = $student_info->degree->college->collegeabbr;
        $degree = $student_info->degree->abbr;
        //number_format($resultDecode['secondPayment'], 2)


        $downandsec = $resultDecode['downPayment'] + $resultDecode['secondPayment'];
        // return $downandsec;

        return response()->json([
            'student_info'      => $student_info,
            'fullPayment'       => number_format($resultDecode['fullPayment'], 2),
            'downPayment'       => number_format($resultDecode['downPayment'], 2),
            'secondPayment'     => number_format($resultDecode['secondPayment'], 2),
            'downsecondPayment' => number_format($downandsec, 2),
            'thirdPayment'      => number_format($resultDecode['thirdPayment'], 2),
            'college'           => $college,
            'degree'            => $degree,
            'srid'              => $student_record->id,
            'cadassest'         => ($assess == 0 ? 0 : $assess)
        ], 200);
    }

    public function charge(Request $request, Assessment $assessment)
    {

        $this->validate(
            $request,
            [
                'mode' => 'required',
                'or' => 'required',
                'ordate' => 'required'

            ],
            [
                'or.required' => 'Type OR number',
                'mode.required' => 'Select mode payment',
                'ordate.required' => 'Select date'
            ]
        );
        $resultDecode = session('assessment');

        $mode = $request->mode;
        $or = $request->or;
        $rawdate = $request->ordate;

        $date = date("Y-m-d H:i:s", strtotime($rawdate));

        $resultDecode = $assessment->assess($request->srid, $request->pref); //return $resultDecode;


        $student_record = StudentRecord::find($request->srid); //return  $student_record;
        // $studentInfo = $student_record->info;

        //return $student_record->college_id;

        if (is_null($student_record->college_id)) {
            $degreeDetail = $student_record->degree;

            $student_record->college_id = $degreeDetail->college_id;
            $student_record->save();
        }

        #$resultDecode = $assessment->assess($request->srid); #dd($resultDecode);

        if ($mode == 'full') {
            $amtPaid = $resultDecode['fullPayment'];
            $payType = 1;
            $payList = $resultDecode['paymentsList']['fullpaymentFeeList'];
            $balanceCode = 0;
        } elseif ($mode == 'down') {
            $amtPaid = $resultDecode['downPayment'];
            $payType = 2;
            $payList = $resultDecode['paymentsList']['downpaymentFeeList'];
            $balanceCode = 23;
        } elseif ($mode == 'second') {
            $amtPaid = $resultDecode['secondPayment'];
            $payType = 6;
            $payList = $resultDecode['paymentsList']['secondpaymentFeeList'];
            $balanceCode = 13;
        } elseif ($mode == 'third') {
            $amtPaid = $resultDecode['thirdPayment'];
            $payType = 4;
            $payList = $resultDecode['paymentsList']['thirdpaymentFeeList'];
            $balanceCode = 12;
        } elseif ($mode == 'scholar') {
            $amtPaid = $resultDecode['fullPayment'];
            $payType = 5;
            $payList = $resultDecode['paymentsList']['fullpaymentFeeList'];
            $balanceCode = 0;
        } elseif ($mode == 'down-and-second') {
            /*
             * DOWN & SECOND PAYMENT
             */
            $downPayFeesList = $resultDecode['paymentsList']['downpaymentFeeList']; #dd($downPayFeesList);
            $secondPayFeesList = $resultDecode['paymentsList']['secondpaymentFeeList'];

            $arrFList = [];
            foreach ($downPayFeesList as $list) {
                $list = (object) $list;
                $arrFList[$list->fund_id] = ['fund_id' => $list->fund_id, 'fund' => $list->fund, 'fund_desc' => $list->fund_desc, 'amount' => $list->amount];
            }

            foreach ($secondPayFeesList as $list) {
                $list = (object) $list;
                if (array_key_exists($list->fund_id, $arrFList)) {
                    $downAmount = $arrFList[$list->fund_id]['amount'];
                    $secondAmount = $list->amount;
                    $newAmount = $downAmount + $secondAmount;

                    $arrFList[$list->fund_id] = ['fund_id' => $list->fund_id, 'fund' => $list->fund, 'fund_desc' => $list->fund_desc, 'amount' => $newAmount];
                } else {
                    $arrFList[$list->fund_id] = ['fund_id' => $list->fund_id, 'fund' => $list->fund, 'fund_desc' => $list->fund_desc, 'amount' => $list->amount];
                }
            }


            $feesList = collect([]);
            foreach ($arrFList as $adjustedFeesList) {
                $arr = [
                    'fund_id' => $adjustedFeesList['fund_id'],
                    'fund' => $adjustedFeesList['fund'],
                    'fund_desc' => $adjustedFeesList['fund_desc'],
                    'amount' => $adjustedFeesList['amount']
                ];
                $feesList->push((object) $arr);
            }

            $payList = $feesList;
            $payType = 3;
            $amtPaid = $feesList->sum('amount');
            $balanceCode = 3;
        }

        #dd($balanceCode);

        $enrollment = $student_record->enrollment()->where('pref_id', $request->pref)->orderBy('created_at', 'desc')->first(); //dd($enrollment);
        //return $enrollment;
        if (is_null($enrollment)) {
            $enlistment = $student_record->enlistment()->where('pref_id', $request->pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first(); #dd($enlistment);

            if (!is_null($enlistment)) {

                $enlistmentDetails = $enlistment->details;

                if ($enlistmentDetails->count() > 0) {

                    $enrollment = new Enrollment();
                    $enrollment->student_rec_id = $enlistment->student_rec_id;
                    $enrollment->section = $enlistment->section;
                    $enrollment->pref_id = $enlistment->pref_id;
                    $enrollment->standing = $enlistment->standing;
                    $enrollment->status_id = $enlistment->status_id;
                    $enrollment->scholarship_id = $enlistment->scholarship_id;
                    $enrollment->free_tuition = $enlistment->free_tuition;
                    $enrollment->fee_sched_id = $enlistment->fee_sched_id;
                    $enrollment->voluntary_contribution = $enlistment->voluntary_contribution;
                    $enrollment->save();

                    #$orRecord = OrRecord::where('student_id', $student_record->student_id)->where('pref_id', $this->pref->id)->first();

                    $orRecord = new OrRecord();
                    $orRecord->enrollment_id = $enrollment->id;
                    $orRecord->student_id = $student_record->student_id;
                    $orRecord->pref_id = $enlistment->pref_id;
                    $orRecord->or_number = $or;
                    $orRecord->full = $resultDecode['fullPayment'];
                    $orRecord->down = $resultDecode['downPayment'];
                    $orRecord->second = $resultDecode['secondPayment'];
                    $orRecord->third = $resultDecode['thirdPayment'];
                    $orRecord->cash = $request->cash;
                    $orRecord->amount = $amtPaid;
                    $orRecord->balance = ($resultDecode['fullPayment'] - $amtPaid);
                    $orRecord->balance_code  = $balanceCode;
                    $orRecord->payment_type = $payType;
                    $orRecord->status = 1;
                    $orRecord->user = Auth::user()->id;
                    $orRecord->branch = Auth::user()->branch;
                    $orRecord->transaction_type = 1;
                    $orRecord->created_at  = $date;
                    $orRecord->save();

                    foreach ($payList as $payment) {
                        $payment = (object) $payment;

                        $payments = new Payment();
                        $payments->or_id = $orRecord->id;
                        $payments->fund = $payment->fund_id;
                        $payments->amount = $payment->amount;
                        $payments->college = $student_record->college_id;
                        $payments->degree = $student_record->degree_id;
                        $payments->save();
                    }

                    foreach ($enlistmentDetails as $enlDetail) {
                        $curriculaDetail = $enlDetail->curDetail;

                        $enrollmentDetails = new EnrollmentDetail();
                        $enrollmentDetails->enrollment_id = $enrollment->id;
                        $enrollmentDetails->sched_id = $enlDetail->sched_id;
                        $enrollmentDetails->curricula_detail_id = $enlDetail->curricula_detail_id;
                        $enrollmentDetails->course_id = $curriculaDetail->course_id;
                        $enrollmentDetails->section = $enlDetail->section;
                        $enrollmentDetails->save();
                    }


                    return response()->json([
                        'message'  => 'SUCCESS OPERATION',
                    ], 200);
                }
            }
            return response()->json([
                'err'  => '1',
            ], 200);
        } else {
            $enlistment = $student_record->enlistment()->where('pref_id', $request->pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();

            $orRecord = new OrRecord();
            $orRecord->enrollment_id = $enrollment->id;
            $orRecord->student_id = $student_record->student_id;
            $orRecord->pref_id = $enlistment->pref_id;
            $orRecord->or_number = $or;
            $orRecord->full = $resultDecode['fullPayment'];
            $orRecord->down = $resultDecode['downPayment'];
            $orRecord->second = $resultDecode['secondPayment'];
            $orRecord->third = $resultDecode['thirdPayment'];
            $orRecord->cash = $request->cash;
            $orRecord->amount = $amtPaid;
            $orRecord->balance = ($resultDecode['fullPayment'] - $amtPaid);
            $orRecord->balance_code  = $balanceCode;
            $orRecord->payment_type = $payType;
            $orRecord->status = 1;
            $orRecord->user = Auth::user()->id;
            $orRecord->branch = Auth::user()->branch;
            $orRecord->transaction_type = 1;
            $orRecord->created_at  = $date;
            $orRecord->save();

            foreach ($payList as $payment) {
                $payment = (object) $payment;

                $payments = new Payment();
                $payments->or_id = $orRecord->id;
                $payments->fund = $payment->fund_id;
                $payments->amount = $payment->amount;
                $payments->college = $student_record->college_id;
                $payments->degree = $student_record->degree_id;
                $payments->save();
            }

            return response()->json([
                'message'  => 'SUCCESS OPERATION',
            ], 200);
        }
        dd("Student already enrolled.");
    }

    public function onlinePaymentCharge(Request $request, Assessment $assessment)
    {
        request()->validate([
            'trans_ref_number' => 'required',
            'srid' => 'required',
            'pref' => 'required',
            'created_at' => 'required'
        ]);

        $rawdate = $request->created_at;

        $date = date("Y-m-d H:i:s", strtotime($rawdate));


        $resultDecode = $assessment->assess($request->srid, $request->pref); #dd($resultDecode);
        // $resultDecode = $assessment->getAssessment($request->srid, $this->pref->id); #dd($resultDecode);

        $student_record = StudentRecord::find($request->srid);
        $studentInfo = $student_record->info;

        $amtPaid = $resultDecode['fullPayment'];
        $payType = 6; // ONLINE FULL
        $payList = $resultDecode['paymentsList']['fullpaymentFeeList'];

        $enrollment = $student_record->enrollment()->where('pref_id', $request->pref)->first(); #dd($enrollment);

        if (is_null($student_record->college_id)) {
            $degreeDetail = $student_record->degree;

            $student_record->college_id = $degreeDetail->college_id;
            $student_record->save();
        }

        if (is_null($enrollment)) {
            $enlistment = $student_record->enlistment()->where('pref_id', $request->pref)->where('enlistment_status', 2)->first(); #dd($enlistment);

            if (!is_null($enlistment)) {

                $enlistmentDetails = $enlistment->details;

                if ($enlistmentDetails->count() > 0) {

                    $enrollment = new Enrollment();
                    $enrollment->student_rec_id = $enlistment->student_rec_id;
                    $enrollment->section = $enlistment->section;
                    $enrollment->pref_id = $enlistment->pref_id;
                    $enrollment->standing = $enlistment->standing;
                    $enrollment->status_id = $enlistment->status_id;
                    $enrollment->scholarship_id = $enlistment->scholarship_id;
                    $enrollment->free_tuition = $enlistment->free_tuition;
                    $enrollment->fee_sched_id = $enlistment->fee_sched_id;
                    $enrollment->voluntary_contribution = $enlistment->voluntary_contribution;
                    $enrollment->save();

                    #$orRecord = OrRecord::where('student_id', $student_record->student_id)->where('pref_id', $this->pref->id)->first();

                    $orRecord = new OrRecord();
                    $orRecord->enrollment_id = $enrollment->id;
                    $orRecord->student_id = $student_record->student_id;
                    $orRecord->pref_id = $request->pref;
                    $orRecord->or_number = 'online';
                    $orRecord->full = $resultDecode['fullPayment'];
                    $orRecord->down = $resultDecode['downPayment'];
                    $orRecord->second = $resultDecode['secondPayment'];
                    $orRecord->third = $resultDecode['thirdPayment'];
                    $orRecord->amount = $amtPaid;
                    $orRecord->balance = ($resultDecode['fullPayment'] - $amtPaid);
                    $orRecord->payment_type = $payType;
                    $orRecord->status = 1;
                    $orRecord->user = Auth::user()->id;
                    $orRecord->branch = Auth::user()->branch;
                    $orRecord->transaction_type = 1;
                    $orRecord->trans_ref_number = $request->trans_ref_number;
                    $orRecord->created_at = $date;
                    $orRecord->updated_at = $date;
                    $orRecord->save();

                    foreach ($payList as $payment) {
                        $payments = new Payment();
                        $payments->or_id = $orRecord->id;
                        $payments->fund = $payment['fund_id'];
                        $payments->amount = $payment['amount'];
                        $payments->college = $student_record->college_id;
                        $payments->degree = $student_record->degree_id;
                        $payments->save();
                    }

                    foreach ($enlistmentDetails as $enlDetail) {
                        $curriculaDetail = $enlDetail->curDetail;

                        $enrollmentDetails = new EnrollmentDetail();
                        $enrollmentDetails->enrollment_id = $enrollment->id;
                        $enrollmentDetails->sched_id = $enlDetail->sched_id;
                        $enrollmentDetails->curricula_detail_id = $enlDetail->curricula_detail_id;
                        $enrollmentDetails->course_id = $curriculaDetail->course_id;
                        $enrollmentDetails->section = $enlDetail->section;
                        $enrollmentDetails->save();
                    }

                    return response()->json([
                        'message'  => 'SUCCESS OPERATION',
                    ], 200);
                }
            }
            dd("Student enlistment not found.");
        } else {
            $orRecord = new OrRecord();
            $orRecord->enrollment_id = $enrollment->id;
            $orRecord->student_id = $student_record->student_id;
            $orRecord->pref_id = $request->pref;
            $orRecord->or_number = 'online';
            $orRecord->full = $resultDecode['fullPayment'];
            $orRecord->down = $resultDecode['downPayment'];
            $orRecord->second = $resultDecode['secondPayment'];
            $orRecord->third = $resultDecode['thirdPayment'];
            $orRecord->amount = $amtPaid;
            $orRecord->balance = ($resultDecode['fullPayment'] - $amtPaid);
            $orRecord->payment_type = $payType;
            $orRecord->status = 1;
            $orRecord->user = Auth::user()->id;
            $orRecord->branch = Auth::user()->branch;
            $orRecord->transaction_type = 1;
            $orRecord->trans_ref_number = $request->trans_ref_number;
            $orRecord->created_at = $date;
            $orRecord->updated_at = $date;
            $orRecord->save();

            foreach ($payList as $payment) {
                $payments = new Payment();
                $payments->or_id = $orRecord->id;
                $payments->fund = $payment['fund_id'];
                $payments->amount = $payment['amount'];
                $payments->college = $student_record->college_id;
                $payments->degree = $student_record->degree_id;
                $payments->save();
            }

            return response()->json([
                'message'  => 'SUCCESS OPERATION',
            ], 200);
        }

        dd("Student already enrolled.");
    }

    public function orupdate(Request $request, $id)
    {
        $this->validate($request, [
            'or_number'        => 'required|max:255',
            'created_at' => 'required',
            'orstatus' => 'required',
        ]);

        $getor = OrRecord::find($id);

        $getor->or_number = request('or_number');
        $getor->status = request('orstatus');
        $getor->created_at = request('created_at');
        $getor->save();

        return response()->json([
            'message' => 'Updated successfully!'
        ], 200);
    }

    public function adminspaymentcontrol()
    {
        return view('admins.paymentcontrol.index');
    }

    public function storepaycontrol(Request $request)
    {
        $i = PayControl::all();

        if (count($i) > 0) {

            return response()->json([
                'status' => 1,
                'message'   => 'Nope'
            ], 200);
        }

        $item = new PayControl();

        $item->control  =  $request->controlcode;

        $item->save();

        return response()->json([
            'item' => $item,
            'status' => 0,
            'message'   => 'Successfully Recorded'
        ], 200);
    }

    public function updatepaycontrol($id)
    {

        $update = PayControl::find($id);

        if ($update->control == 0) {

            $update->control = 1;
        } else {
            $update->control = 0;
        }


        $update->save();

        return response()->json([
            'message' => 'Item updated successfully!'
        ], 200);
    }

    public function getpaycontrol()
    {
        $item = PayControl::first();

        return $item;
    }
}
