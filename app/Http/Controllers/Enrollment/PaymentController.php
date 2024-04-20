<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Course;
use App\EnlistmentDetail;
use App\Enrollment;
use App\Enlistment;
use App\EnrollmentDetail;
use App\OrLog;
use App\OrRecord;
use App\Payment;
use App\Preference;
use App\Repositories\Assessment;
use App\Repositories\num2Words;
use App\Repositories\NumberConverter;
use App\Sresu;
use App\StudentRecord;
use App\Remark;
use App\PayControl;
use Exception;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NumberToWords\NumberToWords;

class PaymentController extends Controller
{

    protected $pref;

    public function __construct()
    {
        $this->pref = Preference::where('enlistment', 1)->get()->pluck('id')->all();

        //for charge function
        $this->or_charge = null;
        //for charge function
    }

    public function enrollment_prefs()
    {
        session()->forget('assessment');
        //$preferences = [];

        $a = Preference::where('id', '>=', 18)->orderBy('enlistment', 'desc')->get();
        $b = [];
        foreach ($a as $pref) {
            $sem = "";
            if ($pref->sem == 1) {
                $sem = "{$pref->cys->cy} | First Semester";
            } else if ($pref->sem == 2) {
                $sem = "{$pref->cys->cy} | Second Semester";
            } else if ($pref->sem == 3) {
                $sem = "{$pref->cys->cy} | Mid-year";
            }
            // $preferences[$pref->id] = $sem;

            $a = [
                'pref' => $pref->id,
                'sem' => $sem,
            ];

            array_push($b, $a);
        }

        //dd($preferences);
        return response()->json([
            'prefs' => $b,
            'pref_id' => $pref->id
        ], 200);
        //return $b;
    }

    public function val_enr_summary(Request $request, Assessment $assessment)
    {

        $this->validate(
            $request,
            [
                'student_number' => ['required']
            ]
        );

        /*
        Payment type
        enrollment = 1
        cad = 2
        */

        $pref_id = $request->pref;
        //return $pref_id;
        $student = Sresu::where('student_number', $request->student_number)->first();
        if (is_null($student)) {

            return response()->json([
                'status' => 0,
                'message' => 'Student not found!'
            ], 200);
        }


        $student_record = $student->record; #dd($student_record->id);

        $enrollment = null;
        $cad = null;
        $enlisted = null;

        foreach ($student->recordTwo as $student_record) {
            //$data[] = $student_record->enlistment()->get(['enlisted.pref_id']);
            $enrollment = $student_record->enrollment()->with(['orRecords'])->where('pref_id', $pref_id)->orderBy('created_at', 'desc')->first();


            $cad = $student_record->cad()->where('pref_id', $pref_id)->whereNotIn('dac_status', array(4, 5))->first(); #dd($cad);


            $enlisted = $student_record->enlistment()->where('pref_id', $pref_id)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();

            if ($cad) {
                break;
            }

            if ($enrollment) {
                break;
            }

            if ($enlisted) {
                break;
            }
        }


        if (!is_null($enrollment)) {
            $orRecord =  $enrollment->orRecord; //dd($orRecord);
            if (!is_null($cad)) {

                switch ($cad->dac_status) {
                    case 1:

                        return response()->json([
                            'status' => 0,
                            'message' => 'CAD pending for faculty approval!'
                        ], 200);

                        break;
                    case 2:

                        return response()->json([
                            'status' => 0,
                            'message' => 'CAD return to student!'
                        ], 200);
                        break;
                    case 3:
                        //       return redirect()->route('cadSummary', ['id' => $cad->id]);

                        return response()->json([
                            'status' => 1,
                            'cad_id' => $cad->id,
                            'payment_type' => 2
                        ], 200);

                        break;
                }
            }
        } else {
            $orRecord = null;
        }

        if (is_null($enlisted)) {

            return response()->json([
                'status' => 0,
                'message' => 'Student is not yet enlisted or enlistment is not yet approved by the student adviser.'
            ], 200);
        }
        //return $enrollment;
        //$orRecord = (!is_null($enrollment)) ? $enrollment->orRecord : null; //dd($orRecord);
        //$orRecord = $enrollment->orRecord;


        // $studentName = $student_info->firstname . ' ' . $student_info->surname;
        // $studentNumber = $student_info->student_number;
        // $college = $student_info->degree->college->collegeabbr;
        // $degree = $student_info->degree->abbr;
        // $scholarship = !is_null($enlisted->scholarship) ? $enlisted->scholarship->scholarship : '----';

        // $resultDecode = $assessment->assess($student_record->id, $enlisted->pref_id);
        //dd($resultDecode);

        //$resultDecode = $assessment->getAssessment($student_record->id, $enlisted->pref_id);

        // $assessSession = session('assessment');
        // session(['assessment' => $resultDecode]);

        return response()->json([
            'status' => 1,
            'payment_type' => 1
        ], 200);
    }

