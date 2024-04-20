<?php

namespace App\Http\Controllers\OtherPayments;

use App\OrLog;
use App\OrRecord;
use App\Payment;
use App\Preference;
use App\Repositories\NumberConverter;
use App\StudentRecord;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    // protected $pref;
    //
    // public function __construct()
    // {
    //     $this->pref = Preference::where('enlistment', 1)->first();
    // }

    public function summary($code, $pref_id)
    {
        $sBalance = session('balance');

        $studentRecord = StudentRecord::find($sBalance['studentRecId']);
        $orRecord = $studentRecord->orRecords()->where('pref_id', $pref_id)->where('transaction_type', 1)->latest('id')->first();

        //dd($orRecord);

        $remBalance = null;

        if ($code == 'all') {
            $arrFList = [];
            $balanceDetails = collect();

            foreach ($sBalance['feesList'] as $fees) {
                foreach ($fees as $fee) {
                    $fee = (object) $fee;
                    if (array_key_exists($fee->fund_id, $arrFList)) {
                        $newAmount = $fee->amount + $arrFList[$fee->fund_id]['amount'];
                        $arrFList[$fee->fund_id] = ['fund_id' => $fee->fund_id, 'fund' => $fee->fund, 'fund_desc' => $fee->fund_desc, 'amount' => $newAmount];
                    } else {
                        $arrFList[$fee->fund_id] = ['fund_id' => $fee->fund_id, 'fund' => $fee->fund, 'fund_desc' => $fee->fund_desc, 'amount' => $fee->amount];
                    }
                }
            }

            foreach ($arrFList as $flist) {
                $balanceDetails->push((object) $flist);
            }
        } else {
            $balanceDetails = $sBalance['feesList'][$code];

            $remBalance = str_split($orRecord->balance_code);
            if (($key = array_search($code, $remBalance)) !== false) {
                unset($remBalance[$key]);
            }
        }

        session(['feesList' => ['balanceDet' => $balanceDetails, 'studentRecord' => $studentRecord, 'remBalance' => $remBalance]]);

        $feeAmt = 0;
        foreach ($balanceDetails as $myBalance) {
            $myBalance = (object) $myBalance;
            $feeAmt = $feeAmt + $myBalance->amount;
        }

        $studentNumber = $studentRecord->sresu->student_number;
        $college = $studentRecord->college->college;
        $studentName = $studentRecord->info->firstname . ' ' . $studentRecord->info->surname;
        $degree = $studentRecord->degree->abbr;

        $enrollment = $studentRecord->enrollment()->with(['orRecords'])->where('pref_id', $pref_id)->first();
        $yearAndSection = $enrollment->standing . $enrollment->section;

        $myORLog = OrLog::where('user', Auth::user()->id)->first();

        //return $orRecord;
        return view('other-payments.balance.summary', compact(
            'studentNumber',
            'college',
            'studentName',
            'degree',
            'yearAndSection',
            'balanceDetails',
            'myORLog',
            'feeAmt',
            'pref_id',
            'orRecord'
        ));
    }

    public function charge(Request $request, NumberConverter $numberConverter)
    {
        $pref_id = $request->pref_id;
        $sFees = session('feesList');

        $student_record = $sFees['studentRecord'];
        $studentInfo = $student_record->info;

        $enrollment = $student_record->enrollment()->with(['orRecords'])->where('pref_id', $pref_id)->first();

        $balanceDet = $sFees['balanceDet'];
        $totalDue = 0;
        foreach ($balanceDet as $balance) {
            $balance = (object) $balance;
            $totalDue = $totalDue + $balance->amount;
        }

        //dd($numberConverter->toText(3646.081500));

        $remainingBalance = 0;
        if (!is_null($sFees['remBalance'])) {
            foreach ($sFees['remBalance'] as $balanceCode) {
                $remainingBalance .= $balanceCode;
            }
        }

        // CREATE OR RECORD
        $or = $request->or;

        $orRecord = new OrRecord();
        $orRecord->enrollment_id = $enrollment->id;
        $orRecord->student_id = $student_record->student_id;
        $orRecord->pref_id = $pref_id;
        $orRecord->or_number = $or;
        $orRecord->full = $totalDue;
        $orRecord->cash = $request->cash;
        $orRecord->amount = $totalDue;
        $orRecord->balance = 0;
        $orRecord->balance_code = $remainingBalance;
        $orRecord->payment_type = 0;
        $orRecord->status = 1;
        $orRecord->user = Auth::user()->id;
        $orRecord->branch = Auth::user()->branch;
        $orRecord->transaction_type = 1;
        $orRecord->currency = $request->currency;
        $orRecord->save();

        foreach ($balanceDet as $payment) {
            $payment = (object) $payment;

            $payments = new Payment();
            $payments->or_id = $orRecord->id;
            $payments->fund = $payment->fund_id;
            $payments->amount = $payment->amount;
            $payments->college = $student_record->college_id;
            $payments->degree = $student_record->degree_id;
            $payments->save();
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
        # Update User OR - [Increment] End

        $currency = $orRecord->currency == 1 ? 'Pesos' : 'US Dollars';

        $converted = $numberConverter->toText($totalDue,  $currency);

        $payList = $balanceDet;
        $amtPaid = $totalDue;

        return view('other-payments.balance.charge', compact(
            'or',
            'studentInfo',
            'student_record',
            'payList',
            'amtPaid',
            'converted'
        ));
    }
}
