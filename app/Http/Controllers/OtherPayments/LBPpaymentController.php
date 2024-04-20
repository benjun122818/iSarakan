<?php

namespace App\Http\Controllers\OtherPayments;

use App\Http\Controllers\Controller;
use App\Course;
use App\EnlistmentDetail;
use App\Enrollment;
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
use App\PayControl;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use NumberToWords\NumberToWords;
use Illuminate\Http\Request;

class LBPpaymentController extends Controller
{
    public function pay($mode, $srid, $pref, Assessment $assessment)
    {
        //dd($pref);
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



        $enlisted = $studentRecord->enlistment()->where('pref_id', $pref)->where('enlistment_status', 2)->orderBy('created_at', 'desc')->first();
        $enrollment = $studentRecord->enrollment()->where('pref_id', $pref)->orderBy('created_at', 'desc')->first();

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

        $mode_view = ($mode == 'online') ? 'enrollment.payment.lbp-pay' : 'enrollment.payment.pay';
        return view($mode_view, compact(
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
            'pref'
        ));
    }

    public function charge(Request $request, Assessment $assessment, NumberConverter $numberConverter)
    {
        $resultDecode = session('assessment');

        $mode = $request->mode;
        $or = $request->or;
        $pref = $request->pref;
        $trans_ref = strtotime($request->created_at);
        //dd($request->cash);
        //  dd($trans_ref);
        //dd($resultDecode['fullPayment']);
        //dd($resultDecode['paymentsList']['fullpaymentFeeList']);

        //dd($pref);

        $balance = 0;

        if (($resultDecode['fullPayment']) > $request->cash) {

            $balance = ($resultDecode['fullPayment']) - $request->cash;
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
                    $orRecord->or_number = 'LBP Deposit';
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
                    $amtPaid = $request->cash;

                    $currency = 'Pesos';

                    $converted = $numberConverter->toText($amtPaid, $currency);

                    return redirect('payment/enrollment')->with('message', 'Succesfully registered!');

                    // return view('enrollment.payment.charge', compact(
                    //     'or',
                    //     'studentInfo',
                    //     'student_record',
                    //     'payList',
                    //     'amtPaid',
                    //     'converted'
                    // ));
                }
            }
            dd("Student enlistment not found.");
        }

        dd("Student already enrolled.");
    }
}
