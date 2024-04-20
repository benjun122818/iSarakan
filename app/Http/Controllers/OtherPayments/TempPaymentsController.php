<?php

namespace App\Http\Controllers\OtherPayments;

use App\Fund;
use App\OrLog;
use App\OrRecord;
use App\Payment;
use App\Preference;
use App\Repositories\NumberConverter;
use App\Sresu;
use App\StudentRecord;
use App\TempPayment;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TempPaymentsController extends Controller
{
    protected $pref;

    public function __construct()
    {
        $this->pref = Preference::where('enlistment', 1)->first();
    }

    public function newList($uid)
    {
        $tempPayments = TempPayment::with(['myFund'])->where('student_id', $uid)->latest()->get();

        return view('other-payments.temp.new', compact(
            'uid',
            'tempPayments'
        ));
    }

    public function store(Request $request, $uid)
    {
        $fundDetails = explode(' :: ', $request->fund);

        $fundCode = $fundDetails[0];
        $fundDesc = $fundDetails[1];
        $fundId = $fundDetails[2];

        $fund = Fund::with(['fees'])->find($fundId);
        $tempPayments = TempPayment::where('student_id', $uid)->where('fund', $fundId)->first();
        $student = Sresu::with('record')->find($uid);

        if (is_null($tempPayments))
        {
            $tempPayment = new TempPayment();
            $tempPayment->student_id = $uid;
            $tempPayment->fund = $fund->fund_id;
            $tempPayment->amount = $fund->fees->amount;
            $tempPayment->college = $student->record->college_id;
            $tempPayment->degree = $student->record->degree_id;
            $tempPayment->save();

            return back()->with('message', 'Fund added to list.');
        }
        else
        {
            return back()->with('err', 'Fund already in the list');
        }
    }

    public function destroy($id)
    {
        $tempPayment = TempPayment::find($id);

        $tempPayment->delete();

        return back()->with('message', 'Successfully removed');
    }

    public function summary($uid)
    {
        $tempPayments = TempPayment::with(['myFund'])->where('student_id', $uid)->latest()->get();

        $studentRecord = StudentRecord::where('student_id', $uid)->first();

        $studentNumber = $studentRecord->sresu->student_number;
        $college = $studentRecord->college->college;
        $studentName = $studentRecord->info->firstname.' '.$studentRecord->info->surname;
        $degree = $studentRecord->degree->abbr;

        $enrollment = $studentRecord->enrollment()->with(['orRecords'])->where('pref_id', $this->pref->id)->first();
        $yearAndSection = $enrollment->standing.$enrollment->section;

        $myORLog = OrLog::where('user', Auth::user()->id)->first();

        $feeAmt = $tempPayments->sum('amount');

        return view('other-payments.temp.summary', compact(
            'tempPayments',
            'studentNumber',
            'college',
            'studentName',
            'degree',
            'yearAndSection',
            'myORLog',
            'feeAmt',
            'uid'
        ));
    }

    public function charge(Request $request, $uid, NumberConverter $numberConverter)
    {
        $tempPayments = TempPayment::with(['myFund'])->where('student_id', $uid)->latest()->get();

        $student_record = StudentRecord::where('student_id', $uid)->first();
        $studentInfo = $student_record->info;

        $enrollment = $student_record->enrollment()->with(['orRecords'])->where('pref_id', $this->pref->id)->first();

        $totalDue = $tempPayments->sum('amount');

        // CREATE OR RECORD
        $or = $request->or;

        $orRecord = new OrRecord();
        $orRecord->enrollment_id = $enrollment->id;
        $orRecord->student_id = $student_record->student_id;
        $orRecord->pref_id = $this->pref->id;
        $orRecord->or_number = $or;
        $orRecord->full = $totalDue;
        $orRecord->cash = $request->cash;
        $orRecord->amount = $totalDue;
        $orRecord->balance = 0;
        $orRecord->balance_code = 0;
        $orRecord->payment_type = 0;
        $orRecord->status = 1;
        $orRecord->user = Auth::user()->id;
        $orRecord->branch = Auth::user()->branch;
        $orRecord->transaction_type = 0;
        $orRecord->save();

        foreach ($tempPayments as $tempPayment)
        {
            $payments = new Payment();
            $payments->or_id = $orRecord->id;
            $payments->fund = $tempPayment->fund;
            $payments->amount = $tempPayment->amount;
            $payments->college = $student_record->college_id;
            $payments->degree = $student_record->degree_id;
            $payments->save();
        }

        # Update User OR - [Increment]
        $orLog = OrLog::where('user', Auth::user()->id)->first();
        if (!is_null($orLog))
        {
            $orLog->last_or = $or + 1;
            $orLog->save();
        }
        else
        {
            $orLog = new OrLog();
            $orLog->last_or = $or + 1;
            $orLog->user = Auth::user()->id;
            $orLog->save();
        }
        # Update User OR - [Increment] End

        $converted = $numberConverter->toText($totalDue);

        $payList = $tempPayments;
        $amtPaid = $totalDue;

        return view('other-payments.temp.charge', compact(
            'or',
            'studentInfo',
            'student_record',
            'payList',
            'amtPaid',
            'converted'
        ));
    }
}