    public function enrSummary(Request $request, Assessment $assessment)
    {


        /*
        Payment type
        enrollment = 1
        cad = 2
        */

        $pref_id = $request->pref;
        //return $pref_id;
        $student = Sresu::where('student_number', $request->sno)->first();
        //return $student;

        $student_record = $student->record; #dd($student_record->id);
        $student_info = $student->info;

        $enrollment = null;
        $cad = null;
        $enlisted = null;

        foreach ($student->recordTwo as $student_record) {
            //$data[] = $student_record->enlistment()->get(['enlisted.pref_id']);
            $enrollment = $student_record->enrollment()->with(['orRecords'])->where('pref_id', $pref_id)->orderBy('created_at', 'desc')->first();


            $cad = $student_record->cad()->where('pref_id', $pref_id)->whereNotIn('dac_status', array(4, 5))->first(); #dd($cad);


            $enlisted = $student_record->enlistment()->where('pref_id', $pref_id)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();

            if ($cad) {
                break;
            }

            if ($enrollment) {
                break;
            }

            if ($enlisted) {
                break;
            }
        }

        $orRecord = null;



        if (!is_null($enrollment)) {
            $yearAndSection = $enrollment->standing . $enrollment->section;
            //$orRecord = $enrollment->orRecord;
        } elseif (!is_null($enlisted)) {
            $yearAndSection = $enlisted->standing . $enlisted->section;
        }

        $studentName = $student_info->firstname . ' ' . $student_info->surname;
        $studentNumber = $student_info->student_number;
        $college = $student_info->degree->college->collegeabbr;
        $degree = $student_info->degree->abbr;
        $scholarship = !is_null($enlisted->scholarship) ? $enlisted->scholarship->scholarship : '----';

        $resultDecode = $assessment->assess($student_record->id, $enlisted->pref_id);
        //dd($resultDecode);
        //$resultDecode = $assessment->getAssessment($student_record->id, $enlisted->pref_id);

        $assessSession = session('assessment');
        session(['assessment' => $resultDecode]);

        return compact(
            'resultDecode',
            'student_record',
            'orRecord',
            'enrollment',
            'student_info',
            'yearAndSection',
            'studentName',
            'studentNumber',
            'college',
            'degree',
            'scholarship',
            'pref_id',
            'enlisted'
        );
        // return response()->json([
        //     'resultDecode' => $resultDecode,
        //     'student_record' => $student_record,
        //     'orRecord' => $orRecord,
        //     'enrollment' => $enrollment,
        //     'student_info' => $student_info,
        //     'yearAndSection' => $yearAndSection,
        //     'studentName' => $studentName,
        //     'studentNumber' => $studentNumber,
        //     'college' => $college,
        //     'degree' => $degree,
        //     'scholarship' => $scholarship,
        //     'pref_id' => $pref_id,
        //     'enlisted' => $enlisted
        // ], 200);
    }

