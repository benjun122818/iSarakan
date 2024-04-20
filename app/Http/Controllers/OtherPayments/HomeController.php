<?php

namespace App\Http\Controllers\OtherPayments;

use App\Preference;
use App\Collection;
use App\Repositories\Assessment;
use App\Repositories\EnrBalance;
use App\Sresu;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    protected $pref;

    public function __construct()
    {
        $this->pref = Preference::where('enlistment', 1)->get()->pluck('id')->all();
    }

    public function index()
    {
        $preferences = [];

        foreach (Preference::where('id', '>=', 17)->get() as $key => $pref) {
            $sem = "";
            if ($pref->sem == 1) {
                $sem = "{$pref->cys->cy} | First Semester";
            } else if ($pref->sem == 2) {
                $sem = "{$pref->cys->cy} | Second Semester";
            } else if ($pref->sem == 3) {
                $sem = "{$pref->cys->cy} | Mid-year";
            }
            $preferences[$pref->id] = $sem;
        }

        return view('other-payments.home', compact('preferences'));
    }

    public function indexlbppayment()
    {
        $preferences = [];

        foreach (Preference::where('id', '>=', 17)->get() as $key => $pref) {
            $sem = "";
            if ($pref->sem == 1) {
                $sem = "{$pref->cys->cy} | First Semester";
            } else if ($pref->sem == 2) {
                $sem = "{$pref->cys->cy} | Second Semester";
            } else if ($pref->sem == 3) {
                $sem = "{$pref->cys->cy} | Mid-year";
            }
            $preferences[$pref->id] = $sem;
        }

        return view('other-payments.lbppayment.home', compact('preferences'));
    }

    public function documentIndex()
    {

        // $c = DB::connection('otherpayment')->table("collections")->leftJoin('dbiusis16.fees', 'fees.fund_id', '=', 'collections.id')
        //     ->select([
        //         'collections.*',
        //         'fees.amount'
        //     ])->get();

        // $c = Collection::leftJoin('dbiusis16.fees', 'fees.fund_id', '=', 'collections.id')
        //     ->select([
        //         'collections.*',
        //         'fees.amount'
        //     ])->get();

        return view('other-payments.others.otherFundPayment');
    }

    public function balances(Request $request, Assessment $assess, EnrBalance $balances)
    {

        $pref_id = $request->pref;
        $student = Sresu::where('student_number', $request->studentNumber)->first();
        //return $student;

        if (is_null($student)) {
            return back()->with('err', 'Student number not found');
        }

        $enrollment = $student->record->enrollment()->where('pref_id', $pref_id)->first();
        // return  $enrollment;
        if (is_null($enrollment)) {
            return back()->with('err', 'Student is not enrolled for the selected semester.');
        }

        $studentRecord = $student->record;
        $orRecord = $student->orRecords()->where('pref_id', $pref_id)->where('transaction_type', 1)->where('status', 1)->latest('id')->first();
        //return  $orRecord;
        if (is_null($orRecord)) {
            return back()->with('err', 'No record found for the selected semester. Please select the last semester where the student has enrolled.');
        }

        // $studentAssessment = $assess->getAssessment($studentRecord->id, $pref_id);
        $studentAssessment = $assess->assess($studentRecord->id, $pref_id);
        //dd($studentAssessment);


        $fullAmt = $studentAssessment['fullPayment'];
        $downAmt = $studentAssessment['downPayment'];
        $secondAmt = $studentAssessment['secondPayment'];
        $thirdAmt = $studentAssessment['thirdPayment'];
        $downAndSecondAmt = $downAmt + $secondAmt;
        $downAndThirdAmt = $downAmt + $thirdAmt;
        $secondAndThirdAmt = $secondAmt + $thirdAmt;

        $iPaid = null;

        //dd($orRecord->amount, $downAmt);

        if (is_null($orRecord->balance_code)) {
            if ($orRecord->amount == $fullAmt) {
                $iPaid = 'full';
            } elseif ($orRecord->amount == $downAmt) {
                $iPaid = 'down';
                $orRecord->balance_code = 23;
                $orRecord->save();
                /*
                $secondPayFeesList = $studentAssessment['paymentsList']['secondpaymentFeeList'];
                $thirdPayFeesList = $studentAssessment['paymentsList']['thirdpaymentFeeList'];

                $arrFList = [];
                foreach ($secondPayFeesList as $list)
                {
                    $arrFList[$list->fund_id] = ['fund_id'=>$list->fund_id, 'fund'=>$list->fund, 'fund_desc'=>$list->fund_desc, 'amount'=>$list->amount];
                }

                foreach ($thirdPayFeesList as $list)
                {
                    if (array_key_exists($list->fund_id, $arrFList))
                    {
                        $secondAmount = $arrFList[$list->fund_id]['amount'];
                        $thirdAmount = $list->amount;
                        $newAmount = $secondAmount + $thirdAmount;

                        $arrFList[$list->fund_id] = ['fund_id'=>$list->fund_id, 'fund'=>$list->fund, 'fund_desc'=>$list->fund_desc, 'amount'=>$newAmount];
                    }
                    else
                    {
                        $arrFList[$list->fund_id] = ['fund_id'=>$list->fund_id, 'fund'=>$list->fund, 'fund_desc'=>$list->fund_desc, 'amount'=>$list->amount];
                    }
                }


                $feesList = collect([]);
                foreach ($arrFList as $adjustedFeesList)
                {
                    $arr=[
                        'fund_id' => $adjustedFeesList['fund_id'],
                        'fund' => $adjustedFeesList['fund'],
                        'fund_desc' => $adjustedFeesList['fund_desc'],
                        'amount' => $adjustedFeesList['amount']
                    ];
                    $feesList->push((object) $arr);
                }

                $balanceAmt = $feesList->sum('amount');
                $balancePayList = $feesList;
                */
            } elseif ($orRecord->amount == $secondAmt) {
                $iPaid = 'second';
                $orRecord->balance_code = 13;
                $orRecord->save();
            } elseif ($orRecord->amount == $thirdAmt) {
                $iPaid = 'second';
                $orRecord->balance_code = 12;
                $orRecord->save();
            } elseif ($orRecord->amount == $downAndSecondAmt) {
                $iPaid = 'downAndSecond';
                $orRecord->balance_code = 3;
                $orRecord->save();
            } elseif ($orRecord->amount == $downAndThirdAmt) {
                $iPaid = 'downAndSecond';
                $orRecord->balance_code = 2;
                $orRecord->save();
            } elseif ($orRecord->amount == $secondAndThirdAmt) {
                $iPaid = 'downAndSecond';
                $orRecord->balance_code = 1;
                $orRecord->save();
            }
        }


        $balanceCodes = str_split($orRecord->balance_code);

        $enrBalances = [];
        $arrFList = [];

        //return $orRecord->balance_code;


        if ($orRecord->balance_code > 0) {

            foreach ($balanceCodes as $bCode) {
                if ($bCode == 1) {
                    $feeList = $studentAssessment['paymentsList']['downpaymentFeeList'];
                } elseif ($bCode == 2) {
                    $feeList = $studentAssessment['paymentsList']['secondpaymentFeeList'];
                } elseif ($bCode == 3) {
                    $feeList = $studentAssessment['paymentsList']['thirdpaymentFeeList'];
                } elseif ($bCode == 4) {
                    $tmpBreakdown = collect($studentAssessment['paymentsList']['fullpaymentFeeList']);

                    $subTotal = 0;
                    $newAmount = 0;
                    // return $request->cash;
                    // return $tmpBreakdown->sortBy('amount');
                    foreach ($tmpBreakdown->sortBy('amount') as $value) {
                        $subTotal += $value['amount'];
                        //   dd($subTotal);
                        if ($orRecord->amount > $newAmount) {

                            if ($orRecord->balances > $subTotal) {
                                $payList[] = [
                                    'fund_id' => $value['fund_id'],
                                    'fund' => $value['fund'],
                                    'fund_desc' => $value['fund_desc'],
                                    'amount' => $value['amount']
                                ];
                            } else {
                                //    dd($value['amount']);
                                $newAmount =  $value['amount'] -  $orRecord->amount;

                                if ($newAmount > $orRecord->amount) {
                                    $payList[] = [
                                        'fund_id' => $value['fund_id'],
                                        'fund' => $value['fund'],
                                        'fund_desc' => $value['fund_desc'],
                                        'amount' => $newAmount
                                    ];
                                    $feeList[] = [
                                        'fund_id' => $value['fund_id'],
                                        'fund' => $value['fund'],
                                        'fund_desc' => $value['fund_desc'],
                                        'amount' => $newAmount
                                    ];
                                } else {
                                    $payList[] = [
                                        'fund_id' => $value['fund_id'],
                                        'fund' => $value['fund'],
                                        'fund_desc' => $value['fund_desc'],
                                        'amount' => $value['amount'] - $newAmount
                                    ];
                                    $feeList[] = [
                                        'fund_id' => $value['fund_id'],
                                        'fund' => $value['fund'],
                                        'fund_desc' => $value['fund_desc'],
                                        'amount' => ($value['amount'] >= $orRecord->balance) ? $orRecord->balance : $value['amount']
                                    ];
                                }
                            }
                        }
                    }
                }

                //return $orRecord->amount;
                // return $payListBal;

                // $fee644 = 0;
                // foreach ($studentAssessment['paymentsList']['fullpaymentFeeList'] as $lbpChecker) {
                //     if ($lbpChecker['fund'] == '644') {
                //         $fee644 = $lbpChecker['amount'];
                //     }
                // }

                // $feeList[0]['amount'] = 1234;
                $enrBalances[$bCode] = $feeList;

                //   dd($feeList);
                foreach ($feeList as $fee) {
                    $fee = (object) $fee;



                    if (array_key_exists($fee->fund_id, $arrFList)) {
                        $newAmount = $fee->amount + $arrFList[$fee->fund_id]['amount'];

                        $arrFList[$fee->fund_id] = ['fund_id' => $fee->fund_id, 'fund' => $fee->fund, 'fund_desc' => $fee->fund_desc, 'amount' => $newAmount];
                    } else {

                        $arrFList[$fee->fund_id] = ['fund_id' => $fee->fund_id, 'fund' => $fee->fund, 'fund_desc' => $fee->fund_desc, 'amount' => $fee->amount];
                    }
                }
            }
        }


        $balanceList = [
            'balanceCodes' => $balanceCodes,
            'studentRecId' => $studentRecord->id,
            'feesList' => $enrBalances
        ];

        // dd($enrBalances);
        session(['balance' => $balanceList]);

        #dd($balanceList);
        #dd($studentAssessment);

        return view('other-payments.balances', compact(
            'studentAssessment',
            'iPaid',
            'balanceCodes',
            'balanceList',
            'student',
            'pref_id'
        ));
    }
}
