<?php

namespace App\Http\Controllers\Enrollment;

use App\Fund;
use App\Http\Controllers\Controller;
use App\OrRecord;
use App\NonEnrolmentFees;
use App\Payment;
use App\Preference;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\Assessment;
use App\Exports\CashierReportExport;
use App\Exports\CashierCashReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    protected $pref;

    public function __construct()
    {
        $this->pref = Preference::where('enlistment', 1)->first();
    }

    public function index()
    {
        $totalSemesterTransactions = OrRecord::where(function ($query) {
            $query->where('pref_id', $this->pref->id);
            $query->whereBranch(Auth::user()->branch);
        })->select(DB::raw('sum(amount) as total'))->first();
        #dd($totalTransactionsToday);

        $totalTransactionsTodayget = OrRecord::where(function ($query) {
            $query->where('pref_id', $this->pref->id);
            $query->whereUser(Auth::user()->id);
        })->whereBetween('created_at', [Carbon::today() . ' 00.00.00', Carbon::today() . ' 23.59.59'])
            ->select(DB::raw('sum(amount) as total'))->first();

        $nonEnrolmentfund = NonEnrolmentFees::where('user_id', Auth::user()->id)->whereDate('created_at', Carbon::today())
            ->groupBy('or_number')->get(['*', \DB::raw('"NonEnrollment" as source')]);

        $totalcollection = $nonEnrolmentfund->sum('amount');
        //dd($totalcollection);


        $totalTransactionsToday = $totalTransactionsTodayget->total + $totalcollection;

        return view('enrollment.reports.index', compact(
            'totalSemesterTransactions',
            'totalTransactionsToday'
        ));
    }

    public function byDate(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);
        ini_set('max_execution_time', 7200);

        $from = null;
        $to = null;
        $totalCollection = null;

        $mode = is_null($request->mode) ? null : $request->mode;

        $orRecords = collect([]);

        $orRecordsget = collect();

        $non_enrollment = collect();

        if (!empty($request->all())) {
            $from = $request->rFrom;
            $to = $request->rTo;

            $or_ids = [];

            // $p = Payment::join('fund', 'fund.fund_id', '=', 'payments.fund')
            //     ->whereBetween('payments.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            //     ->where('fund.dollar', 0)
            //     ->select(
            //         'payments.*',
            //         'fund.dollar'
            //     )->groupBy('or_id')->get();
            //return $p;

            // foreach ($p as $x) {
            //     array_push($or_ids, $x->or_id);
            // }

            //return $or_ids;

            // $orRecordsget = OrRecord::whereIn('id', $or_ids)->with(['payments', 'studentInfo'])
            //     ->where('scholar_charge', '!=', 100)
            //     //->where('status', 1)
            //     ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

            $orRecordsget = OrRecord::with(['payments', 'studentInfo'])
                ->where('scholar_charge', '!=', 100)
                //->where('status', 1)
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);



            $non_enrollment = NonEnrolmentFees::with('studentInfo')->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);


            if ($mode == 1) {
                $orRecordsget = $orRecordsget->where('user', Auth::user()->id);
                //$non_enrollment = $non_enrollment->where('user_id', Auth::user()->id);
            } else {
                $orRecordsget = $orRecordsget->where('branch', Auth::user()->branch);
                //$non_enrollment = $non_enrollment->where('branch', Auth::user()->branch);
            }

            // $non_enrollment = $non_enrollment->orderBy('or_number', 'desc')->get(['*', \DB::raw('"1" as source')]);
            //return $non_enrollment;

            $orRecordsget = $orRecordsget->orderBy('or_number', 'desc')->get(['*', \DB::raw('"0" as source')]);
            //return $orRecordsget;

            // foreach ($orRecordsget as $a) {
            //     $orRecords = $orRecords->push($a);
            // }

            foreach ($orRecordsget as $a) {
                $orRecords = $orRecords->push($a);
            }

            // foreach ($non_enrollment as $b) {
            //     $orRecords = $orRecords->push($b);
            // }

            //return $orRecords;
        }

        $getamount = [];
        //return $orRecords->paymentsre()->first();
        foreach ($orRecords as $orRecord) {
            //$cash = $orRecord->sum('amount');//number_format($orRecord->amount, 2);
            if ($orRecord->status == 0) {
                $cash = '0.00'; //number_format($orRecord->amount, 2);
            } else {
                $cash = $orRecord->amount;
            }
            // $totalcash = number_format($cash, 2);
            array_push($getamount, $cash);
        }
        $totalcash = number_format(array_sum($getamount), 2);


        $reportCtr = 1;

        return view('enrollment.reports.byDate', compact(
            'orRecords',
            'from',
            'to',
            'reportCtr',
            'mode',
            'totalCollection',
            'totalcash'
        ));
    }

    public function byDatePrint(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);
        ini_set('max_execution_time', 7200);

        $from = $request->fr;
        $to = $request->to;

        $non_enrollment = collect();
        $orRecords = collect([]);

        $orRecords1 = OrRecord::with(['payments', 'studentInfo'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        $non_enrollment = NonEnrolmentFees::with(['payments', 'studentInfo'])->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        if ($request->mode == 1) {
            $orRecords1 = $orRecords1->where('user', Auth::user()->id);
            //$non_enrollment = $non_enrollment->where('user_id', Auth::user()->id);
        } else {
            $orRecords1 = $orRecords1->where('branch', Auth::user()->branch);
            //$non_enrollment = $non_enrollment->where('branch', Auth::user()->branch);
        }

        $orRecords1 = $orRecords1->orderBy('or_number', 'desc')->get(['*', \DB::raw('"0" as source')]);
        //$non_enrollment = $non_enrollment->orderBy('or_number', 'desc')->get(['*', \DB::raw('"1" as source')]);

        foreach ($orRecords1 as $a) {

            $orRecords = $orRecords->push($a);
        }

        // foreach ($non_enrollment as $b) {

        //     $orRecords = $orRecords->push($b);
        // }

        //return($non_enrollment);    
        //return($orRecords);
        //dd($orRecords);

        $file = 'cashier-reports-byDate-' . Auth::user()->id;

        $funds = collect([]);

        $data = [];

        # Excel Headers
        $headers = [];
        array_push($headers, 'Date', 'OR Number', 'Student Number', 'Name');

        // $funds1 = Fund::whereHas('payments', function($query) use ($from, $to){
        //     $query->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        // })->orderBy('fund', 'asc')->get();

        //query testing

        $funds1 = Fund::whereRaw("fund.fund_id in (select fund from payments where created_at between '$from. 00:00:00' AND '$to.23:59:59')")
            ->orderBy('fund', 'asc')->get();

        // $funds2 = Fund::whereRaw("fund.fund_id in (select fund_id from payments_none where created_at between '$from. 00:00:00' AND '$to.23:59:59')")
        //     ->orderBy('fund', 'asc')->get();

        // $funds2 = Fund::whereHas('paymentsnon', function($query) use ($from, $to){
        //      $query->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        //  })->orderBy('fund', 'asc')->get();

        //dd($funds2);
        //return  $funds1;

        //end query testing

        // $funds2 = Fund::whereHas('paymentsnon', function($query) use ($from, $to){
        //     $query->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        // })->orderBy('fund', 'asc')->get();

        foreach ($funds1 as $a) {

            $funds = $funds->push($a);
        }

        // foreach ($funds2 as $b) {

        //     $funds = $funds->push($b);
        // }


        //$funds = $funds->push($funds1);

        $fees_stack = [];
        foreach ($funds as $fund) {
            array_push($fees_stack, $fund->fund_id);
            array_push($headers, $fund->fund . '|' . $fund->fund_id);
        }
        array_push($headers, 'Source', 'Status', 'Total');
        // array_push($data, $headers);
        # Excel Header End

        //dd($data);

        #Excel Data
        foreach ($orRecords as $orRecord) {
            $rowRecord = [];

            $or_number = in_array($orRecord->or_number, ['online', 'ONLINE']) ? $orRecord->trans_ref_number : $orRecord->or_number;

            array_push(
                $rowRecord,
                Carbon::parse($orRecord->created_at)->format('Y-m-d'),
                strtoupper($or_number),
                $orRecord->studentInfo == '' ? '--Not found--' : $orRecord->studentInfo->student_number,
                $orRecord->studentInfo == '' ? $orRecord->payor_name : $orRecord->studentInfo->surname . ', ' . $orRecord->studentInfo->firstname
            );
            //dd($orRecord);
            //$source = $orRecord->source;
            //return ($source);
            //return ($orRecord);
            $payments = $orRecord->payments;
            $paymentArr = [];
            foreach ($payments as $paid) {
                //$paymentArr[$paid->fund] = $paid->amount;
                $paymentArr[$orRecord->source == 0 ? $paid->fund : $paid->fund_id] = $paid->amount;
            }

            foreach ($fees_stack as $feeID) {
                if (array_key_exists($feeID, $paymentArr)) {
                    if ($orRecord->scholar_charge == 100 || $orRecord->status == 0) {
                        array_push($rowRecord, 0);
                    } else {
                        array_push($rowRecord, (float) $paymentArr[$feeID]);
                    }
                } else {
                    array_push($rowRecord, 0);
                }
            }
            // dd($rowRecord);
            $fundsource = $orRecord->source == 0 ? 'ENROLLMENT' : 'NON-ENROLLMENT';

            $status = $orRecord->status == 1 ? 'ACTIVE' : 'CANCELLED';


            if ($orRecord->status == 1) {
                if ($orRecord->scholar_charge == 100) {
                    $rowTotal = '0';
                } else {
                    $rowTotal = (float) $payments->sum('amount');
                }
            } else {
                $rowTotal = '0';
            }

            array_push($rowRecord, $fundsource, $status, $rowTotal);
            array_push($data, $rowRecord);
        }
        # Excel Data End
        //  return ($data);
        //dd($data);

        # Generate Excel

        $file = 'cashier-reports-byDate-' . Auth::user()->id . '.xls';
        return Excel::download(new CashierReportExport($headers, $data), $file);
        # Generate Excel End

    }

    public function cashrere(Request $request, Assessment $assessment)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(-1);
        ini_set('max_execution_time', 7200);

        $from = $request->fr;
        //return $from;
        $to = $request->to;

        $non_enrollment = collect();
        $orRecords = collect([]);

        $orRecords1 = OrRecord::with(['payments', 'studentInfo'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        //$non_enrollment = NonEnrolmentFees::with(['payments', 'studentInfo'])->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        if ($request->mode == 1) {
            $orRecords1 = $orRecords1->where('user', Auth::user()->id);
            //$non_enrollment = $non_enrollment->where('user_id', Auth::user()->id);
        } else {
            $orRecords1 = $orRecords1->where('branch', Auth::user()->branch);
            //$non_enrollment = $non_enrollment->where('branch', Auth::user()->branch);
        }

        $orRecords1 = $orRecords1->orderBy('or_number', 'desc')->get(['*', \DB::raw('"0" as source')]);
        //$non_enrollment = $non_enrollment->orderBy('or_number', 'desc')->get(['*', \DB::raw('"1" as source')]);

        foreach ($orRecords1 as $a) {

            $orRecords = $orRecords->push($a);
        }

        // foreach ($non_enrollment as $b) {

        //     $orRecords = $orRecords->push($b);
        // }


        $file = 'cashier-cash-receipt-record-' . Auth::user()->id . '-from_' . $from . '_to_' . $to;

        $funds = collect([]);

        $data = [];

        # Excel Headers
        $headers = [];
        array_push($headers, 'Date', 'OR Number', 'Name', 'Nature of Collection', 'Collection', 'Deposit', 'Undeposited Collection');

        //query testing

        $funds1 = Fund::whereRaw("fund.fund_id in (select fund from payments where created_at between '$from. 00:00:00' AND '$to.23:59:59')")
            ->orderBy('fund', 'asc')->get();

        // $funds2 = Fund::whereRaw("fund.fund_id in (select fund_id from payments_none where created_at between '$from. 00:00:00' AND '$to.23:59:59')")
        //     ->orderBy('fund', 'asc')->get();


        foreach ($funds1 as $a) {

            $funds = $funds->push($a);
        }

        // foreach ($funds2 as $b) {

        //     $funds = $funds->push($b);
        // }


        //$funds = $funds->push($funds1);

        $fees_stack = [];


        //array_push($data, $headers);
        # Excel Header End

        $getamount = [];
        //return $orRecords->paymentsre()->first();
        foreach ($orRecords as $orRecord) {
            //$cash = $orRecord->sum('amount');//number_format($orRecord->amount, 2);
            if ($orRecord->status == 0) {
                $cash = '0.00'; //number_format($orRecord->amount, 2);
            } else {
                $cash = $orRecord->amount;
            }
            // $totalcash = number_format($cash, 2);
            array_push($getamount, $cash);
        }
        $totalcash = number_format(array_sum($getamount), 2);
        $balnces = [];
        $depo = null;
        $undepo = null;
        //array_push($balnces, 'Balance Forwarded', $cash);
        array_push($balnces, '', '', 'Balance Forwarded', '', $totalcash, '', '');
        array_push($data, $balnces);
        //return $data;

        #Excel Data
        $fund_desc = [];
        $descen = [];
        foreach ($orRecords as $orRecord) {
            $rowRecord = [];

            $or_number = in_array($orRecord->or_number, ['online', 'ONLINE']) ? $orRecord->trans_ref_number : $orRecord->or_number;

            array_push(
                $rowRecord,
                Carbon::parse($orRecord->created_at)->format('Y-m-d'),
                strtoupper($or_number) . ($orRecord->status == 0 ? ' (CANCELLED)' : ''),
                $orRecord->studentInfo == '' ? $orRecord->payor_name : $orRecord->studentInfo->surname . ', ' . $orRecord->studentInfo->firstname
            );
            //dd($orRecord);

            $payments = $orRecord->payments;
            $paymentArr = [];


            foreach ($fees_stack as $feeID) {
                if (array_key_exists($feeID, $paymentArr)) {
                    if ($orRecord->scholar_charge == 100 || $orRecord->status == 0) {
                        array_push($rowRecord, 0);
                    } else {
                        array_push($rowRecord, (float) $paymentArr[$feeID]);
                    }
                } else {
                    array_push($rowRecord, 0);
                }
            }

            //$fundsource = $orRecord->source == 0 ? 'ENROLLMENT' : 'NON-ENROLLMENT'; 

            if ($orRecord->source == 0) {
                if ($orRecord->transaction_type == 1) {
                    if ($orRecord->paymentsre->count() > 1) {

                        $fundsource = '644 Tuition Fee';
                    } else {
                        foreach ($orRecord->paymentsre as $value) {
                            $oks = ($value->fundo);
                            // $g =collect($oks);
                            $descen = $oks->fund . ' ' . $oks->fund_desc;
                            // array_push($descen, $oks);
                            //return $g;
                        }
                        $fundsource =  $descen;
                    }
                } else {
                    $fundsource = '644B Dropping/Changing/Adding Fee';
                }
            } else {
                foreach ($orRecord->payments as $value) {
                    array_push($fund_desc, $value->fund->fund . ' ' . $value->fund->fund_desc);
                }
                $fundsource = implode(", ", $fund_desc);
            }



            if ($orRecord->status == 1) {
                if ($orRecord->scholar_charge == 100) {
                    $rowTotal = '0.00';
                } else {
                    $rowTotals = $orRecord->amount;
                    $rowTotal = number_format($rowTotals, 2);
                }
            } else {
                $rowTotal = '0';
            }

            // dd($totalcash);

            array_push($rowRecord, $fundsource, $rowTotal, $undepo, $undepo);
            array_push($data, $rowRecord);
        }
        //dd($oks);

        # Excel Data End

        //dd($headers, $data);

        # Generate Excel
        $file = 'cashier-cash-receipt-record-' . Auth::user()->id . '-from_' . $from . '_to_' . $to . '.xls';
        return Excel::download(new CashierCashReportExport($headers, $data), $file);
    }
}