    public function pay(Request $request, Assessment $assessment)
    {
        //dd($pref);

        $mode = $request->mode;
        $srid = $request->srid;
        $pref = $request->pref;

        $resultDecode = session('assessment'); #dd($resultDecode);

        #$resultDecode = $assessment->assess($srid); #dd($resultDecode);

        $paymentFeesList = [];
        $feeAmt = 0;

        if ($mode == 'scholar') {
            $paymentFeesList = $resultDecode['paymentsList']['fullpaymentFeeList'];
            $feeAmt = $resultDecode['fullPayment'];
        } elseif ($mode == 'online') {
            /*
             * ONLINE PAYMENT
             */
            $paymentFeesList = $resultDecode['paymentsList']['fullpaymentFeeList']; #dd($paymentFeesList);
            $feeAmt = $resultDecode['fullPayment'];
        } elseif ($mode == 'full') {
            /*
             * FULL PAYMENT
             */
            $paymentFeesList = $resultDecode['paymentsList']['fullpaymentFeeList']; #dd($paymentFeesList);
            $feeAmt = $resultDecode['fullPayment'];
        } elseif ($mode == 'down') {
            /*
             * DOWN PAYMENT
             */
            $paymentFeesList = $resultDecode['paymentsList']['downpaymentFeeList'];
            $feeAmt = $resultDecode['downPayment'];
        } elseif ($mode == 'second') {
            /*
             * SECOND PAYMENT
             */
            $paymentFeesList = $resultDecode['paymentsList']['secondpaymentFeeList'];
            $feeAmt = $resultDecode['secondPayment'];
        } elseif ($mode == 'third') {
            /*
             * THIRD PAYMENT
             */
            $paymentFeesList = $resultDecode['paymentsList']['thirdpaymentFeeList'];
            $feeAmt = $resultDecode['thirdPayment'];
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

            $paymentFeesList = $feesList;
            $feeAmt = $feesList->sum('amount');
        }

        $myORLog = OrLog::where('user', Auth::user()->id)->first();

        $studentRecord = StudentRecord::find($srid);
        $student_info = $studentRecord->info;

        // $enlisted = $studentRecord->enlistment()->whereIn('pref_id', $this->pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
        // $enrollment = $studentRecord->enrollment()->where('pref_id', $this->pref)->orderBy('created_at', 'desc')->first();

        //update beniz
        // $enlisted = $studentRecord->enlistment()->where('pref_id', $pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
        // $enrollment = $studentRecord->enrollment()->where('pref_id', $pref)->orderBy('created_at', 'desc')->first();

        $paycontrol = PayControl::first();

        if ($paycontrol->control == 0) {

            if ($this->pref != [$pref]) {

                //return redirect('payment/enrollment')->with('err', 'Payment for this semester is not allowed!');
                return response()->json([
                    'status' => 0,
                    'message' => 'Payment for this semester is not allowed.'
                ], 200);
                //dd($enlisted);
            }

            $enlisted = $studentRecord->enlistment()->whereIn('pref_id', $this->pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
            $enrollment = $studentRecord->enrollment()->where('pref_id', $this->pref)->orderBy('created_at', 'desc')->first();
        } else {

            //dd([$pref]);

            $enlisted = $studentRecord->enlistment()->where('pref_id', $pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
            $enrollment = $studentRecord->enrollment()->where('pref_id', $pref)->orderBy('created_at', 'desc')->first();
        }

        //update beniz

        //dd($enlisted);

        $orRecord = !is_null($enrollment) ? $enrollment->orRecord : null; #dd($orRecord);

        if (!is_null($enrollment)) {
            $yearAndSection = $enrollment->standing . $enrollment->section;
        } elseif (!is_null($enlisted)) {
            $yearAndSection = $enlisted->standing . $enlisted->section;
        }

        $studentName = $student_info->firstname . ' ' . $student_info->surname;
        $studentNumber = $student_info->student_number;
        $college = $student_info->degree->college->collegeabbr;
        $degree = $student_info->degree->abbr;
        //$scholarship = !is_null($enlisted->scholarship) ? $enlisted->scholarship->scholarship : '----';
        //$scholarship = !is_null($enlisted->scholarship) ? $enlisted->scholarships->scholarship : '----';

        if ($enlisted->scholarships) {
            $scholarship =  $enlisted->scholarships->scholarship;
        } else {
            $scholarship = '----';
        }

        //    $mode_view = ($mode == 'online') ? 'enrollment.payment.pay-online' : 'enrollment.payment.pay';
        return compact(
            'paymentFeesList',
            'feeAmt',
            'myORLog',
            'srid',
            'mode',
            'enrollment',
            'orRecord',
            'yearAndSection',
            'studentName',
            'studentNumber',
            'college',
            'degree',
            'scholarship',
            'pref',
            'resultDecode'
        );
    }

    public function charge(Request $request, Assessment $assessment, NumberConverter $numberConverter)
    {


        $this->validate(
            $request,
            [
                'or' => ['required'],
                'cash' => ['required']
            ]
        );


        $resultDecode = session('assessment');

        //$amtPaid = $resultDecode['fullPayment'];
        //return $amtPaid;

        $mode = $request->mode;
        $or = $request->or;
        $pref = $request->pref;

        $createdat = $request->created_at;

        $date = date_create($createdat);
        $date_inputed = date_format($date, "Y-m-d H:i:s");

        //return $date_inputed;
        //return $date_inputed;

        //dd($pref);

        $valor = OrRecord::where('or_number', $or)->where('branch', Auth::user()->branch)->first();

        if (!is_null($valor)) {
            return response()->json([
                'status' => 0,
                'message' => 'OR NUMBER ALREADY EXIST!'
            ], 200);
            //return back()->with('err', 'OR NUMBER ALREADY EXIST!');
        }

        $student_record = StudentRecord::find($request->srid);
        $studentInfo = $student_record->info;

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

        if ($request->cash < $amtPaid) {
            return response()->json([
                'status' => 0,
                'message' => 'Amount Tendered not accepted!',
                'footer' => 'Check entries.!'
            ], 200);
        }
        #dd($balanceCode);

        //$enrollment = $student_record->enrollment()->whereIn('pref_id', $this->pref)->orderBy('created_at', 'desc')->first(); //dd($enrollment);
        //$enlisted = $student_record->enlistment()->where('pref_id', $pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
        $enrollment = $student_record->enrollment()->where('pref_id', $pref)->orderBy('created_at', 'desc')->first(); //dd($enrollment);


        if (is_null($enrollment)) {
            //$enlistment = $student_record->enlistment()->where('pref_id', $pref)->orderBy('created_at', 'desc')->first(); #dd($enlistment);
            $enlistment = $student_record->enlistment()->where('pref_id', $pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first(); #dd($enlistment);
            DB::beginTransaction();
            try {
                //transaction

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
                        $orRecord->currency = ($resultDecode['studentType'] == 3 ? 2 : 1);
                        $orRecord->created_at = $date_inputed;
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

                        //$this->or_charge = $orRecord;
                        $remark = new Remark();
                        $remark->enl_id = $enlistment->id;
                        $remark->activity = "Paid enrollment with the amount of " . $amtPaid .  ($resultDecode['studentType'] == 3 ? " dollars." : " pesos.");
                        $remark->actor = 1;
                        $remark->save();
                        //add logs

                        # Update User OR - [Increment]
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
                        # Update User OR - [Increment] End
                        //dd($amtPaid);
                        $t = explode(".", $amtPaid);
                        $c = count($t);
                        // $whole =  $t[0];
                        //return $c;

                        //$orRecord = $this->or_charge;

                        $currency = $orRecord->currency == 1 ? 'Pesos' : 'US Dollars';

                        if ($c > 1) {
                            $whole =  $t[0];
                            $decimal = (int) $t[1][0];

                            if ($decimal == 0) {
                                $converted = $numberConverter->toText($whole, $currency);
                                // $paying = $whole;
                            } else {
                                $converted = $numberConverter->toText($amtPaid, $currency);
                                //$paying = $amtPaid;
                            }
                        } else {
                            $converted = $numberConverter->toText($amtPaid, $currency);
                            // $paying = $amtPaid;
                        }
                    }
                }

                DB::commit();
            } //try
            catch (\Exception $e) {
                DB::rollBack();
                return $e->getMessage();
            }

            //add logs

            // $converted = $numberConverter->toText($amtPaid);

            $status = 1;

            return compact(
                'status',
                'or',
                'converted'
            );

            // return response()->json([
            //     'status' => 1,
            //     'message' => 'Success!'
            // ], 200);

            dd("Student enlistment not found.");
        }

        dd("Student already enrolled.");
    }

    public function print_charge(Request $request, Assessment $assessment, NumberConverter $numberConverter)
    {

        $resultDecode = session('assessment');

        //  return $resultDecode['paymentsList']['fullpaymentFeeList'];

        $mode = $request->mode;
        $or = $request->or;
        $converted = $request->converted;


        $student_record = StudentRecord::find($request->srid);
        $studentInfo = $student_record->info;

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

        // return $payList;

        return view('enrollment.payment.charge', compact(
            'or',
            'studentInfo',
            'student_record',
            'payList',
            'amtPaid',
            'converted'
        ));
    }

    public function scholarCharge(Request $request, Assessment $assessment)
    {
        $resultDecode = $assessment->assess($request->srid, $request->pref); #dd($resultDecode);
        // $resultDecode = $assessment->getAssessment($request->srid, $this->pref->id); #dd($resultDecode);

        $student_record = StudentRecord::find($request->srid);
        $studentInfo = $student_record->info;

        $amtPaid = $resultDecode['fullPayment'];
        $payType = 5;
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

                    //
                    DB::beginTransaction();
                    try {

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
                        $orRecord->or_number = 'scholar';
                        $orRecord->full = $resultDecode['fullPayment'];
                        $orRecord->down = $resultDecode['downPayment'];
                        $orRecord->second = $resultDecode['secondPayment'];
                        $orRecord->third = $resultDecode['thirdPayment'];
                        $orRecord->cash = $request->cash;
                        $orRecord->amount = $amtPaid;
                        $orRecord->balance = ($resultDecode['fullPayment'] - $amtPaid);
                        $orRecord->payment_type = $payType;
                        $orRecord->scholar_charge = 100;
                        $orRecord->scholarship = $enlistment->scholarship_id;
                        $orRecord->status = 1;
                        $orRecord->user = Auth::user()->id;
                        $orRecord->branch = Auth::user()->branch;
                        $orRecord->transaction_type = 1;
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

                        DB::commit();
                    } //try
                    catch (\Exception $e) {
                        DB::rollBack();
                        return $e->getMessage();
                    }
                    //

                    return response()->json([
                        'status' => 1,
                        'message' => 'Scholarship charged!'
                    ], 200);
                    //return redirect()->route('paymentEnrollmentIndex')->with('message', 'Scholarship charged');
                }
            }
            dd("Student enlistment not found.");
        }
        dd("Student already enrolled.");
    }

    public function onlinePaymentCharge(Request $request, Assessment $assessment)
    {


        $this->validate(
            $request,
            [

                'trans_ref_number' => ['required'],
                'srid' => ['required'],
                'pref' => ['required'],
                'created_at' => ['required']
            ],
            [
                'trans_ref_number.required' => 'Transaction Reference Number is required!',
                'created_at.required' => 'Date is required!',

            ]
        );

        $createdat = $request->created_at;

        $date = date_create($createdat);
        $date_inputed = date_format($date, "Y-m-d H:i:s");

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
            //
            DB::beginTransaction();
            try {

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
                        $orRecord->created_at =  $date_inputed;
                        $orRecord->updated_at = $date_inputed;
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

                        $remark = new Remark();
                        $remark->enl_id = $enlistment->id;
                        $remark->activity = "Paid enrollment via online with the amount of " . $amtPaid . " pesos.";
                        $remark->actor = 1;
                        $remark->save();
                    }
                }

                DB::commit();
            } //try
            catch (\Exception $e) {
                DB::rollBack();
            }

            return response()->json([
                'status' => 1,
                'message' => 'Payment has been saved. Student is now enrolled.'
            ], 200);
            //   return redirect()->route('paymentEnrollmentIndex')->with('message', 'Payment has been saved. Student is now enrolled.');
            //

            dd("Student enlistment not found.");
        }
        dd("Student already enrolled.");
    }
    public function lbp_charge(Request $request, Assessment $assessment, NumberConverter $numberConverter)
    {

        $this->validate(
            $request,
            [

                'cash' => ['required'],
                'or' => ['required'],
                'pref' => ['required'],
                'created_at' => ['required']
            ],
            [
                'created_at.required' => 'Date is required!',
                'cash.required' => 'Deposited is required!',

            ]
        );

        $resultDecode = session('assessment');

        $mode = $request->mode;
        $or = $request->or;
        $pref = $request->pref;
        $trans_ref = strtotime($request->created_at);

        $createdat = $request->created_at;
        $date = date_create($createdat);
        $date_depo = date_format($date, "Y-m-d H:i:s");
        //dd($request->cash);
        //  dd($trans_ref);
        //dd($resultDecode['fullPayment']);
        //dd($resultDecode['paymentsList']['fullpaymentFeeList']);

        //dd($pref);

        $balance = 0;
        $excess = 0;

        if (($resultDecode['fullPayment']) > $request->cash) {

            $balance = ($resultDecode['fullPayment']) - $request->cash;
        } else {
            $excess = $request->cash - ($resultDecode['fullPayment']);
        }

        $tmpBreakdown = collect($resultDecode['paymentsList']['fullpaymentFeeList']);

        $subTotal = 0;
        $newAmount = 0;

        foreach ($tmpBreakdown->sortBy('amount') as $value) {

            $subTotal += $value['amount'];
            if ($request->cash > $newAmount) {


                if ($request->cash > $subTotal) {

                    $payList[] = [
                        'fund_id' => $value['fund_id'],
                        'fund' => $value['fund'],
                        'fund_desc' => $value['fund_desc'],
                        'amount' => $value['amount']
                    ];
                } else {
                    $newAmount = $subTotal - $request->cash;
                    $payList[] = [
                        'fund_id' => $value['fund_id'],
                        'fund' => $value['fund'],
                        'fund_desc' => $value['fund_desc'],
                        'amount' => $value['amount'] - $newAmount
                    ];
                }
            }
        }

        // return [$data, $subTotal, $newAmount];

        $student_record = StudentRecord::find($request->srid);
        $studentInfo = $student_record->info;

        if (is_null($student_record->college_id)) {
            $degreeDetail = $student_record->degree;

            $student_record->college_id = $degreeDetail->college_id;
            $student_record->save();
        }

        #$resultDecode = $assessment->assess($request->srid); #dd($resultDecode);

        #dd($balanceCode);

        //$enrollment = $student_record->enrollment()->whereIn('pref_id', $this->pref)->orderBy('created_at', 'desc')->first(); //dd($enrollment);
        $enrollment = $student_record->enrollment()->where('pref_id', $pref)->orderBy('created_at', 'desc')->first(); //dd($enrollment);



        if (is_null($enrollment)) {
            //$enlistment = $student_record->enlistment()->whereIn('pref_id', $this->pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first(); #dd($enlistment);
            $enlistment = $student_record->enlistment()->where('pref_id', $pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first(); #dd($enlistment);

            if (!is_null($enlistment)) {

                $enlistmentDetails = $enlistment->details;

                if ($enlistmentDetails->count() > 0) {

                    //
                    DB::beginTransaction();
                    try {

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
                        $orRecord->amount = $request->cash;
                        $orRecord->balance = $balance;
                        $orRecord->balance_code  = ($request->cash >= $resultDecode['fullPayment']) ? null : 4;
                        $orRecord->payment_type = ($request->cash >= $resultDecode['fullPayment']) ? 1 : 2;
                        $orRecord->status = 1;
                        $orRecord->user = Auth::user()->id;
                        $orRecord->branch = Auth::user()->branch;
                        $orRecord->transaction_type = 1;
                        $orRecord->trans_ref_number =  $trans_ref;
                        $orRecord->created_at =  $date_depo;
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

                        if ($excess > 0) {
                            DB::table('excess_payment')->insert([
                                'or_record_id' => $orRecord->id,
                                'excess' => $excess
                            ]);
                        }
                        # Update User OR - [Increment]
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


                        $amtPaid = $request->cash;

                        $currency = 'Pesos';

                        $converted = $numberConverter->toText($amtPaid, $currency);

                        DB::commit();

                        //return redirect('payment/enrollment')->with('message', 'Payment has been saved. Student is now enrolled.');
                    } //try
                    catch (\Exception $e) {
                        DB::rollBack();
                        return $e->getMessage();
                    }
                    //

                    $or_id = $orRecord->id;
                    $message = 'Payment has been saved. Student is now enrolled.';
                    $status = 1;
                    return compact(
                        'status',
                        'message',
                        'or',
                        'or_id',
                        //'payList',
                        'amtPaid',
                        'converted'
                    );
                }
            }
            dd("Student enlistment not found.");
        }

        dd("Student already enrolled.");
    }

    public function print_charge_lbppayment(Request $request, Assessment $assessment, NumberConverter $numberConverter)
    {

        $resultDecode = session('assessment');

        //  return $resultDecode['paymentsList']['fullpaymentFeeList'];

        $mode = $request->mode;
        $amtPaid = $request->amt_paid;
        $or_id = $request->or_id;
        $or = $request->or;
        $converted = $request->converted;


        $student_record = StudentRecord::find($request->srid);
        $studentInfo = $student_record->info;

        $payList = Payment::where('or_id', $request->or_id)
            ->join('fund', 'fund.fund_id', '=', 'payments.fund')
            ->select(
                'payments.amount',
                'fund.fund',
                'fund.fund_desc'
            )->get();


        // return $payList;

        return view('enrollment.payment.charge', compact(
            'or',
            'studentInfo',
            'student_record',
            'amtPaid',
            'converted',
            'payList'
        ));
    }
}
